<?php


namespace Core\FieldType;


use Core\App;
use Core\FilePath;

class TextType extends FieldType
{
    public function getEditionView(FilePath $path, App $app = null)
    {
        return file_get_contents($this->app->getTrueTemplatePath($path->add('text')));
    }
}