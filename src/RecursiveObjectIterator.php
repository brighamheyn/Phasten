<?php

namespace Phasten;

use Closure;
use ReflectionProperty;

class RecursiveObjectIterator extends \RecursiveArrayIterator
{
    const IS_PROPERTY = 8;

    const IS_METHOD = 16;

    public int $flags = ReflectionMember::IS_PUBLIC | self::IS_PROPERTY;

    public function __construct(object $object)
    {
        $class = get_class($object);

        $members = [];
        if ($object instanceof \stdClass) {
            foreach (get_object_vars($object) as $name => $value) {
                $members[] = new ReflectionMember("Object", $name, is_object($value) ? get_class($value) : gettype($value), fn() => $value, 0, $value);
            }
        } else {

            if (false === class_exists($class) && false === interface_exists($class)) {
                throw new \InvalidArgumentException("Argument 1 to " . self::class . " must be a valid class or interface.");
            }

            $properties = $this->flags & self::IS_PROPERTY ? Ref::getProperties($class, $this->flags) : [];

            // we only want to include properties that have not been "unset" at runtime
            $filtered = [];
            foreach ($properties as $p) {

                $propertyNames = Closure::bind(function() {
                    return get_object_vars($this);
                }, $object, $object)();

                if (array_key_exists($p->getName(), $propertyNames)) {
                    $filtered[] = $p;
                }
            }

            $propertyMembers = array_map(fn($p) => ReflectionMember::fromReflectionProperty($p, $object), $filtered);

            $methodMembers = array_map(function($method) use ($object) { 
                return ReflectionMember::fromReflectionMethod($method, $object, []); 
            }, $this->flags & self::IS_METHOD ? Ref::getMethods($class, $this->flags): []);
    
            $members = array_merge($members, $propertyMembers, $methodMembers);
        }

        parent::__construct($members);
    }

    /**
     * @return ReflectionMember
     */
    public function current()
    {
        return parent::current();
    }

    public function hasChildren(): bool
    {
        return is_array($this->current()->getValue()) || is_object($this->current()->getValue());
    }

    public function getChildren()
    {
        return new self((object)$this->current()->getValue());
    }
}