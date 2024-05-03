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
        if ($this->getType() === 'php') {
            return $this->name . '.php';
        }
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

    public function getRightPath()
    {

        if ($this->root instanceof TemplatePath) {
            reset($this->root->templates);

            if (strpos($this->getFullPath(), 'standard/module')) {

                $currentPath  = $this->root->getChild(current($this->root->templates));
                $nextPath    = $this->root->getChild(reset($this->root->templates));

                foreach ($currentPath->getchildren() as $child) {
                    break;
                }

                $child->setParent($nextPath);
            }

            while (!file_exists($this->getFullPath()) && array_search(current($this->root->templates), $this->root->templates) < count($this->root->templates) - 1) {
                $currentPath = $this->root->getChild(current($this->root->templates));

                $nextPath    = $this->root->getChild(next($this->root->templates));
                if (current($this->root->templates) !== false) {

                    foreach ($currentPath->getchildren() as $child) {
                       break;
                    }
                    //$child = $currentPath->getFirstChild();
                    $child->setParent($nextPath);
                } else {

                  reset($this->root->templates);


                }

            }

            return $this;
       }

        return $this;
    }
}