<?php

namespace WebnetFr\DatabaseAnonymizerBundle\Annotation;

/**
 * @Annotation
 * @Target("PROPERTY")
 * @author Vlad Riabchenko <vriabchenko@webnet.fr>
 */
class Anonymize
{
    /**
     * @var string
     */
    public $formatter;
}
