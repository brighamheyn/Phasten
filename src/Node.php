<?php

namespace Phasten;

class Node implements NodeInterface
{
    private ?self $parent = null;

    private array $children = [];

    public function hasParent(): bool
    {
        return null !== $this->parent;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function hasChildren(): bool
    {
        return [] !== $this->getChildren();
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this->parent;
        while(null !== $current) {
            $ancestors[] = $current;
            $current = $current->parent;
        }

        return $ancestors;
    }

    public function getRoot(): self
    {
        while(null !== $parent = $this->parent) {
            $parent = $parent->parent;
        }

        return $parent ?? $this;
    }

    public function getSiblings(): array
    {
        if (null === $this->parent) {
            return [];
        }

        return array_filter($this->parent->getChildren(), function ($child) {
            return $child !== $this;
        });
    }

    public function getIndex(): int
    {
        if (!$this->hasParent()) {
            return 0;
        }

        foreach ($this->parent->getChildren() as $index => $child) {
            if ($child === $this) {
                return $index;
            } 
        }
        
        throw new \LogicException(self::class." must have valid index.");
    }

    public function getSeq(): int
    {
        if (!$this->hasParent()) {
            return 0;
        }

        $count = $this->getParent()->getSeq() + 1;
        foreach ($this->getParent()->getChildren() as $index => $child) {
            if ($child === $this) {
                return $count;
            } else {
                $count += $child->count() + 1;
            }
        }

        return $this->getIndex() + $count + 1;
    }

    public function getDepth(): int
    {
        return count($this->getAncestors());
    }

    public function addChild(NodeInterface $child): void
    {
        $child->parent = $this;
        $this->children[] = $child;
    }

    public function mapAncestors(callable $f): array
    {
        return array_map($f, $this->getAncestors());
    }

    public function reduceAncestors(callable $f, $initial = null): array
    {
        return array_reduce($this->getAncestors(), $f, $initial);
    }

    public function mapChildren(callable $f): array
    {
        return array_map($f, $this->getChildren());
    }

    public function reduceChildren(callable $f, $initial = null)
    {
        return array_reduce($this->getChildren(), $f, $initial);
    }

    public function count(): int
    {
        return $this->reduceChildren(function (int $carry, NodeInterface $child) {
            return $carry + $child->count() + 1;
        }, 0);
    }
}