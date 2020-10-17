<?php

namespace Core;

class PathNode extends CommandLineToolShelf
{
    protected string $name;
    protected string $path;
    protected ?PathNode $twinPath;

    protected array $children;
    protected ?PathNode $parent = null;

    public function __construct($path, $name = '')
    {
        $this->path = trim($path, DS) . DS;
        $this->name = $name ?: $path;
    }

    public static function detach(PathNode $path)
    {
        $newPath = clone $path;
        $path->shareChildren($newPath);
        $newPath->parent = null;

        return $newPath;
    }

    public function __toString()
    {
        return $this->getFullPath();
    }

    public function shareChildren(PathNode $path)
    {
        $path->children &= $this->children;
    }

    public function share(PathNode $path)
    {
        $path->addChild($this, $this->name, true);
    }

    /**
     * Ajoute un sous-répertoire dans le hierarchie lors de la génération de la hierarchie
     *
     * @param string $childPath
     * @return PathNode
     */
    public function addChild(string $childPath, $name = '', $shared = false) : PathNode
    {
        if (isset($this->twinPath)) {
            $this->twinPath->addChild($childPath, $name);
        }
        $pathObject = new PathNode($childPath, $name);
        $this->children[$pathObject->name] = $pathObject;

        if (!$shared) {
            $pathObject->parent = $this;
        }

        return $pathObject;
    }

    /**
     * @param string $fileName
     * @param string $extension
     * @return $this
     */
    public function addFile(string $fileName, string $extension = '', $shared = false) : PathNode
    {
        $filePath = new FilePath($fileName, $extension);
        $this->children[$filePath->name] = $filePath;

        if (!$shared) {
            $filePath->parent = $this;
        }
        return $filePath;
    }

    /**
     * Ajoute un sous-répertoire précis lors de la recherche d'un fichier
     * @param string $pathname
     * @param string $errorMsg
     * @return $this
     */
    public function getChild(string $pathname, $errorMsg = '')
    {
        if (isset($this->children[$pathname])) {
            $this->children[$pathname]->setParent($this);
            return $this->children[$pathname];
        }

        static::msg($errorMsg ?: 'Répertoire inexistant, utilisation du parent', 'error');
        return $this;
    }

    public function getFile(string $pathname)
    {
        if (isset($this->children[$pathname]) && $this->children[$pathname] instanceof FilePath) {
            return $this->children[$pathname];
        }

        static::msg('fichier inexistant, utilisation du parent', 'error');
        return $this;
    }

    /**
     * Retrouve un sous répertoire voisin du répertoire en cours, s'il existe, pour substitution (template alternatif..)
     * @param string $pathname
     * @return $this
     */
    public function getSibling(string $pathname)
    {
        return $this->getParent()->getChild($pathname, "Chemin alternatif : '$pathname' inexistent");
    }

    /**
     * Retrouve le répertoire parent, sauf s'il est répertoire racine du projet
     * @return $this|PathNode|null
     */
    private function getParent()
    {
        if (!isset($this->parent)) {
            static::msg('Les chemins racines n\'ont pas de chemin alternatif', 'error');
            return $this;
        }
        return $this->parent;
    }

    public function exists()
    {
        return is_dir($this->getPath());
    }

    public function getFullPath()
    {
        return ($this->parent ?? '') .$this->path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param PathNode|null $twinPath
     */
    public function setTwinPath(?PathNode $twinPath) : void
    {
        $this->twinPath = $twinPath;
    }

    /**
     * @param PathNode|null $parent
     */
    public function setParent(?PathNode $parent) : void
    {
        $this->parent = $parent;
    }

}

/**
 *
 parent path ( projectDir) / templates /template/
 - ON definit projectdirPath                     __DIR__
 - ensuite templatePath                          __DIR__/   templates/
 - ensuite un path pour chaque template          __DIR__/   templates/  nom_template/module
 -un path pour le menu  pour chaque tempalate   __DIR__/   templates/  nom_template/menu (filepath)
 - ensuite un path pour chaque sousrepertoire    __DIR__/   templates/  nom_template/module/nom_sous_rep
 -ensuite un path pour chaque fichier principale __DIR__/   templates/  nom_template/module/nom_sous_rep/  fichier_principal(filepath)
 - ensuite un path pour chaque variante          __DIR__/   templates/  nom_template/module/nom_sous_rep/  fichier_principal(filepath)(variante)
 - ensuite un path pour chaque variante          __DIR__/   templates/  nom_template/module/nom_sous_rep/  fichier_principal(filepath)(variante)(sous variante)
 *
 *
 */
//
//getTrueTemplate(
//    string
//)
//    return templatePath./ (templatename) / module/subdir/file