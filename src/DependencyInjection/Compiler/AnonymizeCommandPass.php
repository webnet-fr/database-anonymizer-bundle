<?php

namespace WebnetFr\DatabaseAnonymizerBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use WebnetFr\DatabaseAnonymizerBundle\Command\AnonymizeCommand;
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
        $anonymizeCommandDefinition = $container->getDefinition(AnonymizeCommand::class);

        // Pass default anonymizer configuration to the command.
        $configuration = new Configuration();
        $processor = new Processor();
        $defaultConfig = $processor->processConfiguration(
            $configuration,
            $container->getExtensionConfig('webnet_fr_database_anonymizer')
        );
        $anonymizeCommandDefinition->addMethodCall('setDefaultConfig', [$defaultConfig]);

        // Pass docntrine dbal registry to the command if it exists.
        if ($container->hasDefinition('doctrine')) {
            $anonymizeCommandDefinition->addMethodCall('setRegistry', [new Reference('doctrine')]);
        }
    }
}
