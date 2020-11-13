<?php

namespace Phasten;

abstract class Ref
{
    private static $classCache = [];

    private static $propertyCache = [];

    private static $defaultPropertyCache = [];

    private static $constructorCache = [];

    private static $constructorParametersCache = [];

    private static $methodCache = [];

    private static $methodParametersCache = [];

    private static $functionCache = [];

    private static $functionParametersCache = [];

    private static $closureCache = [];

    private static $closureParametersCache = [];

    private static $closureReferenceCache = [];

    public static function getClass($class): \ReflectionClass
    {
        $className = is_object($class) ? get_class($class) : $class;

        if (!isset(self::$classCache[$className])) {
            self::$classCache[$className] = new \ReflectionClass($className);
        }

        return self::$classCache[$className];
    }

    public static function getProperties(string $className, $filter = \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE): array
    {
        if (!isset(self::$propertyCache[$className][$filter])) {

            $properties = [];
            foreach (self::getClass($className)->getProperties($filter) as $prop) {
                $properties[$prop->getName()] = $prop;
            }

            self::$propertyCache[$className][$filter] = $properties;
        }

        return self::$propertyCache[$className][$filter];
    }

    public static function getDefaultProperties(string $className): array
    {
        if (!isset(self::$defaultPropertyCache[$className])) {

            $properties = [];
            foreach (self::getClass($className)->getDefaultProperties() as $name => $defaultValue) {
                $properties[$name] = $defaultValue;
            }

            self::$defaultPropertyCache[$className] = $properties;
        }

        return self::$defaultPropertyCache[$className];
    }

    public static function getPropertyByName(string $className, string $propertyName): ?\ReflectionProperty
    {
        $properties = self::getProperties($className);

        return @$properties[$propertyName][0];
    }

    public static function getConstructor(string $className): ?\ReflectionMethod
    {        
        if (!isset(self::$constructorCache[$className])) {
            self::$constructorCache[$className] = self::getClass($className)->getConstructor();
        }

        return self::$constructorCache[$className];
    }

    public static function getMethod(string $className, string $methodName): ?\ReflectionMethod
    {        
        if (!isset(self::$methodCache[$className])) {
            self::getMethods($className);
        }

        return @self::$methodCache[$className][$methodName];
    }

    public static function getMethods(string $className): array
    {
        if (!isset(self::$methodCache[$className])) {
            foreach (self::getClass($className)->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $methodName = $method->getName();
                self::$methodCache[$className][$methodName] = $method;
            }
        }

        return self::$methodCache[$className] ?? [];
    }

    public static function getFunction(string $functionName): \ReflectionFunction
    {        
        if (!isset(self::$functionCache[$functionName])) {
            self::$functionCache[$functionName] = new \ReflectionFunction($functionName);
        }

        return self::$functionCache[$functionName];
    }

    public static function getClosure(\Closure $closure) : \ReflectionFunction
    {
        $id = \spl_object_id($closure);

        // object cannot be dereferenced
        if (!isset(self::$closureReferenceCache[$id])) {
            self::$closureReferenceCache[$id] =& $closure;
        }
        
        if (!isset(self::$closureCache[$id])) {
            self::$closureCache[$id] = new \ReflectionFunction($closure);
        }

        return self::$closureCache[$id];
    }

    public static function getConstructorParameters(string $className): array
    {
        if (!isset(self::$constructorParametersCache[$className])) {

            $parameters = [];
            if (null !== $ctor = self::getConstructor($className)) {
                foreach($ctor->getParameters() as $param) {
                    $parameters[$param->getName()] = $param;
                }
            }
            
            self::$constructorParametersCache[$className] = $parameters;
        }

        return self::$constructorParametersCache[$className];
    }

    public static function getMethodParameters(string $className, string $methodName): array
    {
        if(!isset(self::$methodParametersCache[$className][$methodName])) {
            
            $parameters = [];
            foreach(self::getMethod($className, $methodName)->getParameters() as $param) {
                $parameters[$param->getName()] = $param;
            }

            self::$methodParametersCache[$className][$methodName] = $parameters;
        }

        return self::$methodParametersCache[$className][$methodName];
    }

    public static function getFunctionParameters(string $functionName): array
    {
        if(!isset(self::$functionParametersCache[$functionName])) {
            
            $parameters = [];
            foreach(self::getFunction($functionName)->getParameters() as $param) {
                $parameters[$param->getName()] = $param;
            }

            self::$functionParametersCache[$functionName] = $parameters;
        }

        return self::$functionParametersCache[$functionName];
    }

