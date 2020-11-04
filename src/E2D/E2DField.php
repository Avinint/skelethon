<?php

namespace E2D;

use Core\Field;
use Core\FieldType\DateTimeType;
use Core\FieldType\FieldType;

class E2DField extends Field
{
    protected $formatted;
    protected $parametre;
    
    public function __construct($type, $name, $columnName, $defaultValue, $alias, $model, $params = [])
    {
        parent::__construct($type, $name, $columnName, $defaultValue, $alias, $model, $params);

        $this->formatted = $this->is(['float', 'decimal', 'date', 'datetime', 'time', 'double', 'bool', 'enum', 'foreignKey']);
    }

    public function getFormattedName()
    {
        if ($this->formatted) {
            if ($this->type->getName() === 'foreignKey') {
                return str_replace('nId', '', 's'.$this->name);
            }
            return $this->name.'Formate';
        }

        return $this->name;
    }


    /**
     * @param $actions
     * @return array|string[]
     */
    protected function getAllowedViewTypes($actions)
    {
        if ($this->is('text')) {
            $typeVues = array_intersect(['edition', 'consultation', 'export'], $actions);
        } else {
            //nset($actions['accueil']);
            $typeVues = array_values(['liste'] + $actions);
        }
        return array_values($typeVues);
    }

    public function getFormattedColumn()
    {
        if ($this->formatted) {
            if ($this->type->getName() === 'foreignKey') {
                return str_replace('id_', '', $this->name);
            }
            return $this->column.'_formate';
        }

        return $this->column;
    }

    /**
     * @return string
     */
    public function getSelectField($path)
    {
        $indent = str_repeat("\x20", 20);
        $lines = [];
        $template = file($path, FILE_IGNORE_NEW_LINES);
        if (!$template) {
            return '';
        }
        $lines[] = $indent.str_replace(['ALIAS', 'COLUMN'], [$this->alias, $this->column], $template[0]);
        $lines[] =  $this->type->addSelectFieldFormattedLines($indent, $this, $template);
//        if ($this->isPrimaryKey) {
//            $lines[] =  $this->type->getSelectFieldFormatted($indent, $this, $template);
//        } elseif ('foreignKey' === $this->type->getName()) {
//            $lines[] = $this->type->getSelectFieldFormatted($indent, $this, $template);
//        } else {
//            $lines[] = $this->type->getSelectFieldFormatted($indent, $this, $template);
//        }

        return implode(','.PHP_EOL, array_filter($lines));
    }

//    /**
//     * @param array $template
//     * @param string $indent
//     * @param array $lines
//     * @return string
//     */
//    private function getSelectFieldForeignKey(array $template, Field $this, string $indent): string
//    {
//        $strategy = $this->manyToOne['strategy'] ?? 'joins';
//        if ($strategy === 'nested') {
//            $field = implode($indent, array_slice($template, 9));
//        } else {
//            $field = $indent . $template[8];
//        }
//
//        return str_replace(['FKALIAS', 'LABEL', 'CONCATALIAS', 'FKTABLE', 'PK', 'ALIAS', 'FIELD'],
//            [$this->manyToOne['alias'], $this->manyToOne['label'], $this->manyToOne['labelAlias'], $this->manyToOne['table'], $this->manyToOne['pk'], $this->alias, $this->getFormattedName()],
//            $field);
//    }

