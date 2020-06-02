<?php

require_once 'BaseFactory.php';
require 'Field.php';
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

    private $alias;
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
        echo PHP_EOL;
        if ($name === '') {
            // TODO regler CAMEL CASE conversions
            $name = readline($this->msg('Veuillez renseigner en snake_case le nom de la '.$this->highlight('table').' correspondant au modèle'.PHP_EOL.' ('.$this->highlight('minuscules', 'warning') . ' et ' . $this->highlight('underscores', 'warning').')'.
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
        if (is_null($this->table)) {
            $this->msg('Erreur: Il faut créer la table \''.$this->name.'\' avant de générer le code', 'error');
            die();
        }
        $this->alias = strtoupper(substr(str_replace('_', '', $this->name), 0, 3));

        foreach ($this->table as $field => $data) {
            if ('PRI' === $data->Key) {
                $this->primaryKey = $data->Field;
                $this->idField = $data->sChamp;
            }

            $params = [
                'pk' => 'PRI' === $data->Key,
                'is_nullable' => $data->Null !== 'NO',
                'enum' => $data->Type
            ];

            if (isset($data->maxLength)) {
                $params['maxLength'] = $data->maxLength;
            }

            new Field(
                $data->sType,
                $data->sChamp,
                $data->Field,
                $data->Default,
                $this->alias,
                $params
            );

            $this->addModalTitle($data);
        }
    }

    private function askActions()
    {
        $actionsDisponibles = ['recherche', 'edition', 'suppression', 'consultation'];
        $actions = [];

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
        return implode(PHP_EOL, Field::getFieldMappings());
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
        return implode(PHP_EOL, Field::getSearchCriteria());
    }

    /**
     * @return array
     */
    public function getValidationCriteria(): string
    {
        return implode(PHP_EOL, Field::getValidationCriteria());
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

        return $actionHeader.implode(PHP_EOL, Field::getTableHeaders());
    }

    /**
     * @return string
     */
    public function getTableColumns()
    {
        return implode(PHP_EOL, Field::getTableColumns());
    }

    public function getColumnNumber()
    {
        return count($this->table);
    }

    public function getViewFields($showIdField = false)
    {
        return Field::getViewFields($showIdField);
    }

    public function getViewFieldsByType($type)
    {
        return array_filter(Field::getViewFields(), function($field) use ($type) {
            if (is_array($type)) {
                return array_contains($field['type'], $type);
            }
            return $field['type'] === $type;
        });
    }

    public function getViewFieldsExcludingType($type)
    {
        return array_filter(Field::getViewFields(), function($field) use ($type) {
            if (is_array($type)) {
                return !array_contains($field['type'], $type);
            }
            return $field['type'] !== $type;
        });
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
        $fields =  implode(','.PHP_EOL, Field::getSelectFields());

        return $fields;
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