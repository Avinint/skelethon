<?php

namespace Core;

class Field
{
    protected static $collection = [];

    protected $type;
    protected $name;
    protected $column;
    protected $alias;
    protected $label;
    protected $defaultValue;

    /**
     * Field constructor.
     * @param $type
     * @param $name
     * @param $column
     * @param $formattedName
     * @param $defaultValue
     */
    public function __construct($type, $name, $columnName, $defaultValue, $alias, $params = [])
    {
        $this->type = $type;
        $this->name = $name;
        $this->column = $columnName;
        $this->defaultValue = $defaultValue;
        $this->alias = $alias;
        $this->label = $this->labelize($columnName);

        if ('enum' === $type) {
            $this->enum = $this->parseEnumValues($params['enum']);
        }

        $this->isPrimaryKey = isset($params['pk']) && (true === $params['pk']);
        $this->isNullable = isset($params['is_nullable']) && ($params['is_nullable']);
        $this->maxLength = isset($params['maxlength']) ? ($params['maxlength']) : null;
        $this->step = isset($params['step']) ? ($params['step']) : null;

        self::$collection[] = $this;
    }

    public static function getSelectFields()
    {
        return array_map(function ($field) {return $field->getSelectField();}, self::$collection);
    }

    protected function parseEnumValues($enum)
    {
        $values = str_replace(['enum(',')', '\''], '', $enum);

        return explode(',', $values);
    }

    protected function labelize($name = '')
    {
        $name = strtolower(str_replace('-', '_', $name));
        $name = ucfirst(str_replace('_', ' ', $name));

        return $name;
    }

    public static function getViewFields($showId = false)
    {
        return array_map(function (Field $field) {
            return $field->getViewField();}, array_filter(self::$collection, function ($field) use ($showId) {return !$field->isPrimaryKey || $showId;}));
    }

    private function getViewField()
    {
        $properties =  [
            'field' => $this->getFormattedName(),
            'column' => $this->column,
            'label' => $this->label,
            'type' => $this->type,
            'default' => $this->defaultValue,
            'name' => $this->name,
            'is_nullable' => $this->isNullable,
            'enum' => (isset($this->enum) ? $this->enum : null)
        ];

        if (isset($this->step)) {
            $properties['step'] = $this->step;
        }

        // TODO transferer dans la partie E2D
        if (isset($this->selectAjax)) {
            $properties['selectAjax'] = $this->selectAjax;
        }

        return $properties;
    }

    public static function getSearchCriteria()
    {
        return array_map(function($field) {return $field->getSearchCriterion();}, self::$collection);
    }

    public static function getValidationCriteria()
    {
        return array_map(function($field) { return $field->getValidationCriterion(); }, self::$collection);
    }

    public static function getTableHeaders()
    {
        return array_map(function($field) {return $field->getTableHeader();}, self::$collection);
    }

    public static function getTableColumns()
    {
        return array_map(function ($field) {return $field->getTableColumn();}, self::$collection);
    }

    public static function getAttributes()
    {
        return array_map(function($field){ return $field->getFieldMapping(); }, self::$collection);
    }

    public function isNumber()
    {
        return array_contains($this->type, array('int', 'smallint', 'tinyint', 'float', 'decimal', 'double'));
    }

    public function isDate()
    {
        return array_contains($this->type, array('date', 'datetime'));
    }

    public function isDateOrEnum()
    {
        return $this->isDate() || $this->type === 'enum';
    }

    protected static function getFieldByColumn($columnName)
    {
        return array_values(array_filter(self::$collection, function($field) use ($columnName) {
            return $field->column === $columnName;
        }))[0];
    }

    protected function set($field, $value)
    {
        $this->$field = $value;
    }

    public static function changeFieldType($columnName, $type)
    {
        static::getFieldByColumn($columnName)->set('type', $type);
    }

}