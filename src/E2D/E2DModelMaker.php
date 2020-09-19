<?php

namespace E2D;

use Core\Config;
use Core\Field;
use Core\ModelMaker;

class E2DModelMaker extends ModelMaker
{
    private $modalTitle = [];

    public function __construct($fieldClass, $module, $name, $creationMode = 'generate',  array $params = [], $databaseAccess)
    {
        $this->applyChoicesForAllModules = $params['applyChoicesForAllModules'];
        parent::__construct($fieldClass, $module, $name, $creationMode, $params, $databaseAccess);

    }

    /**
     * initialise l'accès à la base de données
     */
    public function setDbParams()
    {
        $this->setDatabaseAccess(E2DDatabaseAccess::getDatabaseParams());
    }

    public function getTableHeaders($templatePath)
    {
        $actionHeader = empty($this->actions) ? '' : file_get_contents(str_replace('.', '_actionheader.', $templatePath)).PHP_EOL;
        return $actionHeader.implode(PHP_EOL, array_map(function (Field $field) use ($templatePath) {
            return $field->getTableHeader($templatePath);}, $this->fields));
        //return $actionHeader.implode(PHP_EOL, $this->fieldClass::getTableHeaders());
    }

    /**
     * @return string
     */
     public function getTableColumns($templatePath)
     {
         return implode(PHP_EOL, array_map(function (Field $field) use ($templatePath) {return $field->getTableColumn($templatePath);}, $this->fields));
         //return implode(PHP_EOL, $this->fieldClass::getTableColumns());
     }

    /**
     * Details spécifiques au projet
     */
    protected function askSpecifics(): void
    {
        $this->usesMultiCalques = $this->askMulti();
        $this->usesSelect2 = $this->askSelect2();
        $this->usesSwitches = $this->askSwitches();
        $this->usesNoCallBackListeElement = $this->askCallbackListe();
    }

    private function askCallbackListe()
    {
        $usesNoCallbackListe = $this->config->get('noCallbackListeElenent') ?? $this->prompt('Voulez-vous un template qui n\'utilise pas le callback liste ? (Utile si vous avez des valeurs de recherche par défaut)', ['o', 'n']) === 'o';
        $this->config->saveChoice('noCallbackListeElenent', $usesNoCallbackListe, $this->name);
    }

    private function askMulti()
    {
        $usesMulti = $this->config->get('usesMulti') ?? $this->prompt('Voulez-vous pouvoir ouvrir plusieurs calques en même temps ? (multi/concurrent)', ['o', 'n']) === 'o';
        $this->config->saveChoice('usesMulti', $usesMulti, $this->name);

        return $usesMulti === 'o';
    }

    private function askSwitches()
    {
        $usesSwitches = $this->config->get('usesSwitches') ?? $this->prompt('Voulez-vous pouvoir générer des champs switch plutôt que radio pour les booléens ? (switch/radio)', ['o', 'n']) === 'o';
        $this->config->saveChoice('usesSwitches', $usesSwitches, $this->name);

        return $usesSwitches;
    }

    private function askSelect2()
    {
        $useSelect2 = $this->config->get('usesSelect2') ?? $this->prompt('Voulez-vous utiliser les Select2 pour générer les champs Enum ?', ['o', 'n']) === 'o';
        $this->config->saveChoice('usesSelect2', $useSelect2, $this->name);

        return  $useSelect2;
    }

    private function askAddManyToOneField()
    {
        $this->hasOneRelations = $this->config->get('hasOneRelations', $this->name);
        $foreignKeys = $this->config->get('manyToOne', $this->name) ?? [];
        $potentialFields = $this->getPotentialFields($foreignKeys);

        if ($this->hasOneRelations ?? true) {

            $gotPotential = !empty($potentialFields);
            $needToAsk = is_null($this->hasOneRelations) && ($gotPotential || !empty($foreignKeys));

            if ($needToAsk) {
                $this->hasOneRelations = $this->prompt('Voulez-vous transformer des champs en selects Ajax ?', ['o', 'n']) === 'o';
                $this->config->set('hasOneRelations', $this->hasOneRelations, $this->name);
            }

            if ($this->hasOneRelations) {
                if ($gotPotential) {
                    $this->convertToManyToOneFields($potentialFields);
                }

                foreach ($foreignKeys as $column => $fieldData ) {
                    $this->getFieldByColumn($column)->changeToManyToOneField($fieldData);
                    //$this->fieldClass::changeToManyToOneField($column, $fieldData); TODO remove
                }
            }

        }
    }

