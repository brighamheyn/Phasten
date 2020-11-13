<?php

namespace Phasten;

class ReflectionMember
{
    const IS_PUBLIC = \ReflectionProperty::IS_PUBLIC;

    const IS_PROTECTED = \ReflectionProperty::IS_PROTECTED;

    const IS_PRIVATE = \ReflectionProperty::IS_PRIVATE;

    private string $class;

    private string $name;

    private ?string $type;

    private $resolveValue;

    private $value;

    private $numArgs;

    private string $annotation = "";

    public static function fromReflectionClass(\ReflectionClass $class, object $object = null, array $args = [], string $name = null): self
    {
        $className = $class->getShortName();

        $name = $name ?? $className;

        $type = $class->getName();

        $constr = Ref::getConstructor($type);

        $numArgs = null !== $constr ? $constr->getNumberOfRequiredParameters() : 0;

        $numArgs = $numArgs - count($args);

        $resolveValue = function (...$params) use ($class, $constr, $object, $args) {

            $args = array_merge($args, $params);

            if (null === $constr) {
                return $class->newInstanceArgs($args);
            }

            return null !== $object ? $constr->invokeArgs($object, $args) : null;
        };

        return new self($className, $name, $type, $resolveValue, $numArgs, $object, $class->getDocComment());
    }

    public static function fromReflectionProperty(\ReflectionProperty $property, object $object = null): self
    {
        $class = $property->class;

        $type = null;
        if ($property->hasType()) {
            /**
             * @var ReflectionNamedType
             */
            $propertyType = $property->getType();
            $type = $propertyType->getName();
        }

        $resolveValue = function() use ($class, $property, $object) {

            $defaultValues = Ref::getDefaultProperties($class);

            $defaultValue = isset($defaultValues[$property->getName()]) ? $defaultValues[$property->getName()] : null;

            $property->setAccessible(true);
            $value = null !== $object && $property->isInitialized($object) ? $property->getValue($object) : $defaultValue;
            return $value;
        };

        $value = $resolveValue();

        return new self($class, $property->getName(), $type, $resolveValue, 0, $value, $property->getDocComment());
    }

    public static function fromReflectionMethod(\ReflectionMethod $method, object $object = null, array $args = []): self
    {
        $class = $method->class;

        $type = null;
        if ($method->hasReturnType()) {
            /**
             * @var ReflectionNamedType
             */
            $returnType = $method->getReturnType();
            $type = $returnType->getName();
        }

        $numArgs = $method->getNumberOfRequiredParameters();

        $numArgs = $numArgs - count($args);

        $resolveValue = function(...$params) use ($method, $object, $args) {

            $args = array_merge($args, $params);

            return null !== $object ? $method->invokeArgs($object, $args) : null;
        };

        $value = null;

        return new self($class, "$" . $method->getName(), $type, $resolveValue, $numArgs, $value, $method->getDocComment());
    }

    public function __construct(string $class, string $name, ?string $type, callable $resolveValue, int $numArgs = 0, $value = null, string $annotation = "")
    {
        $this->class = $class;
        $this->name = $name;
        $this->type = $type;
        $this->numArgs = $numArgs;
        $this->resolveValue = $resolveValue;
        $this->value = $value;
        $this->annotation = $annotation;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getNumberOfRequiredParameters(): int
    {
        return $this->numArgs;
    }

    public function hasValue(): bool
    {
        return null !== $this->value;
    }

    /**
     * Returns null if value is null or if the value cannot be resolved given the arguments
     * 
     * @return mixed
     */
    public function getValue(...$args)
    {
        if ($this->hasValue()) {
            return $this->value;
        }

        if (count($args) < $this->numArgs) {
            return null;
        }

        $this->value = call_user_func_array($this->resolveValue, $args);

        return $this->value;
    }

    public function hasType(): bool
    {
        return null !== $this->type;
    }

    public function isBuiltIn(): bool
    {
        return null === $this->type || in_array($this->type, ['string', 'integer', 'boolean', 'double', 'float', 'null']);
    }

    public function isUnit(): bool
    {
        return $this->numArgs === 0;
    }

    public function getAnnotations(): array
    {
        $provider = new DefaultAnnotationProvider();

        return $provider->parse($this->annotation);
    }
}
