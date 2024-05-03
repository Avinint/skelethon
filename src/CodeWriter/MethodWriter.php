<?php

class MethodWriter
{
    private string $name;
    private string $access;
    private array $parametres;
    private string $returnType;
    private string $body = '';

    public function __construct($name, $access, $parametres, $returnType)
    {
        $this->name = $name;
        $this->access = $access;
        $this->parametres = $parametres;
        $this->returnType = $returnType;
    }

    public function addBody($body)
    {
        $this->body .= $body;
    }
}