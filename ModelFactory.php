<?php

require_once 'BaseFactory.php';
require 'Database.php';

class ModelFactory extends BaseFactory
{
    use Database;
    private $name;
    private $className;
    public $actions;
    private $module;
    private $table = [];
    private $mappingChamps = [];
    protected $primaryKey;
    protected $idField;
    protected $searchCriteria = [];
    protected $validationCriteria = [];
    private $alias;
    private $sqlSelectFields = [];
    private $tableHeaders = [];
    private $tableColumns = [];
    private $viewFields = [];
    private $modalTitle = [];

    protected function __construct($name, $module)
    {
        if (!is_dir('modules')) {
            $this->msg('Répertoire \'modules\' inexistant, veuillez vérifier que vous travaillez dans le répertoire racine de votre projet', 'error');
            throw new Exception();
        }

        $this->module = $module;
        $this->name = $this->askName($name);
        $this->className = $this->conversionPascalCase($this->name);
        $this->actions = $this->askActions();
        $this->usesMultiCalques = $this->askMulti();
        $this->usesSelect2 = $this->askSelect2();
        $this->usesSwitches = $this->askSwitches();
        $this->setDbParams();

        $this->generate();
    }

    private function askName($name = '')
    {
        if ($name === '') {
            // TODO regler CAMEL CASE conversions
            $name = readline($this->msg('Veuillez renseigner en snake_case ('.$this->highlight('minuscules', 'info') . ' et ' . $this->highlight('underscores', 'info').') le nom de la '.$this->highlight('table').' correspondant au modèle'.
                PHP_EOL.'Si vous envoyez un nom de modèle vide, le nom du modèle sera le nom du module : '. $this->frame($this->module->getName(), 'success').''));
        }

        if ($name === '') {
            $name = $this->module->getName();
        }

        return $name;
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
        
        return  $useSelect2 === 'o';
    }

    private function generate()
    {
        $tables = $this->aListeTables();
        $this->table = $tables[$this->name];
        $this->alias = strtoupper(substr(str_replace('_', '', $this->name), 0, 3));
        if (is_null($this->table)) {
            $this->msg('Erreur: Il faut créer la table \''.$this->name.'\' avant de générer le code', 'error');
            die();
        }

        $indent = str_repeat("\x20", 12);
        foreach ($this->table as $field => $data) {
            if ('PRI' === $data->Key) {
                $this->primaryKey = $data->Field;
                $this->idField = $data->sChamp;
            }
            $this->mappingChamps[] = $indent."'$field' => '$data->sChamp',";
            $this->addLabel($data);
            $this->addSelectField($data, $indent);
            foreach ($this->addSearchCriterion($data) as $criterion) {
                $this->searchCriteria[] = $criterion;
            }
            //$this->searchCriteria = array_merge($this->searchCriteria, $this->addSearchCriterion($data));
            $this->validationCriteria[] = $this->addValidationCriterion($data);

            $this->tableHeaders[] = $this->addTtableHeader($data);
            $this->tableColumns[] = $this->addTableColumn($data);

            $this->viewFields[] = $this->addViewField($data);

            $this->addModalTitle($data);
        }
    }

    private function askActions()
    {
        $actionsDisponibles = ['recherche', 'edition', 'suppression', 'consultation'];
        $actions = [];

//        do {
//            $reponse1 =  strtoupper(readline();
//        }
//        while (!in_array($reponse1 , ['N', 'O']));


        $reponse1 = $this->prompt('Voulez vous sélectionner toutes les actions disponibles? ('. implode(', ', array_map([$this, 'highlight'], $actionsDisponibles, array_fill(0, 4, 'info'))).')', ['o', 'n']);

        if ('o' === $reponse1) {
            $actions = $actionsDisponibles;
        } else {
            foreach ($actionsDisponibles as $action) {
                do {
                    $reponse2 =  strtoupper(readline($this->msg('Voulez vous sélectionner l\'action "'. $action.'" ? [O/N]')));
                }
                while (!in_array($reponse2 , ['N', 'O']));

                if ('O' === $reponse2) {
                    $actions[] = $action;
                }
            }
        }

        array_unshift($actions, 'accueil');

        return $actions;
    }

