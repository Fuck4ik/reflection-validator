<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;

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

/**
 * @internal
 * @coversNothing
 */
final class ReflectionValidatorTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    public function testSimpleValidate(): void
    {
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
    }
}
