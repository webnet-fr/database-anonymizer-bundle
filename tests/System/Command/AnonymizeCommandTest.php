<?php

namespace WebnetFr\DatabaseAnonymizerBundle\Tests\System\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use WebnetFr\DatabaseAnonymizer\ConfigGuesser\ConfigGuesser;
use WebnetFr\DatabaseAnonymizer\GeneratorFactory\ChainGeneratorFactory;
use WebnetFr\DatabaseAnonymizer\GeneratorFactory\ConstantGeneratorFactory;
use WebnetFr\DatabaseAnonymizer\GeneratorFactory\FakerGeneratorFactory;
use WebnetFr\DatabaseAnonymizerBundle\Command\AnonymizeCommand;
use WebnetFr\DatabaseAnonymizerBundle\Config\AnnotationConfigFactory;
use WebnetFr\DatabaseAnonymizerBundle\Tests\System\SystemTestTrait;

/**
 * @author Vlad Riabchenko <vriabchenko@webnet.fr>
 */
class AnonymizeCommandTest extends TestCase
{
    use SystemTestTrait;

    /**
     * {@inheritdoc}
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function setUp() : void
    {
        $this->regenerateUsersOrders();
    }

    public function testWithConfigFile()
    {
        $generator = new ChainGeneratorFactory();
        $generator->addFactory(new ConstantGeneratorFactory())
            ->addFactory(new FakerGeneratorFactory());

        $command = (new Application('Database anonymizer', '0.0.1'))
            ->add(new AnonymizeCommand($generator));

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(array('y'));
        $commandTester->execute([
            'command' => $command->getName(),
            '--config' => realpath(__DIR__.'/../../config/config.yaml'),
            '--type' => $GLOBALS['db_type'],
            '--host' => $GLOBALS['db_host'],
            '--port' => $GLOBALS['db_port'],
            '--database' => $GLOBALS['db_name'],
            '--user' => $GLOBALS['db_username'],
            '--password' => $GLOBALS['db_password'],
        ]);

        $this->doTestValues();
    }

    public function testWithAnnotations()
    {
        $generator = new ChainGeneratorFactory();
        $generator->addFactory(new ConstantGeneratorFactory())
            ->addFactory(new FakerGeneratorFactory());

        $annotationReader = new AnnotationReader();
        $configGuesser = new ConfigGuesser();
        $annotationConfigFactory = new AnnotationConfigFactory($annotationReader, $configGuesser);
        $anonymizeCommand = new AnonymizeCommand($generator);
        $anonymizeCommand->enableAnnotations($annotationConfigFactory);

        $registry = new Registry();

        $command = (new Application('Database anonymizer', '0.0.1'))
            ->add($anonymizeCommand);

        $commandTester = new CommandTester($command);
        $commandTester->setInputs(array('y'));
        $commandTester->execute([
            'command' => $command->getName(),
            '--annotations' => true,
            '--em' => 'default',
        ]);

        $this->doTestValues();
    }

    /**
     * Test actual values.
     */
    private function doTestValues()
    {
        $connection = $this->getConnection();

        $selectSQL = $connection->createQueryBuilder()
            ->select('email, firstname, lastname, birthdate, phone, password')
            ->from('users')
            ->getSQL();
        $selectStmt = $connection->prepare($selectSQL);
        $result = $selectStmt->execute();

        while ($row = $result->fetchAssociative()) {
            $this->assertTrue(is_string($row['email']));
            $this->assertTrue(is_string($row['firstname']));
            $this->assertTrue(is_string($row['lastname']));
            $this->assertTrue(is_string($row['birthdate']));
            $this->assertTrue(is_string($row['phone']) || is_null($row['phone']));
            $this->assertTrue(is_string($row['password']));
        }

        $selectSQL = $connection->createQueryBuilder()
            ->select('address, street_address, zip_code, city, country, comment, comment, created_at')
            ->from('orders')
            ->getSQL();
        $selectStmt = $connection->prepare($selectSQL);
        $result = $selectStmt->execute();

        while ($row = $result->fetchAssociative()) {
            $this->assertTrue(is_string($row['address']));
            $this->assertTrue(is_string($row['street_address']));
            $this->assertTrue(is_string($row['zip_code']));
            $this->assertTrue(is_string($row['city']));
            $this->assertTrue(is_string($row['country']));
            $this->assertTrue(is_string($row['comment']));
            $this->assertTrue(is_string($row['created_at']));
        }
    }
}
