<?php

namespace ESM;

class ESMModelMakerLegacy extends ESMModelMaker
{
    public function getEditFields() :string
    {
        return implode(','.PHP_EOL, array_map(function($field) { return str_repeat("\x20", 12) .$field->getUpdateField();}, $this->getUpdateFields()));
    }

    /**
     * Pour générer le bInsert
     */
    public function getInsertColumns()
    {
        return implode(','.PHP_EOL, array_map(function($field) { return str_repeat("\x20", 16) .$field['column'];}, $this->getViewFields('edition')));
    }

    public function getInsertValues()
    {
        return implode(','.PHP_EOL, array_map(function(ESMFIELD $field) {return str_repeat("\x20", 16) .$field->getInsertValue();}, $this->getInsertFields()));
    }

}