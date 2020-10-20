<?php


namespace Core;


abstract class FileGenerator extends CommandLineToolShelf implements FileGeneratorInterface
{
    public abstract function generate(FilePath $path) : string;

    /**
     * Remplace le chemin du template choisi par le chemin du template standard ou le template de fallback  s'il n'y a pas de template personnalisé
     *
     * @param FilePath $templatePath
     * @param string $fileSuffix
     * @return string|string[]
     */
    public function getTrueTemplatePath(FilePath $templatePath)
    {
        return $this->app->getFileManager()->getTrueTemplatePath($templatePath);
    }
}