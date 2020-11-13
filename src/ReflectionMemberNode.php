<?php

namespace Phasten;

class ReflectionMemberNode extends Node
{
    public ReflectionMember $member;

    public function __construct(ReflectionMember $member)
    {
        $this->member = $member;
    }

    public function getKey(): array
    {
        return array_merge(array_reverse($this->mapAncestors(fn($item) => $item->member->getName(), [])), [$this->member->getName()]);
    }

    public function getPath(): string
    {
        return join("/", array_merge([""], $this->getKey()));
    }

    public function __toString()
    {
        $out =  str_repeat(" ", $this->getDepth());

        $out .= "{$this->member->getName()}";

        $out .= " = ";

        $out .= is_scalar($this->member->getValue()) || is_null($this->member->getValue()) ? (string)$this->member->getValue() : "";

        $out .= "\n";

        foreach ($this->getChildren() as $child) {
            $out .= (string)$child;
        }

        return $out;
    }
}