    /**
     * Pour @ModelFile
     * @return string
     */
    public function getModalTitle()
    {
        if (empty($this->modalTitle)) {
            return '';
        }

        return PHP_EOL.str_repeat("\x20", 8).'$this->aTitreLibelle = [\''.implode(',', $this->modalTitle).'\'];'.PHP_EOL;
    }

    /**
     * Pour @ModelFile
     * @param $data
     */
    public function addModalTitle($data)
    {
        if ($this->usesMultiCalques) {
            if (array_contains($data->Field, ['nom', 'name', 'surname']) || strpos($data->Field, 'nom') === 0 || strpos($data->Field, 'name') === 0) {
                array_unshift($this->modalTitle, $data->Field);
            } else if (strpos($data->Field, 'nom') !== false || strpos($data->Field, 'name')) {
                array_push($this->modalTitle, $data->Field);
            }
        }
    }

    private function getDataForManyToOneField($field)
    {
        $childTable = str_replace('id_', '', $field['column']);
        $tables = $this->databaseAccess->getSimilarTableList($childTable);
        if (count($tables) > 1) {
            $default = $this->config->has('prefix') && array_contains($this->config->get('prefix') . '_' .  $childTable, $tables) ?
                $this->config->get('prefix') . '_' . $childTable :
                (array_contains($childTable, $tables) ? $childTable : false);
            $childTable = $this->askMultipleChoices('table', $tables, $default, $field['column']);
        } else {
            $childTable = $tables[0];
        }

        $tableExists = count($tables) > 0 && !empty($childTable);
        if ($tableExists) {
            $displayFields = [];
            $primaryKeyExists = false;

            $table = $this->databaseAccess->getTableList()[$childTable];
            
            foreach ($table as $column) {
                $displayFields = $this->getAjaxLabelField($column, $displayFields, $childTable);
                if ($column->Field === $field['column'] && 'PRI' === $column->Key) {
                    $primaryKeyExists = true;
                }
            }
            
            if (empty($displayFields)) {
                $displayFields[] = $field['column'];
            }
            
            $concat = count($displayFields) > 1;

            $childTableAlias = $this->createChildTableAlias($childTable);
            if ($concat) {
                //$childTableAlias = 's'. implode("" , array_map([$this, 'pascalize'], $displayFields));
                $displayField = $displayFields;
            } else {
                $displayField = array_shift($displayFields);
            }

            $idField = 'n' . $this->pascalize($field['column']);

            $alias = $this->generateAlias($childTable);

            if ($primaryKeyExists) {

                return ['table' => $childTable, 'pk' => $field['column'], 'label' => $displayField, 'alias' => $alias, 'childTableAlias' => $childTableAlias,  'id' => $idField];
            } else {
                return false;
            }
        }

        return false;
    }

    public function createChildTableAlias($tableName)
    {
        $prefix = $this->config->get('prefix') ?? '';
        if ($prefix)
            $tableName = str_replace($prefix.'_' , '', $tableName);

        return 's'. $this->pascalize($tableName);
    }

    /**
     * TODO revoir le fonctionmt de cette méthode
     * @return array
     */
    public function getSqlSelectFields($template): string
    {
        $indent = str_repeat("\x20", 16);
        $fields =  '\''.PHP_EOL. parent::getSqlSelectFields($template).PHP_EOL.$indent.'\'';

        //  $fields = str_replace_last(' . \''.PHP_EOL.$indent.'\'', '', $fields);

        return $fields;
    }

    protected function askModifySpecificData()
    {
        $this->askAddManyToOneField();
    }

    /**
     * @param bool $filterIdSuffixes
     * @param array $foreignKeys
     * @return array
     */
    private function getPotentialFields(array $foreignKeys): array
    {
        $filterIdSuffixes = $this->config->get('foreign_keys_start_with_id_only') ?? true;
        $potentialFields = array_filter($this->getViewFieldsByType(['int', 'smallint', 'tinyint']), function ($field) use ($filterIdSuffixes, $foreignKeys) {
            return (preg_match('/^id_[a-z]*/', $field['column']) || $filterIdSuffixes === false) && !array_key_exists($field['column'], $foreignKeys);
        });

        return $potentialFields;
    }

    /**
     * @param $field
     * @param array $manyToOneFieldData
     */
    private function generateManyToOneField($fieldColumn, array $manyToOneFieldData): void
    {
        $this->getFieldByColumn($fieldColumn)->changeToManyToOneField($manyToOneFieldData);
        //$this->fieldClass::changeToManyToOneField($fieldColumn, $manyToOneFieldData); TODD remove
        if (!$this->config->has('manyToOne', $this->name)) {
            $this->config->set('manyToOne', [], $this->name);
        }
        //$this->config->set('hasOneRelations', true);

        $this->config->addTo('manyToOne', $fieldColumn, $manyToOneFieldData , $this->name);
    }

