<?php


namespace E2D;


use Core\Action;
use Core\FilePath;

class SuppressionAction extends Action
{
    protected string $name = 'suppression';

    /**
     * @param FilePath $path
     * @return string
     */
    public function generateRoutingFile(FilePath $path) : string
    {
        if (strpos($path, 'blocs')) {
            return '';
        }

        return parent::generateRoutingFile($path);
    }
}