    private function setDbParams()
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

    public function getName() : string
    {
        return $this->name;
    }

    public function getClassName() : string
    {
        return $this->className;
    }

    public function getActions() : array
    {
        return $this->actions;
    }

    public function getMappingChamps() :string
    {
        return implode(PHP_EOL, $this->mappingChamps);
    }

    /**
     * @return string
     */
    public function getPrimaryKey() : string
    {
        return $this->primaryKey;
    }

    /**
     * @return string
     */
    public function getIdField() : string
    {
        return $this->idField;
    }

    /**
     * @return array
     */
    public function getSearchCriteria(): string
    {
        return implode(PHP_EOL, $this->searchCriteria);
    }

    /**
     * @return array
     */
    public function getValidationCriteria(): string
    {
        if (empty($this->validationCriteria)) {
            return str_repeat("\x20", 8).'$aConfig = []'.PHP_EOL;
        }

        return implode(PHP_EOL, $this->validationCriteria);

    }

    /**
     * @return mixed
     */
    public function getAlias()
    {
        return $this->alias;
    }

    public function getTableHeaders()
    {
        $actionHeader = empty($this->actions) ? '' : str_repeat("\x20", 16).'<th class="centre">Actions</th>'.PHP_EOL;
        return $actionHeader.implode(PHP_EOL, $this->tableHeaders);
    }

    /**
     * @return string
     */
    public function getTableColumns()
    {
        return implode(PHP_EOL, $this->tableColumns);
    }

    public function getColumnNumber()
    {
        return count($this->table);
    }

    public function getViewFields($showIdField = false)
    {
        // TODO Fix pour récup id field quelle que soit sa position
        $fields = $this->viewFields;
        if (false === $showIdField && $this->idField === $fields[0]['field']) {
            array_shift($fields);
        }
        return $fields;
    }

    public function getViewFieldsByType($type)
    {
        return array_filter($this->viewFields, function($field) use ($type) {
            if (is_array($type)) {
                return array_contains($field['type'], $type);
            }
            return $field['type'] === $type;
        });
    }

    public function getViewFieldsExcludingType($type)
    {
        return array_filter($this->viewFields, function($field) use ($type) {
            if (is_array($type)) {
                return !array_contains($field['type'], $type);
            }
            return $field['type'] !== $type;
        });
    }

    public function getEnumValues($enum)
    {
        $sValeurs = str_replace('enum(', '', $enum);
        $sValeurs = str_replace(')', '', $sValeurs);
        $sValeurs = str_replace('\'', '', $sValeurs);
        $aValeurs = explode(',', $sValeurs);

        return $aValeurs;
    }

    public function getModalTitle()
    {
        if (empty($this->modalTitle)) {
            return '';
        }

        return PHP_EOL.str_repeat("\x20", 8).'$this->aTitreLibelle = [\''.implode(',', $this->modalTitle).'\'];'.PHP_EOL;
    }

    /**
     * @return array
     */
    public function getSqlSelectFields(): string
    {
        return implode(','.PHP_EOL, $this->sqlSelectFields);
    }
    
