<?php

class Field
{
    private static $collection = [];

    private $type;
    private $name;
    private $column;
    private $alias;
    private $formatted;
    private $label;
    private $defaultValue;

    /**
     * Field constructor.
     * @param $type
     * @param $name
     * @param $column
     * @param $formattedName
     * @param $defaultValue
     */
    public function __construct($type, $name, $columnName, $defaultValue, $alias, $columnInfo = [])
    {
        $this->type = $type;
        $this->name = $name;
        $this->column = $columnName;
        $this->defaultValue = $defaultValue;
        $this->alias = $alias;

        $this->formatted = array_contains($type, array('float', 'decimal', 'date', 'datetime', 'double', 'tinyint'));
        $this->label = $this->labelize($columnName);

        if ('enum' === $type) {
            $this->enum = $this->parseEnumValues($columnInfo['enum']);
        }

        $this->isPrimaryKey = isset($columnInfo['pk']) && (true === $columnInfo['pk']);
        $this->isNullable = isset($columnInfo['is_nullable']) && ($columnInfo['is_nullable']);
        $this->maxLength = isset($columnInfo['maxlength']) ? ($columnInfo['maxlength']) : null;

        self::$collection[] = $this;
    }

    private function getFormattedName()
    {
        if ($this->formatted) {
            return $this->name.'Format';
        }

        return $this->name;
    }

    private function getFormattedColumn()
    {
        if ($this->formatted) {
            return $this->column.'_format';
        }

        return $this->column;
    }

    private function getSelectField()
    {
        $indent = str_repeat("\x20", 20);
        $lines = [];
        $lines[] = "{$indent}$this->alias.$this->column";
        if ($this->isPrimaryKey) {
            $lines[] =  "{$indent}$this->alias.$this->column nIdElement";;
        }

        if ('date' === $this->type) {
            $lines[] = "{$indent}IF($this->alias.$this->column, DATE_FORMAT($this->alias.$this->column, \'%d/%m/%Y\'), \'\') AS {$this->getFormattedName()}";
        } elseif ('datetime' === $this->type) {
            $lines[] = "{$indent}IF($this->alias.$this->column, DATE_FORMAT($this->alias.$this->column, \'%d/%m/%Y à %H\h%i\'), \'\') AS {$this->getFormattedName()}";
        } elseif (array_contains($this->type, ['float', 'double', 'decimal'])) {
            $lines[] = "{$indent}REPLACE($this->alias.$this->column, \'.\', \',\') AS {$this->getFormattedName()}";
        } elseif ('tinyint' === $this->type) {
            $lines[] = "$indent(CASE WHEN $this->alias.$this->column = 1 THEN \'oui\' ELSE \'non\' END) AS {$this->getFormattedName()}";
        }

        return implode(','.PHP_EOL, $lines);
    }

    public static function getSelectFields()
    {
        return array_map(function ($field) {return $field->getSelectField();}, self::$collection);
    }

    private function parseEnumValues($enum)
    {
        $values = str_replace(['enum(',')', '\''], '', $enum);

        return explode(',', $values);
    }

    private function labelize($name = '')
    {
        $name = strtolower(str_replace('-', '_', $name));
        $name = ucfirst(str_replace('_', ' ', $name));

        return $name;
    }

    public static function getViewFields($showId = false)
    {
        return array_map(function ($field) {return [
            'field' => $field->getFormattedName(),
            'column' => $field->column,
            'label' => $field->label,
            'type' => $field->type,
            'default' => $field->defaultValue,
            'name' => $field->name,
            'enum' => (isset($field->enum) ? $field->enum : null)
        ];}, array_filter(self::$collection, function ($field) use ($showId) {return !$field->isPrimaryKey || $showId;}));
    }

