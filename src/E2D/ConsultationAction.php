<?php


namespace E2D;


use Core\Action;
use Core\FilePath;

class ConsultationAction extends Action
{
    protected string $name = 'consultation';

    /**
     * @param FilePath $path
     * @return string
     */
    public function generateRoutingFile(FilePath $path) : string
    {

        return parent::generateRoutingFile($path);
    }
}