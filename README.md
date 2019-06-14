# Database anonymizer bundle

### Why ?

[General Data Protection Regulation] (GDPR) imposes strict rules in the domain of
information storage and treatment. You must not treat the users' personal data 
unless there is a strong necessity. In case you want to dump a production database
in order to use it during development you cannot store or use peronal data in 
a dumped database anymore. You must delete or anonymize personal information before
importing a production database in your developpment setting.


### How ?

This bundle is based on our [database anonymizer] library which in turn relies
on [Faker]. After installation the command `webnet-fr:anonymizer:anonymize` will
be available in your application. 

Anonymize the database using a specified connection and a specified config file:
```bash
php bin/console webnet-fr:anonymizer:anonymize --connection=<name of connection> --config=<config file path>
```

Anonymize the database using a specified connection and a default bundle config:
```bash
php bin/console webnet-fr:anonymizer:anonymize --connection=<name of connection>
```

Anonymize the database using a default connection and a specified config file:
```bash
php bin/console webnet-fr:anonymizer:anonymize --config=<config file path>
```

Anonymize the database using a default connection and a default bundle config:
```bash
php bin/console webnet-fr:anonymizer:anonymize 
```


### How to install ?

Require the bundle:

```bash
composer require webnet-fr/database-anonymizer-bundle
```

Activate it. Here is an example for Symfony 4:

```php
// config/bundles.php 

return [
    // ...
    WebnetFr\DatabaseAnonymizerBundle\WebnetFrDatabaseAnonymizerBundle::class => ['dev' => true],
];
```

```yaml
# config/dev/webnet_fr_database_anonymizer.yaml
webnet_fr_database_anonymizer:
    # configuration
```


### How to configure the fields to anonymize ?

Check out [how to configure the fields to anonymize] of the database anonymizer
library. The bundle provides you with the same configuration with one addition: 
you can configure anonymization par each connection.

- Configuration of one default connection:

```yaml
# packages/dev/webnet_fr_anonymizer.yaml
webnet_fr_database_anonymizer:
    # using default connection
    tables:
        <table name>:
            primary_key: <primary key field>
            fields:
                <field name>:
                    generator: <generator>
```

```yaml
# packages/doctrine.yaml
doctrine:
    # default
    dbal:
        # driver, host, user, password, etc.
```

- Configuration of multiple connections:

```yaml
# packages/dev/webnet_fr_anonymizer.yaml
webnet_fr_database_anonymizer:
    connections:
        first_connection:
            tables:
                <table name>:
                    primary_key: <primary key field>
                    fields:
                        <field name>:
                            generator: <generator>

        second_connection:
            tables:
                <table name>:
                    primary_key: <primary key field>
                    fields:
                        <field name>:
                            generator: <generator>
```
     
```yaml
# packages/doctrine.yaml
doctrine:
    dbal:
        default_connection: user_database
        connections:
            first_connection:
                # driver, host, user, password, etc.

            second_connection:
                # driver, host, user, password, etc.
```

- Using annotations:

If you create [entities] you can configure anonymization with annotations :
```
use Doctrine\ORM\Mapping as ORM;
use WebnetFr\DatabaseAnonymizerBundle\Annotation as Anonymize;

/**
 * @ORM\Table(name="orders")
 * @ORM\Entity
 * 
 * This annotation marks the entities to anonymize.
 * @Anonymize\Table()
 */
class Orders
{
    /**
     * @ORM\Column(name="id", type="integer", nullable=false, options={"unsigned"=true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(name="address", type="string", length=256, nullable=true)
     * @Anonymize\Field(generator="faker", formatter="address")
     */
    public $address;

    /**
     * @ORM\Column(name="zip_code", type="string", length=10, nullable=true)
     * @Anonymize\Field(generator="faker", formatter="postcode")
     */
    public $zipCode;

    /**
     * @ORM\Column(name="comment", type="text", length=0, nullable=true)
     * @Anonymize\Field(generator="faker", formatter="text", arguments={300})
     */
    public $comment;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @Anonymize\Field(generator="faker", formatter="dateTime", date_format="Y-m-d H:i:s")
     */
    public $createdAt;

    /**
     * @ORM\Column(name="comment_history", type="array", nullable=true)
     * 
     * A custom generator with its custom arguments.
     * @Anonymize\Field(generator="comment_history", max_messages_nb=5)
     */
    public $commentHistory;
}
```


### How add your own custom generator ?

If you are not satisfied by the generators Faker gives you you can always add your own. 

