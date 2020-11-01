<?php


namespace E2D;


use Core\Action;
use Core\FilePath;


class ExportAction extends Action
{
    protected string $name = 'export';

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