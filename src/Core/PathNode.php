<?php

namespace Core;

class PathNode extends CommandLineToolShelf
{
    protected string $name;
    protected string $path;
    protected ?TemplateNode $templateNode = null;

    protected array $children;
    protected ?PathNode $parent = null;

    public function __construct($path, $name = '')
    {
        $this->path = trim($path, DS) . DS;
        $this->name = $name ?: $path;
    }

//    public static function detach(PathNode $path)
//    {
//        $newPath = clone $path;
//        $path->shareChildren($newPath);
//        $newPath->parent = null;
//
//        return $newPath;
//    }

    public function setFallbackTemplate(string $template)
    {
        if (isset($this->templateNode)) {
            $this->templateNode->setFallback($template);
        }

        return $this;
    }

    public function __toString()
    {
        return $this->getFullPath();
    }

//    public function shareChildren(PathNode $path)
//    {
//        $path->children &= $this->children;
//    }
//
//    public function share(PathNode $path)
//    {
//        $path->addChild($this, $this->name, true);
//    }

    /**
     * joute un sous-répertoire dans le hierarchie lors de la génération de la hierarchie
     * @param string $childPath
     * @param string $name
     * @return PathNode
     */
    public function addChild(string $childPath, $name = '') : PathNode
    {
        $childNode = new PathNode($childPath, $name);
        $this->children[$childNode->name] = $childNode;

        $childNode->parent = $this;
        if (isset($this->templateNode)) {
            $childNode->setTemplateNode($this->templateNode);
        }

        return $childNode;
    }

    /**
     * @param string $fileName
     * @param string $extension
     * @return $this
     */
    public function addFile(string $fileName, string $extension = '') : FilePath
    {
        $filePath = new FilePath($fileName, $extension);
        $this->children[$filePath->name] = $filePath;

        $filePath->setParent($this);
        if (isset($this->templateNode)) {
            $filePath->setTemplateNode($this->templateNode);
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

        $this->msg($errorMsg ?: 'Répertoire inexistant, utilisation du parent', 'error');
        return $this;
    }

    public function getFile(string $pathname) : FilePath
    {
        if (isset($this->children[$pathname]) && $this->children[$pathname] instanceof FilePath) {
            return $this->children[$pathname];
        } elseif (strpos($pathname, '.')) {
            return $this->addFile($pathname);
        }

        $this->msg('fichier inexistant, utilisation du parent', 'error');
        return $this;
    }

//    /**
//     * Retrouve un sous répertoire voisin du répertoire en cours, s'il existe, pour substitution (template alternatif..)
//     * @param string $pathname
//     * @return $this
//     */
//    public function getSibling(string $pathname)
//    {
//        return $this->getParent()->getChild($pathname, "Chemin alternatif : '$pathname' inexistent");
//    }

    /**
     * Retrouve le répertoire parent, sauf s'il est répertoire racine du projet
     * @return $this|PathNode|null
     */
    public function getParent()
    {
        if (!isset($this->parent)) {
            $this->msg('Les chemins racines n\'ont pas de chemin alternatif', 'error');
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
        return ($this->parent ?? '') .$this->getPath();
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
     * @param PathNode|null $parent
     */
    public function setParent(?PathNode $parent) : void
    {
        $this->parent = $parent;
    }

    /**
     * Permet l'accès à la template node depuis les répertoire
     * descendants pour changer de template
     * @param TemplateNode $templateNode
     */
    public function setTemplateNode(TemplateNode $templateNode)
    {
        $this->templateNode = $templateNode;
    }

    /**
     * Permet d'ajouter un répertoire template dans une hierarchie
     * qui n'en compte pas
     * @param TemplateNode $templateNode
     */
    public function addChildTemplateNode(TemplateNode $templateNode)
    {
        if (is_null($this->templateNode) && empty($this->children)) {
            $this->children[ $templateNode->name] =  $templateNode;
            $templateNode->parent = $this;

            return $templateNode;
        }

        return $this;
    }
}