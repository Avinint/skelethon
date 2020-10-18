<?php


namespace Core;


class TemplateNode extends PathNode
{
    private array $templates;
    protected string $activeTemplate;

    public function __construct(array $templates, $name = 'templateNode')
    {
        $this->templates = $templates;
        $this->activeTemplate = array_contains($name, $this->templates) ? $name : reset($this->templates);
        parent::__construct($name, $name);
    }

    public function getPath()
    {
        return $this->activeTemplate . DS;
    }

    public function setFallback(string $fallback)
    {
        $this->activeTemplate = $fallback ?: 'standard';
    }

    /**
     * Ajoute un sous-répertoire dans le hierarchie
     *
     * @param string $childPath
     * @return PathNode
     */
    public function addChild(string $childPath, $name = '') : PathNode
    {
        $childNode = parent::addChild($childPath, $name);
        $childNode->setTemplateNode($this);

        return $childNode;
    }

}