Imagine you have an entity that stores the users' orders: 

```php
use Doctrine\ORM\Mapping as ORM;

/**
 * Users' orders.
 *
 * @ORM\Table(name="orders")
 * @ORM\Entity
 */
class Orders
{
    /**
     * History of all user's comments. 
     * @var string[]
     *
     * @ORM\Column(name="comments", type="array", nullable=true)
     */
    public $comments;
}
```

And you would like to anonymize each comment in this array:

```yaml
webnet_fr_database_anonymizer:
    tables:
        # ...

        orders:
            fields:
                # ...
                comments:
                    generator: comment_history # your generator
```

In most cases you'll have to add two classes:

1. A factory:

```php
namespace App\DatabaseAnonymizer;

use Faker\Factory;
use WebnetFr\DatabaseAnonymizer\Exception\UnsupportedGeneratorException;
use WebnetFr\DatabaseAnonymizer\Generator\GeneratorInterface;
use WebnetFr\DatabaseAnonymizer\GeneratorFactory\GeneratorFactoryInterface;

/**
 * The factory that creates a generator out of provided configuration.
 * It is a Symfony service.
 */
class CommentHistoryGeneratorFactory implements GeneratorFactoryInterface
{
    /**
     * @param array $config
     *        An array of the configuration for field to anonymize. It contains
     *        all specified entries, like "generator", "unique", "date_format",
     *        "my_custom_entry", etc.
     *
     * @throws \WebnetFr\DatabaseAnonymizer\Exception\UnsupportedGeneratorException
     *          The factory MUST throw "UnsupportedGeneratorException" if it is
     *          impossible to create the generator for provided configuration.
     *
     * @return GeneratorInterface
     */
    public function getGenerator($config): GeneratorInterface
    {
        // Check if the field should be anonymized with "comment_history" encoder.
        $generatorKey = $config['generator'];
        if ('comment_history' !== $generatorKey) {
            throw new UnsupportedGeneratorException($generatorKey.' generator is not known');
        }

        // Retrieve any configuration values you need.
        $locale = $config['locale'] ?? 'en_US';
        $minMessagesNb = $config['min_messages_nb'] ?? 1;
        $maxMessagesNb = $config['max_messages_nb'] ?? 10;
        
        // Create and configure generator.
        // Usually there is ONE generator instance for ONE field to anonymize
        // because there could be different config values for differet fields
        // even though these fields are anoymized with the same 
        // "comment_history" generator.
        $faker = Factory::create($locale);
        $generator = new CommentHistoryGenerator($faker);
        $generator->setMinMessagesNb($minMessagesNb);
        $generator->setMaxMessagesNb($maxMessagesNb);

        return $generator;
    }
}
```

Since `CommentHistoryGeneratorFactory` is a Symfony service it can depend on
any other service (for example on `UserPasswordEncoderInterface` to be able to encode
passwords).

If you use `autodiscover` and `autoconfiguraiton` of Symfony services 
that is all you need. Otherwise you need to register the factory as a service:

```yaml
services:
    App\DatabaseAnonymizer\CommentHistoryGeneratorFactory:
        tags: ["database_anonymizer.generator_factory"]
```


2. A generator:

```php
<?php

namespace App\DatabaseAnonymizer;

use Faker\Generator;
use WebnetFr\DatabaseAnonymizer\Generator\GeneratorInterface;

/**
 * Anonmyizer generator that generates comment history.
 */
class CommentHistoryGenerator implements GeneratorInterface
{
    /**
     * Faker generator
     * @var Generator
     */
    private $faker;

    /**
     * Minimum number of comments in history.
     * @var int
     */
    private $minMessagesNb = 1;

    /**
     * Maximum number of comments in history.
     * @var int
     */
    private $maxMessagesNb = 10;
    
    // Constructors, setters.

    /**
     * Generates new random value for each line.
     */
    public function generate()
    {
        $comments = [];
        foreach (range(0, mt_rand(1, 10)) as $i) {
            $comments[] = $this->faker->realText();
        };

        return serialize($comments);
    }
}
```


[General Data Protection Regulation]: https://en.wikipedia.org/wiki/General_Data_Protection_Regulation
[database anonymizer]: https://github.com/webnet-fr/database-anonymizer
[Faker]: https://github.com/fzaninotto/Faker
[how to configure the fields to anonymize]: https://github.com/webnet-fr/database-anonymizer#how-to-configure-the-fields-to-anonymize-
[entities]: https://symfony.com/doc/current/doctrine.html
