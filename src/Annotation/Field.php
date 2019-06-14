<?php

namespace WebnetFr\DatabaseAnonymizerBundle\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 *
 * @author Vlad Riabchenko <vriabchenko@webnet.fr>
 */
class Field
{
    /**
     * @var array
     */
    private $config;

    /**
     * @param array $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }
}