    private function getSearchCriterion()
    {
        $aCriteresRecherche = [];
        $fieldName = "AND $this->alias.$this->column";

        if (array_contains($this->type, array('smallint', 'int', 'float', 'decimal', 'double'))) {
            $conditionEquals = $fieldName . ' = ' . $this->addNumberField($this->name, array_contains($this->type, ['smallint', 'int']));
            $aCriteresRecherche[] = $this->addNumberCriterion($this->name, $conditionEquals);

            if (!preg_match('/^nId([A-Z]{1}([a-z]*))$/', $this->name)) {
                $conditionMin = $fieldName . ' >= ' . $this->addNumberField($this->name . 'Min', array_contains($this->type, ['smallint', 'int']));
                $aCriteresRecherche[] = $this->addNumberCriterion($this->name.'Min', $conditionMin);
                $conditionMax = $fieldName . ' <= ' . $this->addNumberField($this->name . 'Max', array_contains($this->type, ['smallint', 'int']));
                $aCriteresRecherche[] = $this->addNumberCriterion($this->name.'Max', $conditionMax);
            }
        } elseif ('tinyint' === $this->type) {
            $conditionEquals = $fieldName . ' = ' . $this->addNumberField($this->name);

            $aCriteresRecherche[] = $this->addBooleanCriterion($this->name, $conditionEquals);

        } elseif (array_contains($this->type, array('date', 'datetime')) === true) {

            if ($this->type === 'date') {
                $sSuffixeDebut = '';
                $sSuffixeFin = '';
                $sFormat = ', \'Y-m-d\'';
            } else {
                $sSuffixeDebut = ' 00:00:00';
                $sSuffixeFin = ' 23:59:59';
                $sFormat = ', \'Y-m-d H:i:s\'';
            }

            $whereDebut = $fieldName .' >= \'".addslashes($this->sGetDateFormatUniversel($aRecherche[\''. $this->name.'Debut'.'\']'.$sFormat.")).\"'";
            $aCriteresRecherche[] = $this->addDateCriterion($this->name.'Debut', $whereDebut, $sSuffixeDebut);

            $whereFin = $fieldName .' <= \'".addslashes($this->sGetDateFormatUniversel($aRecherche[\''. $this->name.'Fin'.'\']'.$sFormat.")).\"'";
            $aCriteresRecherche[] = $this->addDateCriterion($this->name.'Fin' , $whereFin, $sSuffixeFin);

        } else {
            $whereIEquals = $fieldName.' LIKE \'".addslashes($aRecherche[\''.$this->name.'\'])."\'';

            $aCriteresRecherche[] = $this->addStringCriterion($this->name, $whereIEquals);
            $whereLike = $fieldName.' LIKE \'%".addslashes($aRecherche[\''.$this->name.'\'])."%\'';
            $aCriteresRecherche[] = $this->addStringCriterion($this->name.'Partiel', $whereLike);
        }

        return implode(PHP_EOL, $aCriteresRecherche);
    }

    public static function getSearchCriteria()
    {
        return array_map(function($field) {return $field->getSearchCriterion();}, self::$collection);
    }

    private function addNumberField($field, $integer = true)
    {
        $text = 'addslashes($aRecherche[\'' . $field . '\'])';
        $text =  $integer ? $text : 'str_replace(\',\', \'.\', '. $text.')';

        return '".'.$text.'."';
    }

    private function addNumberCriterion($field, $whereClause)
    {
        return  str_repeat("\x20", 8) . 'if (isset($aRecherche[\''.$field .'\']) && $aRecherche[\''. $field .'\'] > 0) {'.PHP_EOL.
            $this->addQuery($whereClause);
    }

    private function addBooleanCriterion($field, $whereClause)
    {
        return str_repeat("\x20", 8).'if (isset($aRecherche[\''.$field.'\']) && $aRecherche[\''.$field.'\'] != \'nc\') {'.PHP_EOL.
            $this->addQuery($whereClause);
    }

