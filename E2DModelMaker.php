<?php

use Core\Config;
use Core\Field;
use Core\ModelMaker;

class E2DModelMaker extends ModelMaker
{
    private $modalTitle = [];

    protected function __construct($module, $name, $fieldClass = 'Core\Field',  $applyChoicesForAllModules = '')
    {
        $this->fieldClass = 'E2DField';
        $this->applyChoicesForAllModules = $applyChoicesForAllModules;
        parent::__construct($name, $module, $fieldClass);

    }

    /**
     * initialiser l'accès à la base de données
     */
    protected function setDbParams()
    {
        if (!isset($GLOBALS['aParamsAppli']) || !isset($GLOBALS['aParamsBdd'])) {
            $text = str_replace('<?php', '',file_get_contents('surcharge_conf.php'));
            eval($text);
            $this->hostname = 'localhost';
            $this->username = $GLOBALS['aParamsBdd']['utilisateur'];
            $this->password = $GLOBALS['aParamsBdd']['mot_de_passe'];
            $this->dBName = $GLOBALS['aParamsBdd']['base'];
        }
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
        $useMulti = $this->prompt('Voulez-vous pouvoir ouvrir plusieurs calques en même temps ? (multi/concurrent)', ['o', 'n']);

        return $useMulti === 'o';
    }

    private function askSwitches()
    {
        $usesSwitches = $this->prompt('Voulez-vous pouvoir générer des champs switch plutôt que radio pour les booléens ? (switch/radio)', ['o', 'n']);

        return $usesSwitches === 'o';
    }

    private function askSelect2()
    {
        $useSelect2 = $this->prompt('Voulez-vous utiliser les Select2 pour générer les champs Enum ?', ['o', 'n']);
        $this->saveChoiceInConfig('usesSelect', $useSelect2 === 'o');

        return  $useSelect2 === 'o';
    }

    private function askSelectAjax()
    {
        $filterIdSuffixes = Config::get('main', 'associations_start_with_id_only') ?? true;
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
                return ['table' => $childTable, 'pk' => $field['column'], 'label' => $displayField];
            } else {
                return false;
            }
        }

        return false;
    }

    protected function askModifySpecificData()
    {
        $this->askSelectAjax();
    }
}