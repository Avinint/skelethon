<?php

namespace ESM;

use E2D\E2DField;

class ESMField extends E2DField
{
    private function getEditField()
    {
        if ($this->isDate()) {
            return "$this->column = '.\$this->$this->name.'";
        }
        return "$this->column = \''.addslashes(\$this->$this->name).'\'";
    }

    public static function getInsertColumns()
    {
        return array_map(function($field) { return str_repeat("\x20", 16) .$field->column;}, self::$collection);
    }

    private function getInsertValue()
    {
        if ($this->isDate()) {
            return "'.\$this->$this->name.'";
        }

        return  "\''.addslashes(\$this->$this->name).'\'";
    }

    public static function getInsertValues()
    {
        return array_map(function($field) {return str_repeat("\x20", 16) .$field->getInsertValue();}, self::$collection);
    }

    public static function getEditFields()
    {
        return array_map(function($field) { return str_repeat("\x20", 12) .$field->getEditField();}, self::$collection);
    }
}