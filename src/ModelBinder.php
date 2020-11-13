<?php

namespace Phasten;

abstract class ModelBinder
{
    public static function bindConstructorParameters(string $className, array $args, array $context = [], ?APIException &$error = null): callable
    {
        $isKwargs = self::isAssoc($args);

        $boundParameters = [];
        foreach(Ref::getConstructorParameters($className) as $parameter) {

            try {

                $name = $parameter->getName();
                $type = $parameter->getType();
                $position = $parameter->getPosition();
                $hasType = $parameter->hasType();
                $isOptional = $parameter->isOptional();
                $allowsNull = $parameter->allowsNull();
                $isVariadic = $parameter->isVariadic();
                $hasDefaultValue = $parameter->isDefaultValueAvailable();
                $defaultValue = $hasDefaultValue ? $parameter->getDefaultValue() : null;
                $isBuiltIn = null !== $type ? $type->isBuiltIn() : true;
                $typeName = null !== $type ? $type->getName() : null;
                $isInterface = \interface_exists($typeName);

                $parameterExists = $isKwargs ? \array_key_exists($name, $args) : \array_key_exists($position, $args);
                $parameterValue = $isKwargs ? @$args[$name] : @$args[$position];
                $boundValue = $parameterExists ? $parameterValue : $defaultValue;

                // check context if parameter is not passed and can be resolved 
                if (false === $parameterExists && true === $hasType && false === $isBuiltIn && !empty($context)) {
                    if (null !== $parameterValue = @$context[$typeName]) {
                        $parameterExists = true;
                        $boundValue = $parameterValue;
                    }
                }

                if (true === $isInterface && false === \is_object($parameterValue)) {
                    if (!empty($context) && (null !== $concreteTypeName = @$context[$typeName])) {
                        $typeName = $concreteTypeName;
                    }
                    else {
                        throw new InvalidParameterException(new \Core\Parameter($name, $type, !$isOptional));
                    }
                }

                // assert can be bound
                if (false === $isOptional && false === $allowsNull && false === $parameterExists) {
                    throw new MissingParameterException(new \Core\Parameter($name, $type, !$isOptional));
                }

                // resolve parameter values
                if (false === $isBuiltIn) {
                    if ((false === $isOptional && false === $allowsNull) || \is_array($boundValue)) {

                        $bindingParameters = null === $boundValue ? [] : $boundValue;

                        $boundValue = ($boundValue instanceof $typeName) ? 
                            $boundValue : 
                            self::bindConstructorParameters($typeName, $bindingParameters, $context, $error)();
                        
                    }
                }

                $boundParameters[$position] = $boundValue;

            } catch (APIException $ex) {
                if (null === $error) {
                    $error = $ex;
                } else {
                    $error->bind($ex);
                }
            }
        }

        \ksort($boundParameters);

        $callable = function() use ($className, $boundParameters, &$error) {
            try {
                return new $className(...$boundParameters);
            } catch(\Throwable $ex) {
                if (null === $error) {
                    $error = $ex;
                } else {
                    $error->bind($ex);
                }
            }
        };

        return $callable;
    }

