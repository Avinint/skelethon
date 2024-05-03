<?php


namespace E2D\FieldType;

class StringType extends FieldType
{
    public function getClasseMapping() : string
    {
        return "Char";
    }
}