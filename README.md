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
class A {
    public int $number;
    /** @var B[] */
    public array $rows;
}
class B {
    public int $number;
    /** @var C[] */
    public array $rows;
}
class C {
    #[Assert\Positive]
    public int $number;
}

$reflValidator = Omasn\ReflectionValidator\ReflectionValidator::createSimple();

$violations = $reflValidator->validate(A::class, [
    'number' => 0,
    'rows' => [
        [
            'number' => 0,
            'rows' => [
                [
                    'number' => -1,
                ],
            ],
        ],
    ],
]);

self::assertEquals(1, $violations->count());
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