    public function getSearchCriterion($path)
    {
        $template = file($path);

        $aCritereRecherche = [];
        $indent = str_repeat("\x20", 8);
        $aCritereRecherche = $this->type->getSearchCriterion($indent, $this, $template, $aCritereRecherche);
//        if ($this->isNumber()) {
//
//            if (!$this->is('primaryKey', 'foreignKey')) {
//                $texteCritere = $indent.implode('', array_map(function($line) use ($indent) {return $line.$indent;},
//                    [$template[7], $template[0], ($this->isInteger() ? $template[11] : $template[12]), $template[1], $template[2]]));
//                $aCritereRecherche[] = str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD'],
//                    [$this->alias, $this->column, '>=', $this->name . 'Min'], $texteCritere);
//                $aCritereRecherche[] = str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD'],
//                    [$this->alias, $this->column, '<=', $this->name . 'Max'], $texteCritere);
//                $aCritereRecherche[] = str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD'],
//                    [$this->alias, $this->column, '=', $this->name . ''], $texteCritere);
//            } else {
//                $texteCritere = $indent.implode('', array_map(function($line) use ($indent) {return $line.$indent;},
//                    [$template[7], $template[0], $template[11], $template[1], $template[2]]));
//                $aCritereRecherche[] = str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD'],
//                    [$this->alias, $this->column, '=', $this->name . ''], $texteCritere);
//            }
//        } elseif ('bool' === $this->type) {

//            $aCritereRecherche[] = str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD'],
//                [$this->alias, $this->column, '=', $this->name . ''],
//                $indent . implode('', array_map(function($line) use ($indent) {return $line.$indent;},
//                    [$template[7].$template[0].$template[11].$template[1].$template[2]])));


//        } elseif ($this->isDate()) {
//
//
//
//        } else {
//            $texteCritere = $indent . implode('',array_map(function($line) use ($indent) {return $line.$indent;},
//                [$template[6], $template[0], $template[9], $template[1], $template[2]]));
//            $aCritereRecherche[] = str_replace(['ALIAS', 'COLUMN', 'FIELD'],
//                [$this->alias, $this->column, $this->name], $texteCritere);
//            $texteCritere = $indent . implode('',array_map(function($line) use ($indent) {return $line.$indent;},
//                [$template[6], $template[0], $template[10], $template[1], $template[2]]));
//            $aCritereRecherche[] = str_replace(['ALIAS', 'COLUMN', 'FIELD'],
//                [$this->alias, $this->column, $this->name.'Partiel'], $texteCritere);
//        }

        return implode(PHP_EOL, $aCritereRecherche);
    }

//    /**
//     * @param string $indent
//     * @param array $template
//     * @param array $aCritereRecherche
//     * @return array
//     */
//    private function getSearchCriterionDate(string $indent, array $template, array $aCritereRecherche): array
//    {
//        if ($this->type === 'date') {
//            $sSuffixeDebut = '';
//            $sSuffixeFin = '';
//            $sFormat = 'Y-m-d';
//        } elseif ($this->type === 'datetime') {
//            $sSuffixeDebut = ' 00:00:00';
//            $sSuffixeFin = ' 23:59:59';
//            $sFormat = 'Y-m-d H:i:s';
//        } else {
//            $sSuffixeDebut = '';
//            $sSuffixeFin = '';
//            $sFormat = 'H:i:s';
//        }

//        if ($this->type->getName === 'datetime') {
//            $texteCritere = $indent . implode('', array_map(function ($line) use ($indent) {
//                    return $line . $indent;
//                },
//                    [$template[6], ...array_slice($template, 3, 3), $template[0], $template[13], $template[1], $template[2]]));
//        } else {
//            $texteCritere = $indent . implode('', array_map(function ($line) use ($indent) {
//                    return $line . $indent;
//                },
//                    [$template[6], $template[0], $template[13], $template[1], $template[2]]));
//        }
//
//        $aCritereRecherche[] = str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD', 'SUFFIXE', 'FORMAT'],
//            [$this->alias, $this->column, '>=', $this->name . 'Debut', $this->type::SUFFIXE_DEBUT, $this->type::FORMAT], $texteCritere);
//        $aCritereRecherche[] = str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD', 'SUFFIXE', 'FORMAT'],
//            [$this->alias, $this->column, '<=', $this->name . 'Fin', $this->type::SUFFIXE_DEBUT, $this->type::FORMAT], $texteCritere);
//        return $aCritereRecherche;
//    }

    public function getValidationCriterion($path)
    {
        $indent = str_repeat("\x20", 8);
        $template = file($path);
        $sCritere = $indent . str_replace('NAME', $this->name, $template[0]);
        if ($this->isNullable && !isset($this->maxLength)) {
            return '';
        }
        if (!$this->isNullable) {
            $sCritere .= $indent.$template[1].$indent.$template[2];
        }

        if (isset($this->maxLength)) {
            $maxLength = str_replace(' unsigned', '', $this->maxLength);
            if (strpos($this->maxLength, ',')) {
                $aLength = explode(',', $this->maxLength);
                $maxLength = 1;
                $maxLength += (int)$aLength[0];
                $maxLength += (int)$aLength[1];
            }
            $sCritere .= $indent . str_replace('MAX', $maxLength, $template[3]);
        }

        return $sCritere . $indent .$template[4] . PHP_EOL;
    }

    public function getTableHeader($templatePath)
    {
        return str_replace(['COLUMN', 'LABEL'], [$this->column, $this->label],
            file_get_contents(str_replace('.', '_tableheader.', $templatePath)));
    }

    public function getTableColumn($templatePath)
    {
        return str_replace(['NAME', 'ALIGN'], [$this->getFormattedName(), ''],
            file_get_contents(str_replace('.', '_tablecolumn.', $templatePath)));
    }

    public function getAlignmentFromType()
    {
        return ($this->isNumber() ? ' align-right' : ($this->isDateOrEnum() ? ' align-center'  : ''));
    }

    public function getFieldMapping($templatePath)
    {
        return str_replace(['COLUMN', 'NAME'], [$this->column, $this->name], file_get_contents($templatePath));
    }

    public function changeToManyToOneField($manyToOneParams)
    {
        $this->type = FieldType::create('foreignKey', $this->app);

        if(is_array($manyToOneParams['label'])) {
            $manyToOneParams['label'] = $this->model->generateConcatenatedColumn(
                $manyToOneParams['label'],
                $manyToOneParams['alias']
            );
            $manyToOneParams['concat'] = true;
        }

        $this->manyToOne = $manyToOneParams;
        $this->formatted = true;
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



    public function changerEnChampParametre($type, $lignes = [])
    {
        $this->type = FieldType::create('parametre', $this->app);
        $this->parametre = new \stdClass();
        $this->parametre->type = $type;
        $this->parametre->lignes = [];
        foreach ($lignes as $ligne) {
            $this->parametre->lignes[] = [$ligne['code'], $ligne['valeur']];
        }
    }

    public function getParametre($property = '')
    {
        if ($property)
            return $this->parametre->$property;
        else
            return $this->parametre;
    }

}