<?php


namespace Core;


class FilePath extends PathNode
{
    private $extension;
    private $baseFile;

    public function __construct($fileName, $extension = '')
    {
        $this->suffixes = [];
        if ($extension) {
            [$this->name, $this->extension] = [$fileName, $extension];
        } else {
            [$this->name, $this->extension] = explode('.', $fileName, 2);
        }
    }

    /**
     *  Ajoute un fichier dont le nom = nom du fichier de courant + un suffixe et le retourne
     * @param string $suffix
     * @return PathNode|null
     */
    public function add(string $suffix)
    {

        $file = $this->parent->addFile($this->name.'_'.$suffix, $this->extension);
        $file->setBaseFile($this);
        return $file;
    }

    /**
     * Retourne un fichier dont le nom est dérivé du fichier courant
     * @param string $suffix
     * @return FilePath|PathNode|null
     */
    public function get(string $suffix)
    {
        return $this->parent->getFile($this->name.'_'.$suffix, $this->extension);
    }

    public function getPath()
    {
        return $this->name . '.' . $this->extension;
    }

    public function getType()
    {
        return strpos($this->extension, 'php') !== false ? 'php' :  $this->extension;
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

    /**
     * @return FilePath
     */
    public function getBaseFile()
    {
        return $this->baseFile;
    }

    /**
     * @param FilePath $baseFile
     */
    public function setBaseFile(FilePath $baseFile) : void
    {
        $this->baseFile = $baseFile;
    }

    public function exists()
    {
        return file_exists($this->getFullPath());
    }
}