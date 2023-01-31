<?php

namespace WebnetFr\DatabaseAnonymizerBundle\Tests\System\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use WebnetFr\DatabaseAnonymizer\Command\GuessConfigCommand;
use WebnetFr\DatabaseAnonymizer\ConfigGuesser\ConfigGuesser;
use WebnetFr\DatabaseAnonymizer\ConfigGuesser\ConfigWriter;
use WebnetFr\DatabaseAnonymizer\GeneratorFactory\ChainGeneratorFactory;
use WebnetFr\DatabaseAnonymizer\GeneratorFactory\ConstantGeneratorFactory;
use WebnetFr\DatabaseAnonymizer\GeneratorFactory\FakerGeneratorFactory;
use WebnetFr\DatabaseAnonymizerBundle\Command\AnonymizeCommand;
use WebnetFr\DatabaseAnonymizerBundle\DependencyInjection\WebnetFrDatabaseAnonymizerExtension;

/**
 * @see WebnetFrDatabaseAnonymizerExtension
 *
 * @author Vlad Riabchenko <vriabchenko@webnet.fr>
 */
class WebnetFrDatabaseAnonymizerExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @inheritdoc
     */
    protected function getContainerExtensions() : array
    {
        return [
            new WebnetFrDatabaseAnonymizerExtension(),
        ];
    }

    public function testServicesLoaded()
    {
        $this->load();

        $this->assertContainerBuilderHasService(ConstantGeneratorFactory::class);
        $this->assertContainerBuilderHasService(FakerGeneratorFactory::class);
        $this->assertContainerBuilderHasService(ChainGeneratorFactory::class);
        $this->assertContainerBuilderHasService(AnonymizeCommand::class);
        $this->assertContainerBuilderHasService(ConfigGuesser::class);
        $this->assertContainerBuilderHasService(ConfigWriter::class);
        $this->assertContainerBuilderHasService(GuessConfigCommand::class);
    }
}
