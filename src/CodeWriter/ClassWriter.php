<?php

class ClassWriter
{
    private $name;
    private $namespace;
    private $parent;
    private $interfaces;

    private $imports = [];
    private $methods = [];

    const DATATYPES  = ['void', 'int', 'string', 'float', 'bool', 'array', 'object', 'class', 'callable'];

    /** Initialise une classe
     * @param string $name
     * @param string $namespace
     * @param string|null $parent
     * @param array $interfaces
     */
    public function __construct(string $name, string $namespace, string $parent = null, array $interfaces = [])
    {
        $this->name = $name;
        $this->namespace = $namespace;
        $this->parent = $parent;
        $this->inferfaces = $interfaces;
    }

    public function addImport(array $imports)
    {
        $this->imports += $imports ;
    }

    /**
     *  Ajoute une méthode à la classe
     * @param $methodName
     * @param string $access
     * @param array $parametres
     * @param string $return
     * @param array $classes
     */
    public function addMethod($methodName, $access = 'public', $parametres = [], $returnType = self::DATATYPES['void'])
    {
        $this->methods[$methodName] = new MethodWriter($methodName, $access, $parametres, $returnType);


        return $this->methods[$methodName];
    }
}