    public static function bindMethodParameters(string $className, string $methodName, array $args, array $context = [], ?APIException &$error = null): callable
    {
        $isKwargs = self::isAssoc($args);

        $boundParameters = [];
        foreach(Ref::getMethodParameters($className, $methodName) as $parameter) {

            try {

                $name = $parameter->getName();
                $type = $parameter->getType();
                $position = $parameter->getPosition();
                $hasType = $parameter->hasType();
                $isOptional = $parameter->isOptional();
                $allowsNull = $parameter->allowsNull();
                $isVariadic = $parameter->isVariadic();
                $hasDefaultValue = $parameter->isDefaultValueAvailable();
                $defaultValue = $hasDefaultValue ? $parameter->getDefaultValue() : null;
                $isBuiltIn = null !== $type ? $type->isBuiltIn() : true;
                $typeName = null !== $type ? $type->getName() : null;
                $isInterface = \interface_exists($typeName);

                $parameterExists = $isKwargs ? \array_key_exists($name, $args) : \array_key_exists($position, $args);
                $parameterValue = $isKwargs ? @$args[$name] : @$args[$position];
                $boundValue = $parameterExists ? $parameterValue : $defaultValue;

                // check context if parameter is not passed and can be resolved 
                if (false === $parameterExists && true === $hasType && false === $isBuiltIn && !empty($context)) {
                    if (null !== $parameterValue = @$context[$typeName]) {
                        $parameterExists = true;
                        $boundValue = $parameterValue;
                    }
                }

                if (true === $isInterface && false === \is_object($parameterValue)) {
                    if (!empty($context) && (null !== $concreteTypeName = @$context[$typeName])) {
                        $typeName = $concreteTypeName;
                    }
                    else {
                        throw new InvalidParameterException(new \Core\Parameter($name, $type, !$isOptional));
                    }
                }

                // assert can be bound
                if (false === $isOptional && false === $allowsNull && false === $parameterExists) {
                    throw new MissingParameterException(new \Core\Parameter($name, $type, !$isOptional));
                }

                // resolve parameter values
                if (false === $isBuiltIn) {
                    if ((false === $isOptional && false === $allowsNull) || \is_array($boundValue)) {

                        $bindingParameters = null === $boundValue ? [] : $boundValue;

                        $boundValue = ($boundValue instanceof $typeName) ? 
                            $boundValue : 
                            self::bindConstructorParameters($typeName, $bindingParameters, $context, $error)();
                        
                    }
                }

                $boundParameters[$position] = $boundValue;

            } catch (APIException $ex) {
                if (null === $error) {
                    $error = $ex;
                } else {
                    $error->bind($ex);
                }
            }
        }

        \ksort($boundParameters);

        $callable = function($instance = null) use ($className, $methodName, $boundParameters, &$error) {

            if(!empty($errors)) {
                return $instance;
            }

            if (null === $instance) {
                try {
                    return new $className(...$boundParameters);
                } catch(\Throwable $ex) {
                    if (null === $error) {
                        $error = $ex;
                    } else {
                        $error->bind($ex);
                    }
                    return $instance;
                }
            }

            try {
                return $instance->{$methodName}($boundParameters);
            } catch(\Throwable $ex) {
                if (null === $error) {
                    $error = $ex;
                } else {
                    $error->bind($ex);
                }
                return $instance;
            }
        };

        return $callable;
    }

