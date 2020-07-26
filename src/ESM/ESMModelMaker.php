<?php

namespace ESM;

use E2D\E2DModelMaker;

class ESMModelMaker extends E2DModelMaker
{
    public function getEditFields() :string
    {
        return implode(','.PHP_EOL, $this->fieldClass::getEditFields());
    }

    /**
     * Pour générer le bInsert
     */
    public function getInsertColumns()
    {
        return implode(','.PHP_EOL, $this->fieldClass::getInsertColumns());
    }

    public function getInsertValues()
    {
        return implode(','.PHP_EOL, $this->fieldClass::getInsertValues());
    }

}