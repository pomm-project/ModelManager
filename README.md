# Pomm ModelManager

[![Latest Stable Version](https://poser.pugx.org/pomm-project/model-manager/v/stable)](https://packagist.org/packages/pomm-project/model-manager) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/pomm-project/ModelManager/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/pomm-project/ModelManager/?branch=master) [![Build Status](https://travis-ci.org/pomm-project/ModelManager.svg)](https://travis-ci.org/pomm-project/ModelManager) [![Monthly Downloads](https://poser.pugx.org/pomm-project/model-manager/d/monthly.png)](https://packagist.org/packages/pomm-project/model-manager) [![License](https://poser.pugx.org/pomm-project/model-manager/license.svg)](https://packagist.org/packages/pomm-project/model-manager)


ModelManager is a [Pomm project](http://www.pomm-project.org) package. It makes developers able to manage entities upon the database through model classes. **It is not an ORM**, it grants developers with the ability to perform native queries using all of Postgres’SQL and use almost all its types. This makes model layer to meet with performances while staying lean.

This package will provide:

 * Model classes with all common built-in queries (CRUD but also, `count` and `exists`).
 * Flexible entities
 * Embedded entities converter
 * Model Layer to group model computations in transactions.

The model layer also proposes methods to leverage Postgres nice transaction settings (constraint deferring, isolation levels, read / write access modes etc.).

## Installation

Pomm components are available on [packagist](https://packagist.org/packages/pomm-project/) using [composer](https://packagist.org/). To install and use Pomm's model manager, add a require line to `"pomm-project/model-manager"` in your `composer.json` file. It is advised to install the [CLI package](https://github.com/pomm-project/Cli) as well.

In order to load the model manager's poolers at startup, it is possible to use the provided `SessionBuilder` in Pomm's configuration:

```php
$pomm = new Pomm([
    'project_name' => [
        'dsn' => …,
        'class:session_builder' => '\PommProject\ModelManager\SessionBuilder',
    ],
    …
]);
```

It is better to provide dedicated session builders with your project.

## Documentation

The model manager’s documentation is available [either online](https://github.com/pomm-project/ModelManager/blob/master/documentation/model_manager.rst) or directly in the `documentation` folder.

## Tests

This package uses Atoum as unit test framework. The tests are located in `sources/tests`. This package also provides a `ModelSessionAtoum` class so the test classes can directly get sessions with the `model` and `model layer` poolers loaded.
