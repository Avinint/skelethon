<?php

namespace E2D;

use Core\PathNode;
use Core\TemplateNode;

class E2DTemplateNode extends TemplateNode
{
//   public function getPath()
//    {
//        return $this->activeTemplate . DS . 'module' . DS;
//    }

    /**
     * Ajoute un sous-répertoire dans le hierarchie
     *
     * @param string $childPath
     * @return PathNode
     */
    public function addChild(string $childPath, $name = '') : PathNode
    {
        $childNode = parent::addChild('module'. DS . $childPath, $name);
        $childNode->setTemplateNode($this);

        return $childNode;
    }

}