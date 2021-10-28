# iqomp/handler

A class for handling interaction with model, form validation, and formatter. This
class make it easy to generate general repetition action.

It call form validation automatically for action `create`, `createMany`, and `set`.

Calling `create`, `get`, and `getOne` will return formatted object if data exists.
The returned data is an array for method `get`, and an object for method `create`
and `getOne`. Other method will return as returned by model.

Calling method `get` will also generate pagination data.

## Installation

```
composer require iqomp/handler
```

## Usage

Create a class that extends `Iqomp\Handler\Handler`, and fill some property `model`,
`formatter`, and `forms` as below:

```php
<?php

namespace App\Handler;

class User extends \Iqomp\Handler\Handler
{
    // handling model
    protected string $model = 'App\Model\User';

    // formatter data for the object
    // used for method `get`, `create`, and `getOne`
    protected array $formatter = [
        'name' => 'wallet',
        'options' => []
    ];

    // custom error code for form invalid or general error
    protected array $errors_code = [
        422 => 100004,
        500 => 100003
    ];

    // property name for pagination result per page
    protected string $pager_rpp = 'per_page';

    // property name for pagination result page number
    protected string $pager_page = 'page';

    // property name for pagination total result
    protected string $pager_total = 'total';

    // form name for each methods
    protected array $forms = [
        'create' => 'user/create',
        'createMany' => 'user/createMany',
        'set' => 'user/set'
    ];

    // list of disallowed method to call
    protected array $disalow_methods = [
        'getConnection',
        'getModel'
    ];
}
```

And using it from controller is now as easy as:

```php
    // ...
    $user = new App\Handler\User;

    // get formatted single object
    $object = $user->getOne(['id' => 1]);

    // create new user that should be validated
    // and return the created one after formatted
    $object = $user->create(['...']);
    // get validation errors
    $errors = $user->error();

    // get users page 2
    $objects = $user->get(['status' => 1], 12, 2);
    // get paginations data
    $pager = $user->pagination();
```