    private function addSelectField($data, $indent = '')
    {
        if (array_contains($data->sType, ['date', 'datetime', 'float', 'decimal', 'tinyint'])) {
            $ajoutMappingChampFormate =  "$indent'{$data->Field}_formate' => '{$data->sChamp}Formate',".PHP_EOL;
        }

        $indent .= str_repeat("\x20", 8);

        $this->sqlSelectFields[] = "{$indent}$this->alias.$data->Field";
        if ('PRI' === $data->Key) {
            $this->sqlSelectFields[] = "{$indent}$this->alias.$data->Field nIdElement";
        }
        if ('date' === $data->sType) {
            $this->sqlSelectFields[] = "{$indent}IF($this->alias.$data->Field, DATE_FORMAT($this->alias.$data->Field, \'%d/%m/%Y\'), \'\') AS {$data->Field}_formate";
            $this->mappingChamps[] = $ajoutMappingChampFormate;

        } elseif ('datetime' === $data->sType) {
            $this->sqlSelectFields[] = "{$indent}IF($this->alias.$data->Field, DATE_FORMAT($this->alias.$data->Field, \'%d/%m/%Y à %H\h%i\'), \'\') AS {$data->Field}_formate";
            $this->mappingChamps[] = $ajoutMappingChampFormate;

        } elseif (array_contains($data->sType, array('float', 'decimal'))) {
            $this->sqlSelectFields[] = "{$indent}REPLACE($this->alias.$data->Field, \'.\', \',\') AS {$data->Field}_formate";
            $this->mappingChamps[] = $ajoutMappingChampFormate;
        } elseif ('tinyint' === $data->sType) {

            $this->sqlSelectFields[] = "$indent(CASE WHEN $this->alias.$data->Field = 1 THEN \'oui\' ELSE \'non\' END) AS {$data->Field}_formate";
            $this->mappingChamps[] = $ajoutMappingChampFormate;
        }
    }

    private function addLabel(&$data)
    {
        $data->sLabel = $this->labelize($data->Field);
    }

