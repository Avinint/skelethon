<?php

namespace Core\FieldType;

use Core\Field;

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

//    public function addToExceptions($field, &$exceptions)
//    {
//        $exceptions['aFloats'][] = $field->getName();
//    }
}