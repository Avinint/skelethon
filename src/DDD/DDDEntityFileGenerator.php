<?php


namespace DDD;


use Core\FileGenerator;
use Core\FilePath;

class DDDEntityFileGenerator extends FileGenerator
{

    public function generate(FilePath $path) : string
    {
        $templatePath = $this->getTrueTemplatePath($path);
        if (isset($templatePath)) {
            $text = file_get_contents($templatePath);
        }

        $propertiesTemplatePath = $this->getTrueTemplatePath($path->add('propriete'));
        $initialisationTemplatePath = $this->getTrueTemplatePath($path->add('init'));


        return $text;
    }
}