    private function addSearchCriterion($data)
    {
        $aCriteresRecherche = [];
        $fieldName = "AND $this->alias.$data->Field";

        if (array_contains($data->sType, array('smallint', 'int', 'float', 'decimal', 'double'))) {

            $conditionEquals = $fieldName . ' = ' . $this->addNumberField($data->sChamp, in_array($data->sType, array('smallint', 'int')))  ;

            $aCriteresRecherche[] = $this->addNumberCriterion($data->sChamp, $conditionEquals);

            if (!preg_match('/^nId([A-Z]{1}([a-z]*))$/', $data->sChamp)) {
                $conditionMin = $fieldName . ' >= ' . $this->addNumberField($data->sChamp . 'Min', in_array($data->sType, array('smallint', 'int')));

                $aCriteresRecherche[] = $this->addNumberCriterion($data->sChamp.'Min', $conditionMin);

                $conditionMax = $fieldName . ' <= ' . $this->addNumberField($data->sChamp . 'Max', in_array($data->sType, array('smallint', 'int')));

                $aCriteresRecherche[] = $this->addNumberCriterion($data->sChamp.'Max', $conditionMax);
            }

        } elseif ('tinyint' === $data->sType) {
            $conditionEquals = $fieldName . ' = ' . $this->addNumberField($data->sChamp);

            $aCriteresRecherche[] = $this->addBooleanCriterion($data->sChamp, $conditionEquals);

        } elseif (array_contains($data->sType, array('date', 'datetime')) === true) {
            
            if ($data->sType === 'date') {
                $sSuffixeDebut = '';
                $sSuffixeFin = '';
                $sFormat = ', \'Y-m-d\'';
            } else {
                $sSuffixeDebut = ' 00:00:00';
                $sSuffixeFin = ' 23:59:59';
                $sFormat = ', \'Y-m-d H:i:s\'';
            }

            $whereDebut = $fieldName .' >= \'".addslashes($this->sGetDateFormatUniversel($aRecherche[\''. $data->sChamp.'Debut'.'\']'.$sFormat.")).\"'";
            $aCriteresRecherche[] = $this->addDateCriterion($data->sChamp.'Debut', $whereDebut, $sSuffixeDebut);

            $whereFin = $fieldName .' <= \'".addslashes($this->sGetDateFormatUniversel($aRecherche[\''. $data->sChamp.'Fin'.'\']'.$sFormat.")).\"'";
            $aCriteresRecherche[] = $this->addDateCriterion($data->sChamp.'Fin' , $whereFin, $sSuffixeFin);
        } else {
            $whereIEquals = $fieldName.' LIKE \'".addslashes($aRecherche[\''.$data->sChamp.'\'])."\'';

            $aCriteresRecherche[] = $this->addStringCriterion($data->sChamp, $whereIEquals);
            $whereLike = $fieldName.' LIKE \'%".addslashes($aRecherche[\''.$data->sChamp.'\'])."%\'';
            $aCriteresRecherche[] = $this->addStringCriterion($data->sChamp.'Partiel', $whereLike);
            }

        return $aCriteresRecherche;
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
//        str_repeat("\x20", 12).'if ($aRecherche[\''.$field.'\'] === \'oui\') {'.PHP_EOL.
//        str_repeat("\x20", 16).'$aRecherche[\''.$field."'] = 1;".PHP_EOL.
//        str_repeat("\x20", 12)."} else {".PHP_EOL.
//        str_repeat("\x20", 16).'$aRecherche[\''.$field.'\'] = 0;'.PHP_EOL.
//        str_repeat("\x20", 12)."}".PHP_EOL.
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

    private function addValidationCriterion($data)
    {
        $sCritere = str_repeat("\x20", 8)."\$aConfig['".$data->sChamp.'\'] = array(' . PHP_EOL;
        if ($data->Null == 'NO') {
            $sCritere .=
                str_repeat("\x20", 12).'\'required\' => \'1\','.PHP_EOL.
                str_repeat("\x20", 12).'\'minlength\' => \'1\','.PHP_EOL;
        }

        if (isset($data->nMaxLength)) {
            $nMaxLength = str_replace(' unsigned', '', $data->nMaxLength);
            if (preg_match('/,/', $data->nMaxLength)) {
                $aLength = explode(',', $data->nMaxLength);
                $nMaxLength = 1;
                $nMaxLength += (int)$aLength[0];
                $nMaxLength += (int)$aLength[1];
            }

            $sCritere .= str_repeat("\x20", 12)."'maxlength' => '$nMaxLength'," . PHP_EOL;
        }

        return $sCritere.str_repeat("\x20",8).');' . PHP_EOL;
    }

    /**
     * @param $data
     */
    public function addTtableHeader($data)
    {
        return str_repeat("\x20", 16).'<th id="th_'.$data->Field.'" class="tri">'.$data->sChamp.'</th>';
    }

    public function addTableColumn($data)
    {
        $formate = array_contains($data->sType, array('float', 'decimal', 'date', 'datetime', 'tinyint', 'double')) ? 'Formate' : '';

        $alignment = '';
        if (array_contains($data->sType, array('float', 'decimal', 'int', 'smallint', 'double'))) {
            $alignment = ' align-right';
        }
        if (array_contains($data->sType, array('date', 'datetime', 'enum'))) {
            $alignment = ' align-center';
        }

        return str_repeat("\x20", 16)."<td class=\"{$data->sChamp}$formate{$alignment}\"></td>";
    }

    public function addViewField($data)
    {
        $formate = array_contains($data->sType, array('float', 'decimal', 'date', 'datetime', 'double')) ? 'Formate' : '';
        $result =  ['field' => $data->sChamp.$formate, 'column' => $data->Field, 'label'=> $data->sLabel, 'type'=> $data->sType, 'default' =>  $data->Default ?? '', 'name' => $data->sChamp];

        if ($data->sType === 'tinyint') {
            $result['default'] = $data->Default;
        } elseif ($data->sType === 'enum') {
            $result['enum'] = $this->getEnumValues($data->Type);
        }

        return $result;
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

    /**
     * @param string $name
     * @param $arg2
     * @return ModelFactory
     */
    public static function create(string $name, $arg2) : self
    {
        return parent::getInstance($name, $arg2);
    }

}