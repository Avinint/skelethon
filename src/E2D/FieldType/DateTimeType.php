<?php

namespace E2D\FieldType;

use Core\Field;

class DateTimeType extends DateType
{
    public const SUFFIXE_DEBUT = ' 00:00:00';
    public const SUFFIXE_FIN = ' 23:59:59';
    public const FORMAT = 'Y-m-d H:i:s';

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
                [$field->getAlias(), $field->getColumn(), $this->module, $this->model ,$field->getFormattedName()], $template[3]);
    }

    protected function getCritereDeRechercheDateTemplate(string $indent, array $template)
    {
        return  $indent . implode('', array_map(function ($line) use ($indent) {  return $line . $indent;},
                [$template[6], ...array_slice($template, 3, 3), $template[0], $template[13], $template[1], $template[2]]));
    }

    public function getTemplateChampObligatoire($templatePath)
    {
        $template = file($templatePath, FILE_IGNORE_NEW_LINES);
        return [$template[0], $template[2].$template[4]];
    }

}