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
        if ($path->getName() === 'blocs') {
            return '';
        }

        return parent::generateRoutingFile($path);
    }

    /**
     * @param bool $usesRechercheNoCallback
     * @param string $templatePerActionPath
     * @return FilePath
     */
    protected function getJavaScriptMethodPerActionHook(bool $usesCallbackListeElement, FilePath $path) : FilePath
    {
        if ($usesCallbackListeElement) {
            $path = $this->getTrueTemplatePath($path->add('callbackListeElement'));
        }

        return $path;
    }
}