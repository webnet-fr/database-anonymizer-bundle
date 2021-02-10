<?php

namespace WebnetFr\DatabaseAnonymizerBundle\Config;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\ClassMetadata;
use WebnetFr\DatabaseAnonymizer\ConfigGuesser\ConfigGuesser;
use WebnetFr\DatabaseAnonymizer\Exception\GuesserMissingHintException;
use WebnetFr\DatabaseAnonymizerBundle\Annotation\Field as AnonymizerField;
use WebnetFr\DatabaseAnonymizerBundle\Annotation\Table as AnonymizerTable;

/**
 * @author Vlad Riabchenko <vriabchenko@webnet.fr>
 */
class AnnotationConfigFactory
{
    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * @var ConfigGuesser
     */
    private $configGuesser;

    /**
     * @param Reader $annotationReader
     * @param ConfigGuesser $configGuesser
     */
    public function __construct(Reader $annotationReader, ConfigGuesser $configGuesser)
    {
        $this->annotationReader = $annotationReader;
        $this->configGuesser = $configGuesser;
    }

    /**
     * @param ClassMetadata[] $allMetadata
     * @return array
     */
    public function getConfig(array $allMetadata)
    {
        $config = [];

        foreach ($allMetadata as $metadata) {
            $reflClass = new \ReflectionClass($metadata->name);
            $classAnnotation = $this->annotationReader->getClassAnnotation($reflClass, AnonymizerTable::class);
            if (!$classAnnotation instanceof AnonymizerTable) {
                continue;
            }

            $tableName = $metadata->table['name'];
            $config[$tableName] = [
                'primary_key' => $metadata->identifier,
                'fields' => [],
	    ];

            if ($classAnnotation->truncate) {
                $config[$tableName]['truncate'] = true;
                continue;
            }

            foreach ($metadata->fieldMappings as $fieldName => $fieldMapping) {
                if (in_array($fieldName, $metadata->identifier)) {
                    continue;
                }

                $reflProperty = $reflClass->getProperty($fieldName);

                $fieldAnnotation = $this->annotationReader->getPropertyAnnotation($reflProperty, AnonymizerField::class);
                $fieldConfig = null;
                if ($fieldAnnotation instanceof AnonymizerField) {
                    $fieldConfig = $fieldAnnotation->getConfig();
                } elseif ($classAnnotation->guess) {
                    try {
                        $fieldConfig = $this->configGuesser::guessColumn($fieldName)->getConfigArray();
                    } catch (GuesserMissingHintException $e) {
                        try {
                            $fieldConfig = $this->configGuesser::guessColumn($fieldMapping['columnName'])->getConfigArray();
                        } catch (GuesserMissingHintException $e) {
                        }
                    }
                }

                if ($fieldConfig) {
                    $config[$tableName]['fields'][$fieldMapping['columnName']] = $fieldConfig;
                }
            }

            if (empty($config[$tableName]['fields'])) {
                unset($config[$tableName]);
            }
        }

        return ['tables' => $config];
    }
}
