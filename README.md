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

[database anonymizer]: https://github.com/webnet-fr/database-anonymizer
[Faker]: https://github.com/fzaninotto/Faker
[how to configure the fields to anonymize]: https://github.com/webnet-fr/database-anonymizer#how-to-configure-the-fields-to-anonymize-
