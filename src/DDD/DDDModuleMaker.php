<?php

namespace DDD;

use Core\FilePath;

use E2D\E2DConfigFileGenerator;
use E2D\E2DControllerFileGenerator;
use E2D\E2DJSFileGenerator;
use E2D\E2DModelFileGenerator;
use E2D\E2DViewFileGenerator;

class DDDModuleMaker extends E2DModuleMaker
{

    /**
     * Crée fichier s'il n'existe pas
     *
     * @param FilePath $path
     * @return bool|string
     */
    function ensureFileExists(FilePath $path, FilePath $templatePath)
    {
        $path = $this->getTrueFilePath($path);
        if ($this->getTrueTemplatePath($templatePath) === null) {
            $templatePath = str_replace('controllers', 'application', $templatePath);
        }

        if (! $this->model->hasAction('consultation') &&  strpos($templatePath, 'consultation_TABLE') !== false) {
            return ['Pas de vue créé pour la consultation', 'important'];
        }

        if ($this->fileShouldNotBeCreated($path)) {
            return ['Le fichier ' . $this->highlight($path, 'info') . ' existe déja', 'warning'];
        }

        $text = $this->generateFileContent($templatePath, $path);

        return $this->saveFile($path, $text);
    }

    protected function generatePHPClass($templatePath)
    {
        if (strpos($templatePath, 'Action.class.php')) {
            $text = $this->controllerFileGenerator->generate($templatePath);
        } elseif (strpos($templatePath, 'HTML.class.php')) {
            $text = $this->controllerFileGenerator->generateHTMLController($templatePath);
        } elseif (strpos($templatePath, 'MODEL.class')) {
            $text = $this->entityFileGenerator->generate($templatePath);
        }

        return $text;
    }

    protected function initializeFileGenerators()
    {
        $this->modelFileGenerator      = new E2DModelFileGenerator($this->app);
        $this->controllerFileGenerator = new E2DControllerFileGenerator($this->app);
        $this->jsFileGenerator         = new E2DJSFileGenerator($this->app);
        $this->configFileGenerator     = new E2DConfigFileGenerator($this->app);
        $this->viewFileGenerator       = new E2DViewFileGenerator($this->app);
        $this->entityFileGenerator     = new DDDEntityFileGenerator($this->app);
    }
}