<?php

namespace ESM;

use E2D\E2DControllerFileGenerator;

class ESMControllerFileGenerator extends E2DControllerFileGenerator
{
    /**
     * @param $field
     * @param $fieldTemplatePath
     * @param string $fieldsText
     * @return string
     */
    protected function generateControllerIntegerField($field, $fieldTemplatePath): string
    {
        return str_replace(['COLUMN', 'NAME'], [$field['column'], $field['name']], file($fieldTemplatePath)[5]);
    }
}