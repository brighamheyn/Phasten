<?php

namespace Phasten;

class XMLElement extends Node
{
    public ?string $name;

    public $value;

    private array $attributes = [];

    public function __construct(?string $name, $value = null, array $attributes = [])
    {
        $this->name = $name;
        $this->value = $value;
        
        foreach ($attributes as $attr) {
            $this->addAttribute($attr);
        }
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function addAttribute(XMLAttribute $attr): void
    {
        $this->attributes[$attr->name] = $attr;
    }

    public function __toString()
    {
        $xml = "";
        $xml .= "<{$this->name}";

        foreach ($this->getAttributes() as $attr) {
            $xml .= " " . (string)$attr;
        }

        //$xml .= " " . 'Seq="'. $this->getSeq() .'"';

        $xml .= ">";

        $value = null;
        if (is_callable($this->value)) {
            $value = ($this->value)();
        }else {
            $value = $this->value;
        }

        $value = is_null($value) || is_scalar($value) ? $value : null;

        $xml .= $value;

        foreach ($this->getChildren() as $child) {
            $xml .= (string)$child;
        }

        $xml .= "</{$this->name}>";

        return $xml;
    }
}