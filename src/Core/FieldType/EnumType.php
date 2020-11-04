<?php

namespace Core\FieldType;

use Core\Field;
use Core\FilePath;

class EnumType extends FieldType
{
    public function getEditionView(FilePath $path)
    {
        $suffix = $this->app->usesSelect2 ? 'enum_select2' : 'enum';
        $templatePath = $this->app->getTrueTemplatePath($path->add($suffix));

        return file_get_contents($templatePath);
    }

    /**
     * Ajoute les lignes d champs formatés dans les selects pour récupérer des entités
     * @param string $indent
     * @param Field $field
     * @param array $template
     * @return string
     */
    public function addSelectFieldFormattedLines(string $indent, Field $field, array $template) : string
    {
        return $indent . str_replace(['ALIAS', 'COLUMN', 'mODULE', 'MODEL', 'NAME'],
                [$field->getAlias(), $field->getColumn(), $this->module, $this->model ,$field->getFormattedName()], $template[7]);
    }

    public function getRequiredFieldTemplate($templatePath)
    {
        $template = file($templatePath, FILE_IGNORE_NEW_LINES);
        return [$template[0], $template[1]];
    }
}