<?php

namespace Eto;

use E2D\E2DControllerFileGenerator;
use E2D\E2DField;

class EtoControllerFileGenerator extends E2DControllerFileGenerator
{
    /**
     * @param E2DField $field
     * @param $fieldTemplatePath
     * @param string $fieldsNotNullableText
     * @return string
     */
    protected function generateControllerIntegerField(E2DField $field, $fieldTemplatePath): string
    {
        return str_replace(['COLUMN', 'NAME'], [$field->getColumn(), $field->getName()], file($fieldTemplatePath)[0]);
    }

    /**
     * @param $enumPath
     * @param E2DField $field
     * @param array $allEnumEditLines
     * @param array $allEnumSearchLines
     * @param array $enumDefaults
     * @return array
     */
    protected function handleControllerEnumField($enumPath, E2DField $field, array &$allEnumEditLines, array &$allEnumSearchLines, array &$enumDefaults)
    {
        $enumLines = $enumSearchLines = file($enumPath);
        $enumEditionLines = $enumLines[0];
        $default = $field->getDefaultValue() ?? '';

        // TODO finit d'intégrer les differences pour les champs parametres
        if ($this->model->usesSelect2) {
            if ($default) {
                $enumSearchLines = array_slice($enumLines, 0, 3);
                $enumDefault = $enumLines[3];
            } else {
                $enumSearchLines = array_slice($enumLines, 0, 1);
            }
        } else {
            if ($default) {
                $enumSearchLines = $enumLines;
                $enumDefault = $enumLines[2];
            } else {
                $enumSearchLines = [$enumLines[0]];
            }
        }

        $searches = ['NAME', 'mODULE', 'TABLE', 'COLUMN', 'DEFAULT'];
        $replacements = [$field->getName(), $this->moduleName, $this->model->getName(), $field->getColumn(), $default];

        if ($field->is('parametre')) {
            $searches[] = 'TYPE';
            $replacements[] = $field->getParametre('module');
        }

        $allEnumEditLines[] = str_replace($searches, $replacements, $enumEditionLines);
        $allEnumSearchLines[] = str_replace($searches, $replacements, implode('', $enumSearchLines));


        if ($default) {
            $enumDefaults[] = str_replace($searches, $replacements, $enumDefault);
        }

    }
}    