<?php

declare(strict_types=1);

namespace Omasn\ReflectionValidator;

use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Util\PropertyPath;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;

final readonly class ReflectionValidator
{
    public function __construct(
        private PropertyInfoExtractorInterface $propertyInfo,
        private ValidatorInterface $validator,
    ) {
    }

    public static function createSimple(): self
    {
        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();

        $listExtractors = [$reflectionExtractor];
        $typeExtractors = [$phpDocExtractor, $reflectionExtractor];
        $descriptionExtractors = [$phpDocExtractor];
        $accessExtractors = [$reflectionExtractor];
        $propertyInitializableExtractors = [$reflectionExtractor];

        $propertyInfo = new PropertyInfoExtractor(
            $listExtractors,
            $typeExtractors,
            $descriptionExtractors,
            $accessExtractors,
            $propertyInitializableExtractors
        );

        return new self($propertyInfo, (new ValidatorBuilder())->getValidator());
    }

    /**
     * @param class-string           $class
     * @param mixed[]                $data
     * @param GroupSequence|string[] $groups
     *
     * @throws \ReflectionException
     */
    public function validate(
        string $class,
        array $data,
        GroupSequence|array $groups = [],
    ): ConstraintViolationListInterface {
        $contextValidator = $this->validator->startContext();

        $this->validateClass($class, $data, $groups, $contextValidator);

        return $contextValidator->getViolations();
    }

    /**
     * @param class-string           $class
     * @param GroupSequence|string[] $groups
     *
     * @throws \ReflectionException
     */
    private function validateClass(
        string $class,
        mixed $data,
        GroupSequence|array $groups,
        ContextualValidatorInterface $contextValidator,
        string $contextPath = '',
    ): void {
        $reflClass = new \ReflectionClass($class);

        foreach ($this->getConstraints($reflClass->getAttributes()) as $constraint) {
            $contextValidator->validate($data, $constraint, $groups);
        }
        foreach ($reflClass->getProperties() as $property) {
            $propName = $property->getName();
            $propertyPath = PropertyPath::append($contextPath, $propName);

            if (is_array($data)) {
                $value = $data[$propName] ?? null;
            } else {
                $value = $data;
            }

            foreach ($this->getConstraints($property->getAttributes()) as $constraint) {
                $contextValidator->atPath($propertyPath)
                    ->validate($value, $constraint, $groups)
                ;
            }

            $types = $this->propertyInfo->getTypes($reflClass->getName(), $propName);
            if (null === $types) {
                continue;
            }

            foreach ($types as $type) {
                if (null !== $className = $this->getCollectionClassName($type)) {
                    /** @var null|array<array-key, mixed> $value */
                    foreach ($value ?? [] as $key => $datum) {
                        $this->validateClass(
                            class: $className,
                            data: $datum,
                            groups: $groups,
                            contextValidator: $contextValidator,
                            contextPath: $propertyPath . '[' . $key . ']'
                        );
                    }
                }

                /** @var null|class-string $className */
                $className = $type->getClassName();
                if (null !== $className) {
                    $this->validateClass(
                        class: $className,
                        data: $value,
                        groups: $groups,
                        contextValidator: $contextValidator,
                        contextPath: $propertyPath . '[' . $propName . ']'
                    );
                }
            }
        }
    }

    /**
     * @return null|class-string
     */
    private function getCollectionClassName(Type $type): ?string
    {
        if ($type->isCollection()) {
            foreach ($type->getCollectionValueTypes() as $collectionValueType) {
                /** @var null|class-string $className */
                $className = $collectionValueType->getClassName();
                if (null === $className) {
                    continue;
                }

                return $className;
            }

            return null;
        }

        return null;
    }

    /**
     * @param array<array-key, \ReflectionAttribute> $attributes
     *
     * @return \Generator<Constraint>
     */
    private function getConstraints(array $attributes): \Generator
    {
        foreach ($attributes as $reflectionAttribute) {
            if (is_subclass_of($reflectionAttribute->getName(), Constraint::class)) {
                /** @var Constraint $attribute */
                $attribute = $reflectionAttribute->newInstance();

                yield $attribute;
            }
        }
    }
}
