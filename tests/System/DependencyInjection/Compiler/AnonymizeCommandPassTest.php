<?php

namespace WebnetFr\DatabaseAnonymizerBundle\Tests\System\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use WebnetFr\DatabaseAnonymizerBundle\Command\AnonymizeCommand;
use WebnetFr\DatabaseAnonymizerBundle\Config\AnnotationConfigFactory;
use WebnetFr\DatabaseAnonymizerBundle\DependencyInjection\Compiler\AnonymizeCommandPass;

/**
 * @see AnonymizeCommandPass
 *
 * @author Vlad Riabchenko <vriabchenko@webnet.fr>
 */
class AnonymizeCommandPassTest extends AbstractCompilerPassTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AnonymizeCommandPass());
        $container->prependExtensionConfig('webnet_fr_database_anonymizer', $this->getConfig());
    }

    public function testConfigPassed()
    {
        $this->setDefinition(AnonymizeCommand::class, new Definition());
        $this->compile();

        $expectedConfig = [
            'connections' => [
                'default' => $this->getConfig(),
            ]
        ];

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            AnonymizeCommand::class,
            'setDefaultConfig',
            [$expectedConfig]
        );
    }

    public function testDoctrinePassed()
    {
        $this->setDefinition(AnonymizeCommand::class, new Definition());
        $this->setDefinition('doctrine', new Definition());
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            AnonymizeCommand::class,
            'setRegistry',
            [new Reference('doctrine')]
        );
    }

    public function testAnnotationReaderPassed()
    {
        $this->setDefinition(AnonymizeCommand::class, new Definition());
        $this->setDefinition('annotations.reader', new Definition());
        $this->compile();

        $this->assertContainerBuilderHasService(AnnotationConfigFactory::class);

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            AnonymizeCommand::class,
            'enableAnnotations',
            [new Reference(AnnotationConfigFactory::class)]
        );
    }

    /**
     * @return array
     */
    private function getConfig()
    {
        return [
            'defaults' => [
                'locale' => 'fr_FR',
            ],
            'tables' => [
                'users' => [
                    'fields' => [],
                    'primary_key' => [],
                ],
            ],
        ];
    }
}
