<?php


namespace Core;


abstract class FileGenerator extends CommandLineToolShelf implements FileGeneratorInterface
{
    public abstract function generate(string $path) : string;

    /**
     * Remplace le chemin du template choisi par le chemin du template standard ou le template de fallback  s'il n'y a pas de template personnalisé
     *
     * @param $templatePath
     * @return string|string[]
     */
    public function getTrueTemplatePath($templatePath, $replace = '', $search = '.')
    {
        return $this->app->getFileManager()->getTrueTemplatePath($templatePath, $replace, $search);
    }
}