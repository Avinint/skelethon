<?php

namespace Core;

interface FileGeneratorInterface
{
    public function generate(FilePath $path) : string;
}