    public static function getClosureParameters(\Closure $closure): array
    {
        $id = \spl_object_id($closure);

        if(!isset(self::$closureParametersCache[$id])) {
            
            $parameters = [];
            foreach(self::getClosure($closure)->getParameters() as $param) {
                $parameters[$param->getName()] = $param;
            }

            self::$closureParametersCache[$id] = $parameters;
        }

        return self::$closureParametersCache[$id];
    }

    public static function getConstructorParameterByName(string $className, string $parameterName): ?\ReflectionParameter
    {
        $parameters = self::getConstructorParameters($className);

        return @$parameters[$parameterName];
    }

    public static function getConstructorParameterByPosition(string $className, int $parameterPosition): ?\ReflectionParameter
    {
        $parameters = self::getConstructorParameters($className);

        if ($parameterPosition < 0 || $parameterPosition >= count($parameters)) {
            return null;
        }

        foreach ($parameters as $parameter) {
            
            if ($parameterPosition == $parameter->getPosition()) {
                return $parameter;
            }
        }

        return null;
    }

    public static function getMethodParameterByName(string $className, string $methodName, string $parameterName): ?\ReflectionParameter
    {
        $parameters = self::getMethodParameters($className, $methodName);

        return @$parameters[$parameterName];
    }

    public static function getMethodParameterByPosition(string $className, string $methodName, int $parameterPosition): ?\ReflectionParameter
    {
        $parameters = self::getMethodParameters($className, $methodName);

        if ($parameterPosition < 0 || $parameterPosition >= count($parameters)) {
            return null;
        }

        foreach ($parameters as $parameter) {
            if ($parameterPosition == $parameter->getPosition()) {
                return $parameter;
            }
        }

        return null;
    }

    public static function getFunctionParameterByName(string $functionName, string $parameterName): ?\ReflectionParameter
    {
        $parameters = self::getFunctionParameters($functionName);

        return @$parameters[$parameterName];
    }

    public static function getFunctionParameterByPosition(string $functionName, int $parameterPosition): ?\ReflectionParameter
    {
        $parameters = self::getFunctionParameters($functionName);

        if ($parameterPosition < 0 || $parameterPosition >= count($parameters)) {
            return null;
        }

        foreach ($parameters as $parameter) {
            if ($parameterPosition == $parameter->getPosition()) {
                return $parameter;
            }
        }

        return null;
    }

    public static function getClosureParameterByName(\Closure $closure, string $parameterName): ?\ReflectionParameter
    {
        $parameters = self::getClosureParameters($closure);

        return @$parameters[$parameterName];
    }

    public static function getClosureParameterByPosition(\Closure $closure, int $parameterPosition): ?\ReflectionParameter
    {
        $parameters = self::getClosureParameters($closure);

        if ($parameterPosition < 0 || $parameterPosition >= count($parameters)) {
            return null;
        }

        foreach ($parameters as $parameter) {
            if ($parameterPosition == $parameter->getPosition()) {
                return $parameter;
            }
        }

        return null;
    }

    // static variables cannot be cached because we are retrieving a mutable value
    public static function getFunctionStaticVariables(string $functionName): array
    {
        $vars = [];
        foreach(self::getFunction($functionName)->getStaticVariables() as $name => $value) {
            $vars[$name] = $value;
        }

        return $vars;
    }

    public static function getFunctionStaticVariableByName(string $functionName, string $varName)
    {
        $vars = self::getFunctionStaticVariables($functionName);

        return @$vars[$varName];
    }

    public static function getFunctionStaticVariableByPosition(string $functionName, int $varPosition)
    {
        $vars = self::getFunctionStaticVariables($functionName);

        if ($varPosition < 0 || $varPosition >= count($vars)) {
            return null;
        }

        return @\array_values($vars)[$varPosition];
    }

    public static function getClosureStaticVariables(\Closure $closure): array
    {
        $vars = [];
        foreach(self::getClosure($closure)->getStaticVariables() as $name => $value) {
            $vars[$name] = $value;
        }

        return $vars;
    }

    public static function getClosureStaticVariableByName(\Closure $closure, string $varName)
    {
        $vars = self::getClosureStaticVariables($closure);

        return @$vars[$varName];
    }

    public static function getClosureStaticVariableByPosition(\Closure $closure, int $varPosition)
    {
        $vars = self::getClosureStaticVariables($closure);

        if ($varPosition < 0 || $varPosition >= count($vars)) {
            return null;
        }

        return @\array_values($vars)[$varPosition];
    }
}