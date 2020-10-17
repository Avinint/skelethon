<?php


namespace Core;


class FilePath extends PathNode
{

    private $extension;

    /**
     * Génère un nom de fichier dérivé du fichier actuel pour retrouver une variante suffixée du fichier actuel
     * @param $variation
     * @return $this|FilePath
     */
    public function getVariantFile($variation)
    {
        if (isset($this->children[$variation]) && $this->children[$variation] instanceof FilePath) {
            return $this->parent.$this->fileName.$this->children[$variation];
        }

        static::msg('fichier inexistant, utilisation du parent', 'error');
        return $this;
    }

    public function __construct($fileName, $extension = '')
    {
        if ($extension === '' && strpos($fileName, '.')) {
            [$this->name, $this->extension] = explode('.', $fileName, 2);
        } else {
           [$this->name, ] = [$fileName, $extension];
        }
        $this->path =  $this->name . '.' . $this->extension;
    }

    /**
     * @param string $fileName
     * @param string $extension
     * @return $this|PathNode
     */
    public function addFile(string $fileName, string $extension = '', $shared = false) : PathNode
    {
        return $this;
    }

    public function getType()
    {
        return strpos($this->extension, 'php') !== false ? 'php' :  $this->extension;
    }

    public function __toString()
    {
        return $this->getFullPath();
    }

    public function exists()
    {
        return file_exists($this->getPath());
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed|string $name
     */
    public function setName($name) : void
    {
        $this->name = $name;
        $this->path = $name . '.' . $this->extension;
    }

}