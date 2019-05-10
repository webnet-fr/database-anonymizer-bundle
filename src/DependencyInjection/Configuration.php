<?php

namespace WebnetFr\DatabaseAnonymizerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use WebnetFr\DatabaseAnonymizer\Config\Configuration as BaseConfiguration;
use WebnetFr\DatabaseAnonymizer\Config\ConfigurationTrait;

/**
 * @author Vlad Riabchenko <vriabchenko@webnet.fr>
 */
class Configuration implements ConfigurationInterface
{
    use ConfigurationTrait;

    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('webnet_fr_database_anonymizer');

        $connectionsRootNode = (new TreeBuilder('connections'))->getRootNode();
        $node = $connectionsRootNode
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->prototype('array');

        $this->configureAnonymizer($node);

        $treeBuilder->getRootNode()
            ->beforeNormalization()
                ->ifTrue(static function ($v) {
                    return is_array($v) && !array_key_exists('connections', $v);
                })
                ->then(static function ($v) {
                    $connection = [];
                    foreach ($v as $key => $value) {
                        $connection[$key] = $v[$key];
                        unset($v[$key]);
                    }

                    $v['connections'] = ['default' => $connection];

                    return $v;
                })
            ->end()
            ->append($connectionsRootNode);

        return $treeBuilder;
    }
}
