# ReflectionValidator Util

Extends the capability of the symfony validator

## Installation

Install the latest version with

```bash
$ composer require omasn/reflection-validator
```

## Basic Usage

### Example 1:
```php
$changeSet = new ChangeSetCollection(
    collection1: [
        new User(id: 1, name: 'User 1'),
        new User(id: 5, name: 'User 5'),
        new User(id: 7, name: 'User 7'),
    ],
    collection2: [
        new User(id: 1, name: 'EditedUser 1'),
        new User(id: 10, name: 'NewUser 10'),
    ],
    getId: fn (User $user) => $user->getId(),
);

echo $line = '--------' . PHP_EOL;
foreach ($changeSet as $change) {
    if ($change->isAdd()) {
        echo '+' . $change->element2->getName() . PHP_EOL;
        echo $line;
    } elseif ($change->isEdit()) {
        echo '-' . $change->element1->getName() . PHP_EOL;
        echo '+' . $change->element2->getName() . PHP_EOL;
        echo $line;
    } elseif ($change->isRemove()) {
        echo '-' . $change->element1->getName() . PHP_EOL;
        echo $line;
    }
}
```

Output:
```
--------
-User 1
+EditedUser 1
--------
+NewUser 10
--------
-User 5
--------
-User 7
--------
```

### Example 2:
```php
// Create and compare change set
$changeSet = new ChangeSetCollection(
    collection1: [
        new Order(id: Uuid::from('00000000-0000-0000-0000-000000000001'), customer: 'Jon'),
        new Order(id: Uuid::from('00000000-0000-0000-0000-000000000002'), customer: 'Doe'),
    ],
    collection2: [
        ['uuid' => '00000000-0000-0000-0000-000000000001', 'customer' => 'Dan'],
        ['uuid' => null, 'customer' => 'Wendy'],
    ],
    getId: fn (User|array $order) => $order instanceof Order ? $order->getId() : $order['uuid'],
);

// Example helper function
$_separator = '--------' . PHP_EOL;
$_writeOutputBlock = static function(string ...$lines) {
    foreach ($lines as $line) {
        echo $line . PHP_EOL;
    }
    echo $_separator;
}

echo $_separator;
foreach ($changeSet as $change) {
    match ($change->state) {
        ChangeState::add => $_writeOutputBlock(
            '+' . $change->element2['customer'],
        ),
        ChangeState::edit => $_writeOutputBlock(
            '-' . $change->element1->getCustomer(),
            '+' . $change->element2['customer'],
        ),
        ChangeState::remove => $_writeOutputBlock(
            '-' . $change->element1->getCustomer(),
        ),
    }
}

echo PHP_EOL . sprintf(
    '[Added: %d], [Edited: %d], [Removed: %d]',
    $changeSet->countAdded(),
    $changeSet->countEdited(),
    $changeSet->countRemoved(),
);
```

Output:
```
--------
-Jon
+Dan
--------
-Doe
--------
+Wendy
--------

[Added: 1], [Edited: 1], [Removed: 1]
```

## For contributors


### Install cs-fixer
`mkdir -p tools/php-cs-fixer`
`composer require --working-dir=tools/php-cs-fixer friendsofphp/php-cs-fixer`

### Run tests
Exec: `./vendor/bin/phpunit`

### Run lint
Exec cs-fixer: `tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src`
Exec phpstan: `./vendor/bin/phpstan analyse src tests`
Exec psalm: `./vendor/bin/psalm`
