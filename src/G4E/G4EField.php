<?php

namespace G4E;

use E2D\E2DField;
use E2D\FieldType\NumberType;

class G4EField extends E2DField
{
    const TYPES = ['ClePrimaire' => 'PrimaryKeyType', 'Char' => 'StringType', 'Timestamp' => 'TimestampType',
        'DateTime' => 'DatetimeType', 'Nombre' => 'NumberType', 'Double' => 'FloatType', 'Texte' => 'TextType',
                   'CleEtrangere' => 'ForeignKeyType', 'Date' => 'DateType'];

    const OFFSETS = ['foreignKey' => 2];

    public function getFieldMapping($templatePath, $table = '')
    {
        $suffixes = $this->isNullable || $this->isPrimaryKey ? '' :  '->oSetNotNull()';

        if ($this->is('foreignKey')) {

            $template = $this->selectCorrectTemplate($templatePath, $suffixes,  self::OFFSETS['foreignKey'] )
                . PHP_EOL . $this->getChampLibelle($templatePath);

        } else {
            $template = $this->selectCorrectTemplate($templatePath, $suffixes);
        }

        return str_replace(['{{ COLUMN }}', '{{ NAME }}', '{{ TYPE }}', '{{ TABLE }}', '{{ SUFFIXES }}', '{{ ALIAS }}'],
                           [$this->column, $this->name, $this->type->getClasseMapping(), $this->manyToOne['table'] ?? $table, $suffixes, $this->manyToOne['alias'] ?? ''],
                           $template);
    }

    protected function selectCorrectTemplate($templatePath, $suffixes = '', $indexOffset = 0)
    {
        return file($templatePath, FILE_IGNORE_NEW_LINES) [(int) ($suffixes !== '') + $indexOffset];
    }

    /**
     *  AJoute champ libellé si présence champ clé étrangère
     * @param $templatePath
     * @return array|mixed|string|string[]
     */
    protected function getChampLibelle($templatePath)
    {
        return  isset($this->manyToOne['label']) ? str_replace(['{{ COLUMN }}', '{{ NAME }}', '{{ TYPE }}', '{{ TABLE }}', '{{ SUFFIXES }}'],
                [
                    $this->manyToOne['label'],
                    $this->manyToOne['labelAlias'], 'Char' ,
                    $this->manyToOne['table'], "->oSetAlias('{$this->manyToOne['alias']}')"
                ], $this->selectCorrectTemplate($templatePath, "->oSetAlias('{$this->manyToOne['alias']}')")) : '';
    }

}