<?php

namespace Core;

abstract class Field
{
    const FIELD_VIEWS = ['recherche', 'liste', 'consultation', 'edition'];
//    protected static $collection = [];
    public static $module;
    public $model;

    protected $type;
    protected $name;
    protected $column;
    protected $alias;
    protected $label;
    protected $isNullable;
    protected $defaultValue;
    protected $views;
    protected $maxLength;
    protected $enum;
    protected $manyToOne;
    protected $step;

    public $isPrimaryKey;

    /**
     * Field constructor.
     * @param $type
     * @param $name
     * @param $columnName
     * @param $defaultValue
     * @param $alias
     * @param $model
     * @param array $params
     */
    public function __construct($type, $name, $columnName, $defaultValue, $alias, $model, $params = [])
    {
        $this->type = $type;
        $this->name = $name;
        $this->column = $columnName;
        $this->defaultValue = $defaultValue;
        $this->alias = $alias;
        $this->label = $this->labelize($columnName);
        $this->model = $model;

        if ('enum' === $type) {
            $this->enum = $this->parseEnumValues($params['enum']);
        }

        $this->isPrimaryKey = $type === 'primaryKey';
        $this->isNullable = isset($params['is_nullable']) && ($params['is_nullable']);
        $this->maxLength = isset($params['maxlength']) ? ($params['maxlength']) : null;
        //$this->step = isset($params['step']) ? ($params['step']) : null;

       // self::$collection[] = $this;
    }

    // TODO remove
    public static function getSelectFields($template)
    {
        return array_map(function ($field) use ($template) {return $field->getSelectField($template);}, self::$collection);
    }

    protected function parseEnumValues($enum)
    {
        $values = str_replace(['enum(',')', '\''], '', $enum);

        return explode(',', $values);
    }

    protected function labelize($name = '')
    {
        if (strpos($name, 'id_') === 0) {
            $name = str_replace('id_', '', $name);
        }
        $name = strtolower(str_replace('-', '_', $name));
        $name = ucfirst(str_replace('_', ' ', $name));

        return $name;
    }

    public function getViewField()
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

//    public static function getAttributes()
//    {
//        return array_map(function($field){ return $field->getFieldMapping(); }, self::$collection);
//    }
    /**
     * @return bool
     */
    public function isNumber()
    {
        return $this->is(['int', 'smallint', 'tinyint', 'bigint', 'primaryKey', 'foreignKey', 'float', 'decimal', 'double']);
    }

    /**
     * @return bool
     */
    public function isFloat()
    {
        return $this->is(['float', 'decimal', 'double']);
    }

    /**
     * @return bool
     */
    public function isInteger()
    {
        return $this->is(['int', 'smallint', 'tinyint', 'bigint', 'primaryKey', 'foreignKey']);
    }

    /**
     * @return bool
     */
    public function isDate()
    {
        return array_contains($this->type, array('date', 'datetime', 'time'));
    }

    /**
     * @return bool
     */
    public function isTime()
    {
        return array_contains($this->type, array('datetime', 'time'));
    }

    /**
     * @return bool
     */
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

    /**
     * @return bool
     */
    public function isNullable()
    {
        return $this->isNullable;
    }

    abstract public function getSelectField($template);

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     * @return bool
     */
    public function is($type)
    {
        if (is_array($type))
            return array_contains($this->type, $type);
        else
            return $this->type ===  $type;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @return mixed
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param array $views
     */
    public function setViews(?array $views = [])
    {
        $this->views = $views;
    }

    /**
     * @param string|array $view
     * @return bool
     */
    public function hasView($view)
    {
        return isset($this->views) && ($this->views === []
                || ('base' === $view && array_contains_array(self::FIELD_VIEWS,  $this->views, true))
                || (is_array($view) && array_contains_array($view, $this->views, true))
                || ( array_contains($view, $this->views)));
    }

    /**
     * @return mixed
     */
    public function getViews()
    {
        return $this->views;
    }

    /**
     * @param $actions
     * @return array|null
     */
    public function askViews($actions) : ?array
    {
        if ($this->isPrimaryKey) {
            $hasAllViews = true;
            $views = ['recherche'];
        } else {
            $typeVues = $this->getAllowedViewTypes($actions);
            $views = $this->handleQuestionsAboutViews($typeVues);
            $hasAllViews = count($views) === count(self::FIELD_VIEWS);
        }

        return $this->saveViews($views, $hasAllViews);
    }

    /**
     * @param array $typeVues
     * @return array
     */
    private function handleQuestionsAboutViews(array $typeVues): array
    {
        $views = [];
        $answerForAllViews = (int)ModelMaker::prompt('Voulez vous sélectionner \'' . $this->getColumn() . '\' pour toutes les vues, pour certaines vues, ou jamais? ', [1, 2, 3]);
        if ($answerForAllViews === 1) {
            $views = $typeVues;
        } elseif ($answerForAllViews === 2) {
            $views = $this->askEachView($typeVues);
        }
        return $views;
    }

    /**
     * @param array $typeVues
     * @return array
     */
    private function askEachView(array $typeVues): array
    {
        $views = [];
        foreach ($typeVues as $typeVue) {
            if (ModelMaker::prompt('Voulez vous sélectionner \'' . $this->getColumn() . '\' pour la vue \'' . $typeVue . '\'?', ['o', 'n']) === 'o') {
                $views[] = $typeVue;
            }
        }

        return $views;
    }

    /**
     * @param array $views
     * @param bool $hasAllViews
     * @return array|null
     */
    private function saveViews(array $views, bool $hasAllViews) : ?array
    {
        if (!empty($views)) {
            if ($hasAllViews) {
                return $this->addViews();
            } else {
                return $this->addViews($views);
            }
        }

        return null;
    }

    /**
     * @param array $views
     */
    private function addViews(array $views = []): array
    {
        $this->setViews($views);
        return $views;
    }

    /**
     * @param $actions
     * @return array|string[]
     */
    private function getAllowedViewTypes($actions)
    {
        if ($this->is('text')) {
            $typeVues = array_intersect(['edition', 'consultation'], ['liste'] + $actions);
        } else {
            $typeVues = array_values(array_intersect(self::FIELD_VIEWS, ['liste'] + $actions));
        }
        return array_values($typeVues);
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function getEnum()
    {
        return $this->enum;
    }

    /**
     * @return mixed
     */
    public function getManyToOne()
    {
        return $this->manyToOne;
    }

    /**
     * @return mixed
     */
    public function getStep()
    {
        return  isset($this->step) ? ' step="'.$this->step.'"' : '';
    }

}