    public static function bindFunctionParameters(string $functionName, array $args, array $context = [], ?APIException &$error = null): callable
    {
        $isKwargs = self::isAssoc($args);

        $boundParameters = [];
        foreach(Ref::getFunctionParameters($functionName) as $parameter) {

            try {

                $name = $parameter->getName();
                $type = $parameter->getType();
                $position = $parameter->getPosition();
                $hasType = $parameter->hasType();
                $isOptional = $parameter->isOptional();
                $allowsNull = $parameter->allowsNull();
                $isVariadic = $parameter->isVariadic();
                $hasDefaultValue = $parameter->isDefaultValueAvailable();
                $defaultValue = $hasDefaultValue ? $parameter->getDefaultValue() : null;
                $isBuiltIn = null !== $type ? $type->isBuiltIn() : true;
                $typeName = null !== $type ? $type->getName() : null;
                $isInterface = \interface_exists($typeName);

                $parameterExists = $isKwargs ? \array_key_exists($name, $args) : \array_key_exists($position, $args);
                $parameterValue = $isKwargs ? @$args[$name] : @$args[$position];
                $boundValue = $parameterExists ? $parameterValue : $defaultValue;

                // check context if parameter is not passed and can be resolved 
                if (false === $parameterExists && true === $hasType && false === $isBuiltIn && !empty($context)) {
                    if (null !== $parameterValue = @$context[$typeName]) {
                        $parameterExists = true;
                        $boundValue = $parameterValue;
                    }
                }

                if (true === $isInterface && false === \is_object($parameterValue)) {
                    if (!empty($context) && (null !== $concreteTypeName = @$context[$typeName])) {
                        $typeName = $concreteTypeName;
                    }
                    else {
                        throw new InvalidParameterException(new \Core\Parameter($name, $type, !$isOptional));
                    }
                }

                // assert can be bound
                if (false === $isOptional && false === $allowsNull && false === $parameterExists) {
                    throw new MissingParameterException(new \Core\Parameter($name, $type, !$isOptional));
                }

                // resolve parameter values
                if (false === $isBuiltIn) {
                    if ((false === $isOptional && false === $allowsNull) || \is_array($boundValue)) {

                        $bindingParameters = null === $boundValue ? [] : $boundValue;

                        $boundValue = ($boundValue instanceof $typeName) ? 
                            $boundValue : 
                            self::bindConstructorParameters($typeName, $bindingParameters, $context, $error)();
                        
                    }
                }

                $boundParameters[$position] = $boundValue;

            } catch (APIException $ex) {
                if (null === $error) {
                    $error = $ex;
                } else {
                    $error->bind($ex);
                }
            }
        }

        \ksort($boundParameters);

        $callable = function() use ($functionName, $boundParameters, &$error) {

            if(null !== $error) {
                return null;
            }

            try {
                return $functionName(...$boundParameters);
            } catch(\Throwable $ex) {
                if (null === $error) {
                    $error = $ex;
                } else {
                    $error->bind($ex);
                }
                return null;
            }
        };

        return $callable;
    }

    public static function bindClosureParameters(\Closure $closure, array $args, array $context = [], ?APIException &$error = null): callable
    {
        $isKwargs = self::isAssoc($args);

        $boundParameters = [];
        foreach(Ref::getClosureParameters($closure) as $parameter) {

            try {

                $name = $parameter->getName();
                $type = $parameter->getType();
                $position = $parameter->getPosition();
                $hasType = $parameter->hasType();
                $isOptional = $parameter->isOptional();
                $allowsNull = $parameter->allowsNull();
                $isVariadic = $parameter->isVariadic();
                $hasDefaultValue = $parameter->isDefaultValueAvailable();
                $defaultValue = $hasDefaultValue ? $parameter->getDefaultValue() : null;
                $isBuiltIn = null !== $type ? $type->isBuiltIn() : true;
                $typeName = null !== $type ? $type->getName() : null;
                $isInterface = \interface_exists($typeName);

                $parameterExists = $isKwargs ? \array_key_exists($name, $args) : \array_key_exists($position, $args);
                $parameterValue = $isKwargs ? @$args[$name] : @$args[$position];
                $boundValue = $parameterExists ? $parameterValue : $defaultValue;

                // check context if parameter is not passed and can be resolved 
                if (false === $parameterExists && true === $hasType && false === $isBuiltIn && !empty($context)) {
                    if (null !== $parameterValue = @$context[$typeName]) {
                        $parameterExists = true;
                        $boundValue = $parameterValue;
                    }
                }

                if (true === $isInterface && false === \is_object($parameterValue)) {
                    if (!empty($context) && (null !== $concreteTypeName = @$context[$typeName])) {
                        $typeName = $concreteTypeName;
                    }
                    else {
                        throw new InvalidParameterException(new \Core\Parameter($name, $type, !$isOptional));
                    }
                }

                // assert can be bound
                if (false === $isOptional && false === $allowsNull && false === $parameterExists) {
                    throw new MissingParameterException(new \Core\Parameter($name, $type, !$isOptional));
                }

                // resolve parameter values
                if (false === $isBuiltIn) {
                    if ((false === $isOptional && false === $allowsNull) || \is_array($boundValue)) {

                        $bindingParameters = null === $boundValue ? [] : $boundValue;

                        $boundValue = ($boundValue instanceof $typeName) ? 
                            $boundValue : 
                            self::bindConstructorParameters($typeName, $bindingParameters, $context, $error)();
                        
                    }
                }

                $boundParameters[$position] = $boundValue;

            } catch (APIException $ex) {
                if (null === $error) {
                    $error = $ex;
                } else {
                    $error->bind($ex);
                }
            }
        }

        \ksort($boundParameters);

        $callable = function() use ($closure, $boundParameters, &$error) {

            if(null !== $error) {
                return null;
            }

            try {
                return $closure(...$boundParameters);
            } catch (\Throwable $ex) {
                if (null === $error) {
                    $error = $ex;
                } else {
                    $error->bind($ex);
                }
                return null;
            }
        };

        return $callable;
    }

