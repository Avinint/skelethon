<?php

namespace Core\FieldType;

use Core\Field;

class TimeType extends DateType
{
    public const FORMAT = 'H:i:s';

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
                [$field->getAlias(), $field->getColumn(), $this->module, $this->model, $field->getFormattedName()], $template[4]);
    }

    public function getTemplateChampObligatoire($templatePath)
    {
        $template = file($templatePath, FILE_IGNORE_NEW_LINES);
        return [$template[0], $template[2].$template[5]];
    }
}