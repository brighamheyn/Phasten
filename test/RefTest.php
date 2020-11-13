<?php

use PHPUnit\Framework\TestCase;

final class RefTest extends TestCase
{
    public function testCanGetClassFromString(): void
    {
        $this->assertInstanceOf(ReflectionClass::class, Ref::getClass(DateTime::class));
    }

    public function testCanGetCachedClassInstance(): void
    {
        $ref = Ref::getClass(DateTime::class);

        $fromCache = Ref::getClass(DateTime::class);

        $this->assertSame($ref, $fromCache);
    }

    public function testCannotGetClassFromNonClassString(): void
    {
        $this->expectException(ReflectionException::class);

        Ref::getClass("NotAnObject");
    }

    public function testCanGetContructor(): void
    {
        $this->assertInstanceOf(ReflectionMethod::class, Ref::getConstructor(DateTime::class));
    }

    public function testCannotGetConstructorFromNonClassString(): void
    {
        $this->expectException(ReflectionException::class);

        Ref::getConstructor("NotAnObject");
    }

    public function testEmptyConstructorIsNull() : void
    {
        $test = new class {};

        $this->assertNull(Ref::getConstructor(get_class($test)));
    }

    public function testCanCachedGetConstructorInstance(): void
    {
        $ctor = Ref::getConstructor(DateTime::class);

        $fromCache = Ref::getConstructor(DateTime::class);

        $this->assertSame($ctor, $fromCache);
    }

    public function testCanGetMethod(): void
    {
        $test = new class
        {
            public function foo()
            {
                
            }
        };

        $this->assertInstanceOf(ReflectionMethod::class, Ref::getMethod(get_class($test), 'foo'));
    }

    public function testCannotGetMethodFromNonClassString(): void
    {
        $this->expectException(ReflectionException::class);

        Ref::getMethod('NonAnObject', 'methodName');
    }

    public function testCannotGetMethodFromNonExistantMethodString(): void
    {
        $this->expectException(ReflectionException::class);

        $test = new class
        {
            public function foo()
            {
                
            }
        };

        Ref::getMethod(get_class($test), 'bar');
    }

    public function testCanGetFunction(): void
    {
        $this->assertInstanceOf(ReflectionFunction::class, Ref::getFunction('substr'));
    }

    public function testCannotGetFunctionFromNonFunctionString(): void
    {
        $this->expectException(ReflectionException::class);

        Ref::getFunction('not_a_function');
    }

    public function testCanGetCachedFunction(): void
    {
        $func = Ref::getFunction('substr');

        $fromCache = Ref::getFunction('substr');

        $this->assertSame($func, $fromCache);
    }

    public function testCanGetClosure(): void
    {
        $func = function() {};

        $this->assertInstanceOf(ReflectionFunction::class, Ref::getClosure($func));
    }

    public function testCanGetCachedClosure(): void
    {
        $func = function() {};

        $test = Ref::getClosure($func);
        $fromCache = Ref::getClosure($func);

        $this->assertSame($test, $fromCache);
    }

    public function testCanGetConstructorParameters(): void
    {
        $parameters = Ref::getConstructorParameters(self::provideTestClass());

        foreach ($parameters as $name => $param) {
            $this->assertEquals($name, $param->getName());
            $this->assertInstanceOf(ReflectionParameter::class, $param);
        }
    }

    public function testCanGetCachedConstructorParameters(): void
    {
        $parameters = Ref::getConstructorParameters(self::provideTestClass());
        $cached = Ref::getConstructorParameters(self::provideTestClass());

        foreach ($parameters as $name => $param) {
            $this->assertSame($param, $cached[$name]);
        }
    }

    public function testCanGetConstructorParameterByName(): void
    {
        $param = Ref::getConstructorParameterByName(self::provideTestClass(), 'one');

        $this->assertEquals('one', $param->getName());
    }

    public function testCanGetConstructorParameterByPosition(): void
    {
        $param = Ref::getConstructorParameterByPosition(self::provideTestClass(), 0);

        $this->assertEquals(0, $param->getPosition());
    }

    public function testCanGetMethodParameters(): void
    {
        $parameters = Ref::getMethodParameters(...self::provideTestMethod());

        foreach ($parameters as $name => $param) {
            $this->assertEquals($name, $param->getName());
            $this->assertInstanceOf(ReflectionParameter::class, $param);
        }
    }

    public function testCanGetCachedMethodParameters(): void
    {
        $parameters = Ref::getMethodParameters(...self::provideTestMethod());
        $cached = Ref::getMethodParameters(...self::provideTestMethod());

        foreach ($parameters as $name => $param) {
            $this->assertSame($param, $cached[$name]);
        }
    }

    public function testCanGetMethodParameterByName(): void
    {
        $method = self::provideTestMethod();

        $param = Ref::getMethodParameterByName($method[0], $method[1], 'one');

        $this->assertEquals('one', $param->getName());
    }

    public function testCanGetMethodParameterByPosition(): void
    {
        $method = self::provideTestMethod();

        $param = Ref::getMethodParameterByPosition($method[0], $method[1], 0);

        $this->assertEquals(0, $param->getPosition());
    }

