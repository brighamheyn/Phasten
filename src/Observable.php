<?php

namespace Phasten;

ini_set('display_errors', 'on');

require_once __DIR__ . '/Reflector.php';

const TEST = "TEST";

class Todos
{   
    public $items = [];

    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function addTodo(?string $name = null, $another): void
    {
        $this->items[] = $name. $another;
    }

    public function __get(string $property)
    {
        echo "getting -- ";

        return @$this->{$property};
    }
}

abstract class Observable
{
    const BEFORE_GET = 'before_get';

    const AFTER_GET = 'after_get';

    const BEFORE_SET = 'before_set';

    const AFTER_SET = 'after_set';

    const BEFORE_CALL = 'before_call';

    const AFTER_CALL = 'after_call';

    const BEFORE_CONSTRUCT = 'before_construct';

    const AFTER_CONSTRUCT = 'after_construct';

    public static function of(string $className, array $constructorArgs = [], callable $listener = null)
    {   
        $self = self::class;

        if (null === $listener) {
            $listener = function(string $action, string $member, array $args) {};
        }

        $methods = Reflector::getMethods($className);

        $code = "\treturn new class extends \\$className \n\t{";

        $code .= "\n\n\t\tprivate \$listener;";

        $code .= "\n\n\t\tprivate string \$definition;";

        $code .= "\n\n\t\tpublic function __construct() \n\t\t{";
        $code .= "\n\t\t}";

        foreach ($methods as $method) {

            $methodName = $method->getName();

            if (in_array($methodName, ["__construct", "__get", "__set", "__call"])) {
                continue;
            }
            
            $isStatic = $method->isStatic() ? "static" : "";

            $code .= "\n\n\t\tpublic $isStatic function $methodName(";

            $parameters = Reflector::getMethodParameters($className, $methodName);

            foreach ($parameters as $parameter) {

                $parameterName = $parameter->getName();
                $type = $parameter->hasType() ? $parameter->getType() : null;

                $typeSignature = "";
                if ($type) {
                    $typeName = $type->getName();
                    $allowsNull = $type->allowsNull();
                    $typeSignature = $allowsNull ? "?$typeName" : $typeName;
                }
                $defaultValue = "";
                if ($parameter->isDefaultValueAvailable()) {
                    $defaultValue = $parameter->isDefaultValueConstant() 
                        ? "= " . $parameter->getDefaultValueConstantName() 
                        : "= " . json_encode($parameter->getDefaultValue()); 
                }

                $code .= "$typeSignature \$$parameterName $defaultValue";

                $code .= ", ";
            }

            $returnType = $method->getReturnType();

            $doesReturn = true;
            $returnSignature = "";
            if ($returnType) {
                $typeName = $returnType->getName();
                $doesReturn = $typeName !== "void";
                $allowsNull = $returnType->allowsNull();
                $returnSignature = $allowsNull ? ": ?$typeName" : ": $typeName";
            }

            $code = substr($code, 0, strlen($code) - 2) . ")$returnSignature";

            $code .= "\n\t\t{";

            $code .= "\n\t\t\t(\$this->listener)(\\$self::BEFORE_CALL, '$methodName', func_get_args());";
            $code .= "\n\t\t\t\$returnValue = parent::$methodName(...func_get_args());";
            $code .= "\n\t\t\t(\$this->listener)(\\$self::AFTER_CALL, '$methodName', [\$returnValue]);";
            $code .= $doesReturn ? "\n\t\t\treturn \$returnValue;" : "";
            $code .= "\n\t\t}";
        }

        $code .= "\n\n\t\tpublic function __get(string \$property) \n\t\t{";
        $code .= "\n\t\t\t(\$this->listener)(\\$self::BEFORE_GET, \$property, []);";
        $code .= "\n\t\t\t\$propertyValue = method_exists(parent::class, '__get') ? parent::__get(\$property) : property_exists(\$this, \$property) ? @\$this->{\$property} : null;";
        $code .= "\n\t\t\t(\$this->listener)(\\$self::AFTER_GET, \$property, [\$propertyValue]);";
        $code .= "\n\t\t\treturn \$propertyValue;";
        $code .= "\n\t\t}";

        $code .= "\n\n\t\tpublic function __set(string \$property, \$value): void \n\t\t{";
        $code .= "\n\t\t\t\$propertyValue = method_exists(parent::class, '__get') ? parent::__get(\$property) : property_exists(\$this, \$property) ? @\$this->{\$property} : null;";
        $code .= "\n\t\t\t(\$this->listener)(\\$self::BEFORE_SET, \$property, [\$propertyValue]);";
        $code .= "\n\t\t\tif(method_exists(parent::class, '__set')) {"; 
        $code .= "\n\t\t\t\tparent::__set(\$property, \$value);";
        $code .= "\n\t\t\t} else {";
        $code .= "\n\t\t\t\t\$this->{\$property} = \$value;";
        $code .= "\n\t\t\t}";
        $code .= "\n\t\t\t\$propertyValue = method_exists(parent::class, '__get') ? parent::__get(\$property) : property_exists(\$this, \$property) ? @\$this->{\$property} : null;";
        $code .= "\n\t\t\t(\$this->listener)(\\$self::AFTER_SET, \$property, [\$propertyValue]);";
        $code .= "\n\t\t}";

        $code .= "\n\n\t\tpublic function __call(string \$method, \$args) \n\t\t{";
        $code .= "\n\t\t\t(\$this->listener)(\\$self::BEFORE_CALL, \$method, \$args);";
        $code .= "\n\t\t\t\$returnValue = method_exists(parent::class, '__call') ? parent::__call(\$method, \$args) : \$this->{\$method}(...\$args);";
        $code .= "\n\t\t\t(\$this->listener)(\\$self::AFTER_CALL, \$method, [\$returnValue]);";
        $code .= "\n\t\t}";

        $code .= "\n\t};";

        //echo $code; die;

        $observable = eval($code);

        \Closure::bind(function() use ($code, $listener, $constructorArgs) {
            $this->listener = $listener;
            $this->definition = $code;

            ($this->listener)(Observable::BEFORE_CONSTRUCT, '__constructor', $constructorArgs);
            @parent::__construct(...$constructorArgs);
            ($this->listener)(Observable::AFTER_CONSTRUCT, '__constructor', [$this]);
            
        }, $observable, $observable)();

        return $observable;
    }
}

class App
{
    public Todos $todos;

    public function __construct(Todos $todos)
    {
        $this->todos = $todos;
    }
}

header('content-type: text');

$obs = Observable::of(Todos::class, ["test"], function(string $action, string $member, array $args) {
    $args = json_encode($args);
    echo "$action::$member->($args)\n\n";
});

$app = new App($obs);

$obs->addTodo("a todo!", "!!!");

$obs->name = "My Todos";

//$n = $obs->name;

echo $n;

//echo json_encode($obs);