<?php

namespace WebnetFr\DatabaseAnonymizerBundle\Command;

use Doctrine\Common\Annotations\Reader;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use WebnetFr\DatabaseAnonymizer\Anonymizer;
use WebnetFr\DatabaseAnonymizer\Command\AnonymizeCommandTrait;
use WebnetFr\DatabaseAnonymizer\Config\TargetFactory;
use WebnetFr\DatabaseAnonymizer\GeneratorFactory\GeneratorFactoryInterface;
use WebnetFr\DatabaseAnonymizerBundle\Config\AnnotationConfigFactory;

/**
 * @author Vlad Riabchenko <vriabchenko@webnet.fr>
 */
class AnonymizeCommand extends Command
{
    use AnonymizeCommandTrait;

    protected static $defaultName = 'webnet-fr:anonymizer:anonymize';

    /**
     * @var GeneratorFactoryInterface
     */
    private $generatorFactory;

    /**
     * Default anonymizer configuration usually defined in webnet_fr_database_anonymizer.yaml.
     *
     * @var array
     */
    private $defaultConfig;

    /**
     * @var RegistryInterface
     */
    private $registry;

    /**
     * @var AnnotationConfigFactory
     */
    private $annotationConfigFactory;

    /**
     * @var Anonymizer
     */
    private $anonymizer;

    /**
     * @param GeneratorFactoryInterface $generatorFactory
     */
    public function __construct(GeneratorFactoryInterface $generatorFactory)
    {
        parent::__construct();

        $this->generatorFactory = $generatorFactory;
    }

    /**
     * Set Doctrine registry.
     *
     * @param RegistryInterface $registry
     */
    public function setRegistry(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Enable annotations.
     *
     * @param AnnotationConfigFactory $annotationConfigFactory
     */
    public function enableAnnotations(AnnotationConfigFactory $annotationConfigFactory)
    {
        $this->annotationConfigFactory = $annotationConfigFactory;
    }

    /**
     * Set default anonymizer configuration.
     *
     * @param array $defaultConfig
     *
     * @return $this
     */
    public function setDefaultConfig(array $defaultConfig)
    {
        $this->defaultConfig = $defaultConfig;

        return $this;
    }

    public function setAnonymizer(Anonymizer $anonymizer)
    {
        $this->anonymizer = $anonymizer;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('webnet-fr:anonymizer:anonymize')
            ->setDescription('Anoymize database.')
            ->setHelp('Anoymize database according to GDPR (General Data Protection Regulation).')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file.')
            ->addOption('url', 'U', InputOption::VALUE_REQUIRED, 'Database connection string.')
            ->addOption('type', 't', InputOption::VALUE_REQUIRED, 'Database type.')
            ->addOption('host', 'H', InputOption::VALUE_REQUIRED, 'Database host.')
            ->addOption('port', 'P', InputOption::VALUE_REQUIRED, 'Database port.')
            ->addOption('database', 'd', InputOption::VALUE_REQUIRED, 'Database name.')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'User.')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Password.')
            ->addOption('connection', 'C', InputOption::VALUE_REQUIRED, 'Name of the connection to database.')
            ->addOption('annotations', 'a', InputOption::VALUE_NONE, 'Use annotations. "em" option must be provided.')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'Entity manager.')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure you want to anonymize your database?', true);

        if (!$questionHelper->ask($input, $output, $question)) {
            return;
        }

        $em = null;
        $connection = null;
        $configName = null;


        try {
            $connection = $this->getConnectionFromInput($input);
        } catch (DBALException $e) {
            $connection = null;
        }

        // Retrieve database connection.
        if ($connection) {
            $configName = 'default';
        } elseif ($emName = $input->getOption('em')) {
            $em = $this->registry->getEntityManager($emName);
            $connection = $em->getConnection();
            $configName = (string) $emName;
        } else {
            if (!$this->registry) {
                throw new \LogicException('You must activete doctrine dbal component');
            }

            $configName = $input->getOption('connection');
            if (!$configName) {
                $configName = $this->registry->getDefaultConnectionName();
            }

            $connection = $this->registry->getConnection($configName);
        }

        if (!$connection) {
            throw new \LogicException('Cannot find or crete connection');
        }

        // Retrieve anonymizer configuration.
        if ($input->getOption('annotations')) {
            if (!$em) {
                $output->writeln('<error>You must pass entity manager name in "--em" option. Pass "--em=default" if there is only one entity manager.</error>');

                return;
            }

            if (!$this->annotationConfigFactory) {
                $output->writeln('<error>You must enable Doctrine annotations: "annotations.reader" service is required.</error>');

                return;
            }

            $config = $this->annotationConfigFactory->getConfig($em->getMetadataFactory()->getAllMetadata());
        } elseif ($configFile = $input->getOption('config')) {
            $configFilePath = realpath($input->getOption('config'));
            if (!is_file($configFilePath)) {
                $output->writeln(sprintf('<error>Configuration file "%s" does not exist.</error>', $configFile));

                return;
            }

            $config = $this->getConfigFromFile($configFilePath);
        } elseif (!empty($this->defaultConfig)) {
            if (!array_key_exists($configName, $this->defaultConfig['connections'])) {
                throw new \LogicException('You must configure anonymizer for "'.$configName.'" connection');
            };

            $config = $this->defaultConfig['connections'][$configName];
        } else {
            throw new \InvalidArgumentException('You must either provide the path of configuration file or confiqure the bundle or define annotations.');
        }

        $targetFactory = new TargetFactory($this->generatorFactory);
        $targetFactory->setConnection($connection);
        $targetTables = $targetFactory->createTargets($config);

        $this->anonymizer->anonymize($connection, $targetTables);
    }
}
