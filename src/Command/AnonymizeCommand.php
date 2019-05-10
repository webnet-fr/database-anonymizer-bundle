<?php

namespace WebnetFr\DatabaseAnonymizerBundle\Command;

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

/**
 * @author Vlad Riabchenko <vriabchenko@webnet.fr>
 */
class AnonymizeCommand extends Command
{
    use AnonymizeCommandTrait;

    protected static $defaultName = 'webnet-fr:anonymizer:anonymize';

    /**
     * @var RegistryInterface
     */
    private $registry;

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
     * @param GeneratorFactoryInterface $generatorFactory
     */
    public function __construct(GeneratorFactoryInterface $generatorFactory)
    {
        parent::__construct();

        $this->generatorFactory = $generatorFactory;
    }

    /**
     * Set registry
     *
     * @param RegistryInterface $registry
     *
     * @return $this
     */
    public function setRegistry($registry)
    {
        $this->registry = $registry;

        return $this;
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
            ->addOption('connection', 'C', InputOption::VALUE_REQUIRED, 'Name of the connection to database.')
        ;
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure you want to anonymize your database?', 'y');

        if (!$questionHelper->ask($input, $output, $question)) {
            return;
        }

        // Retrieve database connection.
        if ($dbUrl = $input->getOption('db_url')) {
            $connection = $this->getConnection($dbUrl);
            $connectionName = 'default';
        } else {
            if (!$this->registry) {
                throw new \LogicException('You must activete doctrine dbal component');
            }

            $connectionName = $input->getOption('connection');
            if (!$connectionName) {
                $connectionName = $this->registry->getDefaultConnectionName();
            }

            $connection = $this->registry->getConnection($connectionName);
        }

        if (!$connection) {
            throw new \LogicException('Cannot find or crete connection');
        }

        // Retrieve anonymizer configuration.
        if ($configFile = $input->getOption('config')) {
            $configFilePath = realpath($input->getArgument('config'));
            if (!is_file($configFilePath)) {
                $output->writeln(sprintf('<error>Configuration file "%s" does not exist.</error>', $configFile));

                return;
            }

            $config = $this->getConfigFromFile($configFilePath);
        } elseif ($this->defaultConfig) {
            if (!array_key_exists($connectionName, $this->defaultConfig['connections'])) {
                throw new \LogicException('You must configure anonymizer for "'.$connectionName.'" connection');
            };

            $config = $this->defaultConfig['connections'][$connectionName];
        } else {
            throw new \InvalidArgumentException('You must either provide the path of configuration file or confiqure the bundle');
        }

        $targetFactory = new TargetFactory($this->generatorFactory);
        $targetFactory->setConnection($connection);
        $targetTables = $targetFactory->createTargets($config);

        $anonymizer = new Anonymizer();
        $anonymizer->anonymize($connection, $targetTables);
    }
}
