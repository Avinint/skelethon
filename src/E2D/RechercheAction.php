<?php


namespace E2D;

use Core\Action;
use Core\FilePath;

class RechercheAction extends Action
{
    protected string $name = 'recherche';

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

    /**
     * @param bool $usesRechercheNoCallback
     * @param string $templatePerActionPath
     * @return FilePath
     */
    protected function getJavaScriptMethodPerActionHook(bool $usesRechercheNoCallback, FilePath $path) : FilePath
    {
        if ($usesRechercheNoCallback) {
            $path = $this->getTrueTemplatePath($path->add('nocallback'));
        }

        return $path;
    }
}