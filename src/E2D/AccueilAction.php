<?php

namespace E2D;

use Core\Action;
use Core\FilePath;

class AccueilAction extends Action
{
    protected string $name = 'accueil';


    /**
     * @param FilePath $path
     * @param string $action
     * @return string
     */
    public function generateRoutingFile(FilePath $path) : string
    {
        if ($path->getName() ===  'routing_accueil') {
            return '';
        }

        return parent::generateRoutingFile($path);
    }
}