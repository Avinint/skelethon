<?php

use Core\Config;
use Core\Field;
use Core\ModelMaker;

class E2DModelMaker extends ModelMaker
{
    private $modalTitle = [];

    public function __construct($module, $name, $creationMode = 'generate',  array $params = [])
    {
        $this->creationMode = $creationMode;
        $this->fieldClass = E2DField::class;
        $this->applyChoicesForAllModules = $params['applyChoicesForAllModules'];
        parent::__construct($module, $name, $creationMode, $params);

    }

    /**
     * initialise l'accès à la base de données
     */
    public function setDbParams()
    {
        $this->setDatabaseAccess(E2DDatabaseAccess::getDatabaseParams());
    }

    public function getTableHeaders()
    {
        $actionHeader = empty($this->actions) ? '' : str_repeat("\x20", 16).'<th class="centre">Actions</th>'.PHP_EOL;

        return $actionHeader.implode(PHP_EOL, Field::getTableHeaders());
    }

    /**
     * Details spécifiques au projet
     */
    protected function askSpecifics(): void
    {
        $this->usesMultiCalques = $this->askMulti();
        $this->usesSelect2 = $this->askSelect2();
        $this->usesSwitches = $this->askSwitches();
    }

    private function askMulti()
    {
        $useMulti = $this->moduleConfig['models'][$this->name]['usesMulti'] ?? $this->prompt('Voulez-vous pouvoir ouvrir plusieurs calques en même temps ? (multi/concurrent)', ['o', 'n']) === 'o';
        $this->saveChoiceInConfig('usesMulti', $useMulti, $this->name);

        return $useMulti === 'o';
    }

    private function askSwitches()
    {
        $usesSwitches = $this->moduleConfig['models'][$this->name]['usesSwitches'] ?? $this->prompt('Voulez-vous pouvoir générer des champs switch plutôt que radio pour les booléens ? (switch/radio)', ['o', 'n']) === 'o';
        $this->saveChoiceInConfig('usesSwitches', $usesSwitches, $this->name);

        return $usesSwitches;
    }

    private function askSelect2()
    {
        $useSelect2 = $this->moduleConfig['models'][$this->name]['usesSelect2'] ?? $this->prompt('Voulez-vous utiliser les Select2 pour générer les champs Enum ?', ['o', 'n']) === 'o';
        $this->saveChoiceInConfig('usesSelect2', $useSelect2, $this->name);

        return  $useSelect2;
    }

    private function askSelectAjax()
    {
        $filterIdSuffixes = $this->config->get('associations_start_with_id_only') ?? true;
        $potentialFields = array_filter($this->getViewFieldsByType(['int', 'smallint', 'tinyint']), function($field) use ($filterIdSuffixes) {; return preg_match('/^id_[a-z]*/', $field['column']) || $filterIdSuffixes === false;});

        $this->usesSelectAjax = !empty($potentialFields) && ($this->creationMode === 'addSelectAjax' ||
            $this->prompt('Voulez-vous transformer des champs en selects Ajax ?', ['o', 'n']) === 'o') ;

        if ($this->usesSelectAjax) {
            $askConvertAll = false;
            if (count($potentialFields) > 1) {
                $listNames = implode('', array_map(function($field) { return $this->highlight($field['name'], 'info').PHP_EOL;}, $potentialFields));
                $askConvertAll = $this->prompt('Voulez-vous convertir tous les champs suivants :'.PHP_EOL.$listNames.'en Select Ajax ?', ['o', 'n']) === 'o';
            }

            foreach ($potentialFields as &$field) {
                if ($askConvertAll || $this->prompt('Voulez-vous convertir le champ '.$this->highlight($field['name']).' en Select Ajax ?', ['o', 'n']) === 'o') {
                    $selectAjaxFieldData = $this->getDataForSelectAjaxField($field);

                    if ($selectAjaxFieldData === false) {
                        $this->msg('Champ invalide comme clé étrangère', 'error');
                    } else {
                        $this->fieldClass::changeToSelectAjax($field['column'], $selectAjaxFieldData);
                    }

                }
            }
        }
    }

    public function getModalTitle()
    {
        if (empty($this->modalTitle)) {
            return '';
        }

        return PHP_EOL.str_repeat("\x20", 8).'$this->aTitreLibelle = [\''.implode(',', $this->modalTitle).'\'];'.PHP_EOL;
    }

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

    private function getDataForSelectAjaxField($field)
    {
        $childTable = str_replace('id_', '', $field['column']);
        $sRequete = 'SHOW tables  FROM ' . $this->dBName. ' LIKE \''.$childTable.'\'';
        $tables = $this->query($sRequete);

        $tableExists = count($tables) > 0;

        if ($tableExists) {
            $displayField = '';
            $primaryKeyExists = false;
            $sRequete = "show columns from $childTable" ;
            $columns = $this->query($sRequete);
            foreach ($columns as $column) {

                if ($column->Field === $field['column'] && 'PRI' === $column->Key) {
                    $primaryKeyExists = true;
                }

                if (strpos($column->Field, 'name') > 0) {
                    $displayField = $column->Field;
                }

                if (strpos($column->Field, 'name') === 0) {
                    $displayField = $column->Field;
                }

                if (strpos($column->Field, 'nom') > 0) {
                    $displayField = $column->Field;
                }

                if (strpos($column->Field, 'nom') === 0) {
                    $displayField = $column->Field;
                }

                if ($column->Field === 'label') {
                    $displayField = $column->Field;
                }

                if ($column->Field === 'libelle') {
                    $displayField = $column->Field;
                }

                if ($column->Field === $childTable) {
                    $displayField = $childTable;
                }
            }

            if ($primaryKeyExists) {
                $alias = strtoupper(substr_replace($childTable, '', 3));
                return ['table' => $childTable, 'pk' => $field['column'], 'label' => $displayField, 'alias' => $alias];
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getSqlSelectFields(): string
    {
        $indent = str_repeat("\x20", 16);
        $fields =  '\''.PHP_EOL. parent::getSqlSelectFields().PHP_EOL.$indent.'\'';

        $fields = str_replace_last(' . \''.PHP_EOL.$indent.'\'', '', $fields);

        return $fields;
    }

    protected function askModifySpecificData()
    {
        $this->askSelectAjax();
    }
}