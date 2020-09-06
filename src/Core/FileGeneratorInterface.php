<?php

namespace Core;

interface FileGeneratorInterface
{
    public function generate(string $path) : string;
}