<?php

namespace Phasten;

interface AnnotationProviderInterface
{
    public function parse(string $string): array;
}