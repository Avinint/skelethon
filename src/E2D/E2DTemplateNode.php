<?php

namespace E2D;

use Core\TemplateNode;

class E2DTemplateNode extends TemplateNode
{
   public function getPath()
    {
        return $this->activeTemplate . DS . 'module' . DS;
    }

}