    public static function bindProperties(object $class, int $filter, array $args, array $context = [], ?APIException &$error = null): callable
    {
        $className = get_class($class);

        $boundParameters = [];
        foreach(Ref::getProperties($className, $filter) as [$property, $defaultValue]) {

            try {

                $name = $property->getName();
                $type = $property->getType();
                $hasType = $property->hasType();
                $allowsNull = null !== $type ? $type->allowsNull() : true;
                $isBuiltIn = null !== $type ? $type->isBuiltIn() : true;
                $typeName = null !== $type ? $type->getName() : null;
                $isInterface = \interface_exists($typeName);
                $isOptional = true;

                $parameterExists = \array_key_exists($name, $args);
                $parameterValue = @$args[$name];
                $boundValue = $parameterExists ? $parameterValue : $defaultValue;

                // check context if parameter is not passed and can be resolved 
                if (false === $parameterExists && true === $hasType && false === $isBuiltIn && !empty($context)) {
                    if (null !== $parameterValue = @$context[$typeName]) {
                        $parameterExists = true;
                        $boundValue = $parameterValue;
                    }
                }

                if (true === $isInterface && false === \is_object($parameterValue)) {
                    if (!empty($context) && (null !== $concreteTypeName = @$context[$typeName])) {
                        $typeName = $concreteTypeName;
                    }
                    else {
                        throw new InvalidParameterException(new \Core\Parameter($name, $type, !$isOptional));
                    }
                }

                // assert can be bound
                if (false === $isOptional && false === $allowsNull && false === $parameterExists) {
                    throw new MissingParameterException(new \Core\Parameter($name, $type, !$isOptional));
                }

                // resolve parameter values
                if (false === $isBuiltIn) {

                    if ((false === $isOptional && false === $allowsNull) || \is_array($boundValue)) {

                        $bindingParameters = null === $boundValue ? [] : $boundValue;

                        $boundValue = ($boundValue instanceof $typeName) ? 
                            $boundValue : 
                            self::bindConstructorParameters($typeName, $bindingParameters, $context, $error)();
                        
                    } else {
                        throw new InvalidParameterException(new \Core\Parameter($name, $typeName, !$isOptional));
                    }

                } else {
                    if (false === $allowsNull && gettype($boundValue) !== $typeName) {
                        throw new InvalidParameterException(new \Core\Parameter($name, $typeName, !$isOptional));
                    }
                }

                $boundParameters[$name] = $boundValue;

            } catch (APIException $ex) {
                if (null === $error) {
                    $error = $ex;
                } else {
                    $error->bind($ex);
                }
            }
        }

        \ksort($boundParameters);

        return function () use ($class, $className, $boundParameters, &$error) {
            foreach ($boundParameters as $name => $value) {

                try {
                    $property = Ref::getPropertyByName($className, $name);
                    $property->setAccessible(true);
                    $property->setValue($class, $value);
                } catch (\Throwable $ex) {
                    if (null === $error) {
                        $error = $ex;
                    } else {
                        $error->bind($ex);
                    }
                }
            }

            return $class;
        };
    }

    public static function isAssoc($array): bool
    {
        return is_array($array) && array_keys($array) !== range(0, count($array) - 1);
    }
}