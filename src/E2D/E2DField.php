<?php

namespace E2D;

use Core\Field;

class E2DField extends Field
{
    protected $formatted;
    
    public function __construct($type, $name, $columnName, $defaultValue, $alias, $params = [])
    {
        parent::__construct($type, $name, $columnName, $defaultValue, $alias, $params);

        $this->formatted = array_contains($type, array('float', 'decimal', 'date', 'datetime', 'time', 'double', 'bool', 'enum', 'foreignKey'));
    }

    protected function getFormattedName()
    {
        if ($this->formatted) {
            if ($this->type === 'foreignKey') {
                return str_replace('nId', '', 's'.$this->name);
            }
            return $this->name.'Formate';
        }

        return $this->name;
    }

    protected function getFormattedColumn()
    {
        if ($this->formatted) {
            if ($this->type === 'foreignKey') {
                return str_replace('id_', '', $this->name);
            }
            return $this->column.'_formate';
        }

        return $this->column;
    }

    /**
     * @return string
     */
    public function getSelectField()
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
        } elseif ('time' === $this->type) {
            $lines[] = "{$indent}IF($this->alias.$this->column, DATE_FORMAT($this->alias.$this->column, \'%H\h%i\'), \'\') AS {$this->getFormattedName()}";
        } elseif (array_contains($this->type, ['float', 'double', 'decimal'])) {
            $lines[] = "{$indent}REPLACE($this->alias.$this->column, \'.\', \',\') AS {$this->getFormattedName()}";
        } elseif ('bool' === $this->type) {
            $lines[] = "$indent(CASE WHEN $this->alias.$this->column = 1 THEN \'oui\' ELSE \'non\' END) AS {$this->getFormattedName()}";
        } elseif ("enum" === $this->type) {
            $module = self::$module;
            $model = self::$model;
            $lines[] = $indent."' . \$this->sFormateValeurChampConf('$module', '$model', '$this->column', '{$this->getFormattedName()}') . '";
        } elseif ('foreignKey' === $this->type) {
            $strategy = $this->oneToMany['strategy'] ?? 'joins';
            if ($strategy === 'nested') {
                $template = '(
                        SELECT LABEL
                        FROM FKTABLE
                        WHERE PK = ALIAS.PK
                    ) AS FIELD';
            } else {
                if ($this->oneToMany['concat']) {
                   ;$template = 'LABEL CONCATALIAS';
                } else {

                    $template = 'FKALIAS.LABEL CONCATALIAS';
                }
            }


            //$alias = $this->oneToMany['alias'] !== '' ?  $this->oneToMany['alias'].'.' : '';
            $lines[] = str_replace(['FKALIAS', 'LABEL', 'CONCATALIAS', 'FKTABLE', 'PK', 'ALIAS', 'FIELD'],
                [$this->oneToMany['alias'], $this->oneToMany['label'], $this->oneToMany['childTableAlias'], $this->oneToMany['table'], $this->oneToMany['pk'], $this->alias, $this->getFormattedName()],
                $indent.$template);
        }

        return implode(','.PHP_EOL, $lines);
    }

    public function getSearchCriterion()
    {
        $aCriteresRecherche = [];
        $fieldName = "AND $this->alias.$this->column";

        if (array_contains($this->type, array('tinyint', 'smallint', 'int', 'float', 'decimal', 'double', 'foreignKey'))) {
            $conditionEquals = $fieldName . ' = ' . $this->addNumberField($this->name, array_contains($this->type, ['smallint', 'int']));
            $aCriteresRecherche[] = $this->addNumberCriterion($this->name, $conditionEquals);

            if (!preg_match('/^nId([A-Z]{1}([a-z]*))$/', $this->name)) {
                $conditionMin = $fieldName . ' >= ' . $this->addNumberField($this->name . 'Min', array_contains($this->type, ['smallint', 'int']));
                $aCriteresRecherche[] = $this->addNumberCriterion($this->name.'Min', $conditionMin);
                $conditionMax = $fieldName . ' <= ' . $this->addNumberField($this->name . 'Max', array_contains($this->type, ['smallint', 'int']));
                $aCriteresRecherche[] = $this->addNumberCriterion($this->name.'Max', $conditionMax);
            }
        } elseif ('bool' === $this->type) {
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
            $whereLike = $fieldName.' LIKE \'%".addslashes($aRecherche[\''.$this->name.'Partiel\'])."%\'';
            $aCriteresRecherche[] = $this->addStringCriterion($this->name.'Partiel', $whereLike);
        }

        return implode(PHP_EOL, $aCriteresRecherche);
    }

    protected function addNumberField($field, $integer = true)
    {
        $text = 'addslashes($aRecherche[\'' . $field . '\'])';
        $text =  $integer ? $text : 'str_replace(\',\', \'.\', '. $text.')';

        return '".'.$text.'."';
    }

    protected function addNumberCriterion($field, $whereClause)
    {
        return  str_repeat("\x20", 8) . 'if (isset($aRecherche[\''.$field .'\']) && $aRecherche[\''. $field .'\'] > 0) {'.PHP_EOL.
            $this->addQuery($whereClause);
    }

    protected function addBooleanCriterion($field, $whereClause)
    {
        return str_repeat("\x20", 8).'if (isset($aRecherche[\''.$field.'\']) && $aRecherche[\''.$field.'\'] != \'nc\') {'.PHP_EOL.
            $this->addQuery($whereClause);
    }

    protected function addDateCriterion($field, $whereClause, $suffixe)
    {
        return str_repeat("\x20", 8) . "if (isset(\$aRecherche['" . $field . '\']) === true && $aRecherche[\'' . $field . '\'] !== \'\') {' . PHP_EOL .
            str_repeat("\x20", 12) . 'if (!preg_match(\'/:/\', $aRecherche[\'' . $field . '\']) && !preg_match(\'/h/\', $aRecherche[\'' . $field . "'])) {" . PHP_EOL .
            str_repeat("\x20", 16) . '$aRecherche[\'' . $field . '\']'.($suffixe ? ' .= \'' . $suffixe . '\'' : $suffixe).';' . PHP_EOL .
            str_repeat("\x20", 12)."}" . PHP_EOL .
            $this->addQuery($whereClause);
    }

    protected function addStringCriterion($field, $whereClause)
    {
        return str_repeat("\x20", 8).'if (isset($aRecherche[\''.$field.'\']) && $aRecherche[\''.$field.'\'] != \'\') {'.PHP_EOL.
            $this->addQuery($whereClause);
    }

    protected function addQuery($whereClause)
    {
        return str_repeat("\x20", 12).'$sRequete .= "'.PHP_EOL.
            str_repeat("\x20", 16) . $whereClause . PHP_EOL.
            str_repeat("\x20", 12).'";'.PHP_EOL.
            str_repeat("\x20", 8).'}'.PHP_EOL;
    }

    public function getValidationCriterion()
    {
        $sCritere = str_repeat("\x20", 8) . "\$aConfig['" . $this->name . '\'] = array(' . PHP_EOL;
        if (!$this->isNullable) {
            $sCritere .=
                str_repeat("\x20", 12) . '\'required\' => \'1\',' . PHP_EOL .
                str_repeat("\x20", 12) . '\'minlength\' => \'1\',' . PHP_EOL;
        }

        if (isset($this->maxLength)) {
            $maxLength = str_replace(' unsigned', '', $this->maxLength);
            if (preg_match('/,/', $this->maxLength)) {
                $aLength = explode(',', $this->maxLength);
                $maxLength = 1;
                $maxLength += (int)$aLength[0];
                $maxLength += (int)$aLength[1];
            }

            $sCritere .= str_repeat("\x20", 12) . "'maxlength' => '$maxLength'," . PHP_EOL;
        }

        return $sCritere . str_repeat("\x20", 8) . ');' . PHP_EOL;
    }

    /**
     * @param $data
     */
    public function getTableHeader()
    {
        return str_repeat("\x20", 16).'<th id="th_'.$this->column.'" class="tri">'.$this->label.'</th>';
    }

   public function getTableColumn()
    {
        $alignment = $this->getAlignmentFromType();

        return str_repeat("\x20", 16)."<td class=\"{$this->getFormattedName()}$alignment\"></td>";
    }

    public function getAlignmentFromType()
    {
        return ($this->isNumber() ? ' align-right' : ($this->isDateOrEnum() ? ' align-center'  : ''));
    }

    public function getFieldMapping()
    {
        return str_repeat("\x20", 12)."'$this->column' => '$this->name',";;
    }

    public function changeToOneToManyField($oneToManyParams)
    {
        $this->type = 'foreignKey';
        if(is_array($oneToManyParams['label'])) {
            $oneToManyParams['label'] = static::generateConcatenatedColumn(
                $oneToManyParams['label'],
                $oneToManyParams['alias']
            );
            $oneToManyParams['concat'] = true;
        }

        $this->oneToMany = $oneToManyParams;
        $this->formatted = true;

    }

    /**
     * Transforme un champ clé étrangère en select2 Ajax
     * @param $properties
     */
    protected function handleAssociations(&$properties)
    {
        if (isset($this->oneToMany)) {
            $properties['oneToMany'] = $this->oneToMany;
        }
    }

    /**
     * @param array $column
     * @param string $alias
     * @return string
     */
    protected static function generateConcatenatedColumn(array $column, $alias = ''): string
    {
        if ($alias !== '') {
            $alias = $alias. '.';

            $column = array_map(function($part) use ($alias) {return $alias.$part;}, $column);
        }
        return "CONCAT_WS(\' \', " . implode(", ",  $column) . ')';
    }
}