    /**
     * Essaie de deviner quel champ utiliser dans table parente de la relation one2Many
     * @param $column
     * @param array $displayFields
     * @param bool $childTable
     * @return array
     */
    private function getAjaxLabelField($column, array $displayFields, bool $childTable): array
    {
        $champNom = $column->Field === 'nom';
        $champPrenom = $column->Field === 'prenom';
        $fieldName = strpos($column->Field, 'name') === 0;
        $fieldFirstName = strpos($column->Field, 'first_name') !== false || strpos($column->Field, 'firstname') !== false;
        if ($champNom || $champPrenom) {
            if ($champNom) {
                $displayFields[] = $column->Field;
            }
            if ($champPrenom) {
                $displayFields[] = $column->Field;
            }
        } elseif ($column->Field === 'libelle') {
            $displayFields[] = $column->Field;
        } elseif ($column->Field === 'titre') {
            $displayFields[] = $column->Field;
        } elseif ($column->Field === 'nom') {
            $displayFields[] = $column->Field;
        } elseif ($fieldName || $fieldFirstName) {
            if ($fieldName) {
                $displayFields[] = $column->Field;
            }
            if ($fieldFirstName) {
                $displayFields[] = $column->Field;
            }
        } elseif ($column->Field === 'label') {
            $displayFields[] = $column->Field;
        } elseif ($column->Field === 'title') {
            $displayFields[] = $column->Field;

        } elseif (strpos($column->Field, 'name') > 0) {
            $displayFields[] = $column->Field;
        } elseif ($column->Field === $childTable) {
            $displayFields[] = $childTable;
        }
        return $displayFields;
    }

    /**
     * @param array $potentialFields
     *
     */
    private function convertToManyToOneFields(array $potentialFields)
    {
        $listNames = implode('', array_map(function ($field) {
            return $this->highlight($field['name'], 'info') . PHP_EOL;
        }, $potentialFields));
        $askConvertAll = $this->prompt('Voulez-vous convertir tous les champs suivants :' . PHP_EOL . $listNames . 'en Select Ajax ?', ['o', 'n']) === 'o';
        foreach ($potentialFields as &$field) {
            $manyToOneFieldData = $this->getDataForManyToOneField($field);
            if ($manyToOneFieldData === false) {
                $this->msg('Champ invalide comme clé étrangère', 'error');
            } else {
                if ($askConvertAll || $this->prompt('Voulez-vous convertir le champ ' . $this->highlight($field['name']) . ' en Select Ajax ?', ['o', 'n']) === 'o') {
                    $this->generateManyToOneField($field['column'], $manyToOneFieldData);
                }
            }
        }
    }

    /**
     * @param string $alias
     * @return string
     */
    protected function generateAlias(string $alias): string
    {
        if ($this->config->has('prefix')) {
            if (strpos($alias, $this->config->get('prefix')) === 0) {
                $alias = str_replace($this->config->get('prefix') . '_', '', $alias);
            }
        }

        if (strpos($alias, '_') < 2) {
            str_replace_first('_', '', $alias);
        }

        if (($position = strpos($alias, '_')) === false) {
            $alias = strtoupper(substr_replace($alias, '', 3));
        } else {
            $lastCharacter = $alias[$position + 1];
            $alias = strtoupper(substr_replace($alias, '', 2) . $lastCharacter);
        }
        return $alias;
    }

    public function getJoins(string $template)
    {
        $joinText = '';
//        $joins = [];
        $joinList = $this->config->get('manyToOne');

        if (!empty($joinList)) {
            $joins = array_map(function($join) use ($template) {
                return str_replace(['FKTABLE', 'FKALIAS', 'ALIAS', 'FK'],
                    [$join['table'], $join['alias'], $this->getAlias(), $join['pk']], $template);
            }, $joinList);

            $joinText = PHP_EOL.implode(PHP_EOL, $joins);
        }

        return $joinText;
    }

    /**
     * @param array $column
     * @param string $alias
     * @return string
     */
    public function generateConcatenatedColumn(array $columns, $alias = ''): string
    {
        if ($alias !== '') {
            $alias = $alias. '.';

            $columns = array_map(function($part) use ($alias) {return $alias.$part;}, $columns);
        }
        return "CONCAT_WS(\' \', " . implode(", ",  $columns) . ')';
    }
}