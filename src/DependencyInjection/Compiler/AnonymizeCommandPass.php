<?php

namespace WebnetFr\DatabaseAnonymizerBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use WebnetFr\DatabaseAnonymizer\Anonymizer;
use WebnetFr\DatabaseAnonymizer\ConfigGuesser\ConfigGuesser;
use WebnetFr\DatabaseAnonymizerBundle\Command\AnonymizeCommand;
use WebnetFr\DatabaseAnonymizerBundle\Config\AnnotationConfigFactory;
use WebnetFr\DatabaseAnonymizerBundle\DependencyInjection\Configuration;

/**
 * @author Vlad Riabchenko <vriabchenko@webnet.fr>
 */
class AnonymizeCommandPass implements CompilerPassInterface
{
    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(AnonymizeCommand::class)) {
            return;
        }

        $anonymizeCommandDefinition = $container->findDefinition(AnonymizeCommand::class);

        // Pass default anonymizer configuration to the command.
        $configuration = new Configuration();
        $processor = new Processor();
        $defaultConfig = $processor->processConfiguration(
            $configuration,
            $container->getExtensionConfig('webnet_fr_database_anonymizer')
        );
        $anonymizeCommandDefinition->addMethodCall('setDefaultConfig', [$defaultConfig]);

        // Pass the Doctrine registry to the command if it exists.
        if ($container->has('doctrine')) {
            $anonymizeCommandDefinition->addMethodCall('setRegistry', [new Reference('doctrine')]);
        }

        // Enable Doctrine annotations.
        if ($container->has('annotations.reader')) {
            $annotationConfigFactoryDefinition = new Definition(
                AnnotationConfigFactory::class,
                [
                    new Reference('annotations.reader'),
                    new Reference(ConfigGuesser::class),
                ]
            );
            $container->setDefinition(AnnotationConfigFactory::class, $annotationConfigFactoryDefinition);

            $anonymizeCommandDefinition->addMethodCall('enableAnnotations', [new Reference(AnnotationConfigFactory::class)]);
        }

        $anonymizeCommandDefinition->addMethodCall('setAnonymizer', [new Reference(Anonymizer::class)]);
    }
}
