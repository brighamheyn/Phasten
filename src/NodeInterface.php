<?php

namespace Phasten;

interface NodeInterface
{
    public function hasParent(): bool;

    public function getParent(): ?self;

    public function hasChildren(): bool;

    public function getChildren(): array;

    public function getAncestors(): array;

    public function getRoot(): self;

    public function getSiblings(): array;

    public function getIndex(): int;

    public function getSeq(): int;

    public function getDepth(): int;

    public function count(): int;

    public function addChild(self $child): void;
}