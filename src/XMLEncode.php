<?php

namespace Phasten;

class XMLEncode
{
    const HTMLSPECIALCHARS = ['htmlspecialchars', [\ENT_XML1, 'UTF-8', true]];

    const CDATA = [self::class.'::cdata', []];

    private array $func;

    public function __construct(array $func = self::HTMLSPECIALCHARS)
    {        
        $this->func = $func;
    }

    public function encode($value)
    {
        [$f, $args] = $this->func;  

        return @call_user_func_array($f, [$value, ...$args]);
    }

    public static function cdata($value): string
    {
        if (is_scalar($value) || is_null($value)) {
            return "<![CDATA[$value]]>";
        }

        return "";
    }
}