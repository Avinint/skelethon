<?php


namespace Core;


class Path extends PathNode
{

    /**
     * Répertoire racine, pas de parent
     * @return $this|PathNode|null
     */
    public function getParent()
    {
        return null;
    }

    public function __construct($path, $name = '')
    {
        $this->path = rtrim($path, DS) . DS;
        $this->name = $name ?: $path;
        $this->root = $this;
    }

    public function __toString()
    {
        return $this->getFullPath();
    }

    public function isRoot()
    {
        return true;
    }

}