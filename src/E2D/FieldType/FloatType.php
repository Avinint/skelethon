<?php

namespace E2D\FieldType;

use Core\Field;
use Core\FilePath;

class FloatType extends NumberType
{
    protected $templateIndex = 12;

    /**
     * @param string $indent
     * @param Field $field
     * @param array $template
     * @return string
     */
    public function addSelectFieldFormattedLines(string $indent, Field $field, array $template) : string
    {
        return $indent . str_replace(['ALIAS', 'COLUMN', 'mODULE', 'MODEL', 'NAME'],
                [$field->getAlias(), $field->getColumn(), $this->module, $this->model ,$field->getFormattedName()], $template[5]);
    }

    public function getTemplateChampObligatoire($templatePath)
    {
        $template = file($templatePath, FILE_IGNORE_NEW_LINES);
        return [$template[0], $template[6]];
    }

    //////// LEGACY !!!!!!!!!!!!!!!!!!!!!!!

    /**
     * @param FilePath $templatePath
     * @return array
     */
    public function getTemplateChampObligatoireLegacy(FilePath $templatePath)
    {
        $template = file($templatePath, FILE_IGNORE_NEW_LINES);
        return [$template[1]];
    }

    /**
     * @param FilePath $templatePath
     * @return array
     */
    public function getTemplateChampNullableLegacy(FilePath $templatePath)
    {
        $template = file($templatePath, FILE_IGNORE_NEW_LINES);
        return $template[5];
    }

    public function getClasseMapping() : string
    {
        return "Double";
    }

}