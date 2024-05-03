<?php


namespace E2D\FieldType;


class TimestampType extends DateTimeType
{
    public function getClasseMapping() : string
    {
        return "Timestamp";
    }
}