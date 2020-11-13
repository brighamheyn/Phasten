<?php

namespace Phasten;

class DefaultAnnotationProvider implements AnnotationProviderInterface
{
    private static string $namespace;

    public static function init(string $namespace): void
    {
        self::$namespace = $namespace;
    }

    public function parse(string $string): array
    {
        $attributes = [];
        
        foreach ($this->sequence($string) as $line) {
            $attribute = $this->provide($line);

            if (isset($attribute)) {
                $attributes[get_class($attribute)] = $attribute;
            }
        }

        return $attributes;
    }

    private function sequence(string $string) : array
    {
        $pattern = '/(@[a-zA-Z]+\b\s*\(.*\)\s*\n)|(@[a-zA-Z]+\b\s*\n)/i';
        
        preg_match_all($pattern, $string, $matches);

        return $matches[0];
    }

    private function provide(string $string)
    {
        $pattern = '/(@[a-zA-Z]+\b)/i';
        preg_match($pattern, $string, $name);

        $class = str_replace('@','',$name[0]);

        $pattern = '/@[a-zA-Z]+\b\s*\((\s*.*\s*)\)\s*\n/i';
        preg_match($pattern, $string, $args);

        $args = isset($args[1]) ? $args[1] : 'NULL';

        $namespace = self::$namespace;

        $class = "{$namespace}\\$class";

        $attribute =  eval("return new $class($args);");

        if(!$attribute) {
            throw new \Exception("Attribute construction exception thrown for $class");
        }

        return $attribute;
    }
}