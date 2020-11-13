<?php

namespace Phasten;

class XMLAttribute extends Node
{
    public ?string $name;

    public $value;

    public function __construct(?string $name = null, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function __toString()
    {
        $name = $this->name;
        $value = is_null($this->value) || is_scalar($this->value) ? $this->value : json_encode($this->value);

        $escaped = htmlspecialchars($value, \ENT_XML1 | \ENT_COMPAT, 'UTF-8');

        return "{$name}=\"{$escaped}\"";
    }
}
