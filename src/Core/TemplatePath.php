<?php


namespace Core;


class TemplatePath extends Path
{
    public $templates = [];

    public function __construct($path, $templates = ['standard'], $name = 'templatePath')
    {
        parent::__construct($path, $name);
        $this->templates = $templates;
        foreach ($templates as $template) {
            $this->addChild($template);
        }

    }

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
        $childNode->root = $this;

        $childNode->parent = $this;
        if (isset($this->templateNode)) {
            $childNode->setTemplateNode($this->templateNode);
        }

        return $childNode;
    }

    public function isRoot()
    {
        return true;
    }


}