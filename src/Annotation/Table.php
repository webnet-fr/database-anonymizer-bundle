<?php

namespace WebnetFr\DatabaseAnonymizerBundle\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 *
 * @author Vlad Riabchenko <vriabchenko@webnet.fr>
 */
class Table
{
    /**
     * @var bool
     */
    public $guess = false;
}