    private function addDateCriterion($field, $whereClause, $suffixe)
    {
        return str_repeat("\x20", 8) . "if (isset(\$aRecherche['" . $field . '\']) === true && $aRecherche[\'' . $field . '\'] !== \'\') {' . PHP_EOL .
            str_repeat("\x20", 12) . 'if (!preg_match(\'/:/\', $aRecherche[\'' . $field . '\']) && !preg_match(\'/h/\', $aRecherche[\'' . $field . "'])) {" . PHP_EOL .
            str_repeat("\x20", 16) . '$aRecherche[\'' . $field . '\']'.($suffixe ? ' .= \'' . $suffixe . '\'' : $suffixe).';' . PHP_EOL .
            str_repeat("\x20", 12)."}" . PHP_EOL .
            $this->addQuery($whereClause);
    }

    private function addStringCriterion($field, $whereClause)
    {
        return str_repeat("\x20", 8).'if (isset($aRecherche[\''.$field.'\']) && $aRecherche[\''.$field.'\'] != \'\') {'.PHP_EOL.
            $this->addQuery($whereClause);
    }

    private function addQuery($whereClause)
    {
        return str_repeat("\x20", 12).'$sRequete .= "'.PHP_EOL.
            str_repeat("\x20", 16) . $whereClause . PHP_EOL.
            str_repeat("\x20", 12).'";'.PHP_EOL.
            str_repeat("\x20", 8).'}'.PHP_EOL;
    }

    private function getValidationCriterion()
    {
        $sCritere = str_repeat("\x20", 8)."\$aConfig['".$this->name.'\'] = array(' . PHP_EOL;
        if (!$this->isNullable) {
            $sCritere .=
                str_repeat("\x20", 12).'\'required\' => \'1\','.PHP_EOL.
                str_repeat("\x20", 12).'\'minlength\' => \'1\','.PHP_EOL;
        }

        if (isset($this->maxLength)) {
            $maxLength = str_replace(' unsigned', '', $this->maxLength);
            if (preg_match('/,/', $this->maxLength)) {
                $aLength = explode(',', $this->maxLength);
                $maxLength = 1;
                $maxLength += (int)$aLength[0];
                $maxLength += (int)$aLength[1];
            }

            $sCritere .= str_repeat("\x20", 12)."'maxlength' => '$maxLength'," . PHP_EOL;
        }

        return $sCritere.str_repeat("\x20",8).');' . PHP_EOL;
    }

    public static function getValidationCriteria()
    {
        return array_map(function($field) { return $field->getValidationCriterion(); }, self::$collection);
    }

    /**
     * @param $data
     */
    private function getTableHeader()
    {
        return str_repeat("\x20", 16).'<th id="th_'.$this->column.'" class="tri">'.$this->name.'</th>';
    }

    public static function getTableHeaders()
    {
        return array_map(function($field) {return $field->getTableHeader();}, self::$collection);
    }

    private function getTableColumn()
    {
        $alignment = $this->getAlignmentFromType();

        return str_repeat("\x20", 16)."<td class=\"{$this->getFormattedName()}$alignment\"></td>";
    }

    public function getAlignmentFromType()
    {
        return ($this->isNumber() ? ' align-right' : ($this->isDateOrEnum() ? ' align-center'  : ''));
    }

    public static function getTableColumns()
    {
        return array_map(function ($field) {return $field->getTableColumn();}, self::$collection);
    }

    private function getFieldMapping()
    {
        return str_repeat("\x20", 12)."'$this->column' => '$this->name',";;
    }

    public static function getFieldMappings()
    {
        return array_map(function($field){ return $field->getFieldMapping(); }, self::$collection);
    }

    public function isNumber()
    {
        return array_contains($this->type, array('int', 'smallint', 'float', 'decimal', 'double'));
    }

    public function isDate()
    {
        return array_contains($this->type, array('date', 'datetime'));
    }

    public function isDateOrEnum()
    {
        return $this->isDate() || $this->type === 'enum';
    }
}