    public function testCanGetFunctionParameters(): void
    {
        $parameters = Ref::getFunctionParameters('substr');

        foreach ($parameters as $name => $param) {
            $this->assertEquals($name, $param->getName());
            $this->assertInstanceOf(ReflectionParameter::class, $param);
        }
    }

    public function testCanGetCachedFunctionParameters(): void
    {
        $parameters = Ref::getFunctionParameters('substr');
        $cached = Ref::getFunctionParameters('substr');

        foreach ($parameters as $name => $param) {
            $this->assertSame($param, $cached[$name]);
        }
    }

    public function testCanGetFunctionParameterByName(): void
    {
        $param = Ref::getFunctionParameterByName('substr', 'str');

        $this->assertEquals('str', $param->getName());
    }

    public function testCanGetFunctionParameterByPosition(): void
    {
        $param = Ref::getFunctionParameterByPosition('substr', 0);

        $this->assertEquals(0, $param->getPosition());
    }

    public function testCanGetFunctionStaticVariables(): void
    {
        function test()
        {
            static $one;
            static $two;
        };

        $vars = Ref::getFunctionStaticVariables('test');

        $this->assertEquals($vars, ['one' => null, 'two' => null]);
    }

    public function testCanGetFunctionStaticVariableByName(): void
    {
        function test2()
        {
            static $one = 1;
            static $two = 2;
        };

        $value = Ref::getFunctionStaticVariableByName('test2', 'one');

        $this->assertEquals($value, 1);
    }

    public function testCanGetFunctionStaticVariableByPosition(): void
    {
        function test3()
        {
            static $one = 1;
            static $two = 2;
        };

        $value = Ref::getFunctionStaticVariableByPosition('test3', 0);

        $this->assertEquals($value, 1);
    }

    public function testCanGetClosureParameters(): void
    {
        $parameters = Ref::getClosureParameters(function($one, $two){});

        foreach ($parameters as $name => $param) {
            $this->assertEquals($name, $param->getName());
            $this->assertInstanceOf(ReflectionParameter::class, $param);
        }
    }

    public function testCanGetCachedClosureParameters(): void
    {
        $func = function($one, $two){};

        $parameters = Ref::getClosureParameters($func);
        $cached = Ref::getClosureParameters($func);

        foreach ($parameters as $name => $param) {
            $this->assertSame($param, $cached[$name]);
        }
    }

    public function testCanGetClosureParameterByName(): void
    {
        $func = function($one, $two){};

        $param = Ref::getClosureParameterByName($func, 'one');

        $this->assertEquals('one', $param->getName());
    }

    public function testCanGetClosureParameterByPosition(): void
    {
        $func = function($one, $two){};

        $param = Ref::getClosureParameterByPosition($func, 0);

        $this->assertEquals(0, $param->getPosition());
    }

    public function testCanGetClosureStaticVariables(): void
    {
        $func = function()
        {
            static $one;
            static $two;
        };

        $vars = Ref::getClosureStaticVariables($func);

        $this->assertEquals($vars, ['one' => null, 'two' => null]);
    }
    
    public function testCanGetClosureStaticVariableByName(): void
    {
        $func = function()
        {
            static $one = 1;
            static $two = 2;
        };

        $value = Ref::getClosureStaticVariableByName($func, 'one');

        $this->assertEquals($value, 1);
    }

    public function testCanGetClosureStaticVariableByPosition(): void
    {
        $func = function()
        {
            static $one = 1;
            static $two = 2;
        };

        $value = Ref::getClosureStaticVariableByPosition($func, 0);

        $this->assertEquals($value, 1);
    }

    public function testCanGetClosureStaticVariablesFromUse(): void
    {   
        $var1 = 'one';
        $var2 = 'two';

        $func = function() use ($var1, $var2) {};

        $vars = Ref::getClosureStaticVariables($func);

        $this->assertEquals($vars, ['var1' => 'one', 'var2' => 'two']);
    }

    public function testCanGetClosureStaticVariablesFromUseAndStatic(): void
    {
        $var1 = 'one';
        $var2 = 'two';

        $func = function() use ($var1, $var2) { 
            static $var3 = 'three'; 
            static $var4 = 'four';
        };

        $vars = Ref::getClosureStaticVariables($func);

        $this->assertEquals($vars, ['var1' => 'one', 'var2' => 'two', 'var3' => 'three', 'var4' => 'four']);
    }

    public function testCanGetClosureStaticVariablesFromUseOverridingStatic(): void
    {
        $var1 = 'one';
        $var2 = 'two';

        $func = function() use ($var1, $var2) { 
            static $var1 = 'three'; 
            static $var4 = 'four';
        };

        $vars = Ref::getClosureStaticVariables($func);

        $this->assertEquals($vars, ['var1' => 'one', 'var2' => 'two', 'var4' => 'four']);
    }

    private static function provideTestClass(): string
    {
        $test = new class(1, 2, 3)
        {
            public function __construct(int $one, int $two, int $three) {}

            public function foo(int $one, int $two, int $three) {}
        };

        return get_class($test);
    }

    private static function provideTestMethod(): array
    {
        return [self::provideTestClass(), 'foo'];
    }
}