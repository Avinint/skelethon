<?php


namespace Core;


class FilePath extends PathNode
{
    private $extension;

    public function __construct($fileName, $extension = '')
    {
        $this->suffixes = [];
        if ($extension) {
            [$this->name, $this->extension] = [$fileName, $extension];
        } else {
            [$this->name, $this->extension] = explode('.', $fileName, 2);
        }
    }

    public function add(string $suffix)
    {
        return $this->parent->addFile($this->name.$suffix, $this->extension);
    }

    public function getPath()
    {
        return $this->name . '.' . $this->extension;
    }

    public function getType()
    {
        return strpos($this->extension, 'php') !== false ? 'php' :  $this->extension;
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
    }

    /**
     * @return mixed
     */
    public function getExtension()
    {
        return $this->extension;
    }

}