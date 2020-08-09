<?php

namespace Core;

abstract class ModelMaker extends BaseMaker
{
    protected $name;
    protected $className;
    protected $tableName;
    /**
     * @var DatabaseAccess
     */
    protected $databaseAccess;
    public $actions;
    protected $module;
    private $table = [];
    private $mappingChamps = [];
    protected $fields;
    protected $primaryKey;
    protected $idField;

    protected $alias;

    protected $fieldClass;


    public function __construct($fieldClass, $module, $name, $mode, array $params = [], FileManager $fileManager = null)
    {
        parent::__construct($fileManager);
        $this->setConfig($params);
        static::$verbose = $this->config->get('verbose') ?? true;
        $this->creationMode = $mode;
        $this->fieldClass = $fieldClass;
        $this->module = Field::$module = $module;
        $this->name = Field::$model = $this->askName($name);

        $this->setClassName($this->name);

        $this->actions = $this->askActions();

        $this->askSpecifics();
    }

    private function askName($name = '')
    {
        echo PHP_EOL;
        if ($name === '') {
            $name = readline($this->msg('Veuillez renseigner en snake_case le nom de la '.$this->highlight('table').' correspondant au modèle'.PHP_EOL.' ('.$this->highlight('minuscules', 'warning') . ' et ' . $this->highlight('underscores', 'warning').')'.
                PHP_EOL.'Si vous envoyez un nom de modèle vide, le nom du modèle sera le nom du module : '. $this->frame($this->module, 'success').''));
        }

        if ($name === '') {
            $name = $this->module->getName();
        }

        return $name;
    }


    private function askTableName()
    {
        $prefix = $this->config->get('prefix') ?? null;
        if (!isset($prefix) && ($this->config->get('usesPrefix') ?? true)) {
            $prefix = $this->askPrefix();
        }
        if (isset($prefix)) {
            $tempTableName = $prefix . '_'. $this->name;
        } else {
            $tempTableName = $this->name;
        }

        $tableName = $this->config->get('tableName') ?? readline($this->msg('Si le nom de la table en base est différent de '. $this->highlight($tempTableName , 'success'). ' entrer le nom de la table :').'');

        if (empty($tableName)) $tableName = $tempTableName;

        if (!$this->config->has('tableName')) {
            $this->config->set('tableName', $tableName, $this->name);
        }

        $this->tableName = $tableName;
    }


    public function generate()
    {
        $this->fields = [];
        $this->alias =  $this->generateAlias($this->tableName);
        foreach ($this->table as $field => $data) {
            if ('PRI' === $data->Key) {
                $this->primaryKey = $data->Field;
                $this->idField = $data->sChamp;
            }

            $this->addField($data);
            $this->addModalTitle($data);
        }

        $this->askModifySpecificData();
    }

    private function addField($data)
    {
        $params = [
            'pk' => 'PRI' === $data->Key,
            'is_nullable' => $data->Null !== 'NO',
            'enum' => $data->Type
        ];

        if (isset($data->maxLength)) {
            $params['maxLength'] = $data->maxLength;
        }

        if (isset($data->step)) {
            $params['step'] = $data->step;
        }

        $this->fields[$data->Field] = new $this->fieldClass(
            $data->sType,
            $data->sChamp,
            $data->Field,
            $data->Default,
            $this->alias,
            $params
        );
    }

    private function askActions()
    {
        if (    $this->config->has('actions')) {
            return $this->config->get('actions');
        } else {
            $actionsDisponibles = ['recherche', 'edition', 'suppression', 'consultation'];
            $actions = [];
            $reponse1 = $this->prompt('Voulez vous sélectionner toutes les actions disponibles? (' . implode(', ', array_map([$this, 'highlight'], $actionsDisponibles, array_fill(0, 4, 'info'))) . ')', ['o', 'n']);

            if ('o' === $reponse1) {
                $actions = $actionsDisponibles;
            } else {
                foreach ($actionsDisponibles as $action) {
                    do {
                        $reponse2 = strtoupper(readline($this->msg('Voulez vous sélectionner l\'action "' . $action . '" ? [O/N]')));
                    } while (!in_array($reponse2, ['N', 'O']));

                    if ('O' === $reponse2) {
                        $actions[] = $action;
                    }
                }
            }

            array_unshift($actions, 'accueil');

            if (!empty($actions)) {
                $this->saveChoiceInConfig('actions', $actions, $this->name);
            }

            return $actions;
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

    public function getAttributes() :string
    {
        return  implode(PHP_EOL, array_map(function (Field $field) {return $field->getFieldMapping();}, $this->fields));
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
        return implode(PHP_EOL, array_map(function (Field $field) {return $field->getSearchCriterion();}, $this->fields));
//        return implode(PHP_EOL, $this->fieldClass::getSearchCriteria()); TODO remove
    }

    /**
     * @return array
     */
    public function getValidationCriteria(): string
    {
        return implode(PHP_EOL, array_map(function (Field $field) {return $field->getValidationCriterion();}, $this->fields));
//        return implode(PHP_EOL, $this->fieldClass::getValidationCriteria()); TODO remove
    }

    public function getViewFields($showIdField = false)
    {
        return array_map(function (Field $field) {
            return $field->getViewField();}, array_filter($this->fields, function ($field) use ($showIdField) { return !$field->isPrimaryKey || $showIdField;}));
//        return $this->fieldClass::getViewFields($showIdField); TODO remove
    }

    public function getViewFieldsByType($type)
    {
        return array_filter($this->getViewFields(), function ($field) use ($type) {
            if (is_array($type)) {
                return array_contains($field['type'], $type);
            }
            return $field['type'] === $type;
        });
    }

    public function getViewFieldsExcludingType($type)
    {
        return array_filter($this->getViewFields(), function ($field) use ($type) {
            if (is_array($type)) {
                return !array_contains($field['type'], $type);
            }
            return $field['type'] !== $type;
        });
    }

    /**
     * @return array
     */
    public function getSqlSelectFields(): string
    {
        //$fields =  implode(','.PHP_EOL, $this->fieldClass::getSelectFields());
//        return $fields;
        return  implode(','.PHP_EOL, array_map(function (Field $field) {return $field->getSelectField();}, $this->fields));
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

        return  $actionHeader.implode(PHP_EOL, array_map(function (Field $field) {return $field->getTableHeader();}, $this->fields));

        //return $actionHeader.implode(PHP_EOL, $this->fieldClass::getTableHeaders());
    }


    /**
     * @return string
     */
    public function getTableColumns()
    {
        return implode(PHP_EOL, array_map(function (Field $field) {return $field->getTableColumn();}, $this->fields));
        //return implode(PHP_EOL, $this->fieldClass::getTableColumns());
    }

    public function getColumnNumber()
    {
        return count($this->table);
    }

    public function setDbTable(): void
    {
        $this->askTableName();
        $table = $this->databaseAccess->getTable($this->tableName);
        if (null === $table) {
            $this->msg('Erreur: Il faut créer la table \'' . $this->name . '\' avant de générer le code', 'error');
            $this->config->set('tableName', null, $this->name);
            die();
        }
        $this->table = $table;
    }

    public function getTitre() : string
    {
        return 'Mes '.$this->labelize($this->name).'s';
    }

    /**
     * Ask specific questions
     */
    abstract protected function askSpecifics(): void;
    // pose des questions spécifiques au projet


    abstract protected function askModifySpecificData();

    /**
     * @return DatabaseAccess
     */
    public function getDatabaseAccess(): DatabaseAccessInterface
    {
        return $this->databaseAccess;
    }

    /**
     * @param DatabaseAccess $databaseAccess
     */
    public function setDatabaseAccess(DatabaseAccessInterface $databaseAccess): void
    {
        $this->databaseAccess = $databaseAccess;
    }
    // modifie certains champs en fonction des choix de l'utilisateur aprèz intiialisation
    private function setClassName(string $name)
    {
        if ($prefix = $this->config->get('prefix') ?? '') {
            $name = str_replace_first($prefix, '', $name);
        }

        $this->className = $this->pascalize($name);
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    protected function generateAlias(string $alias): string
    {
       return strtoupper(substr(str_replace('_', '', $this->table), 0, 3));
    }

    private function askPrefix()
    {
        $prefix = null;
        $reponse1 = $this->prompt("Voulez vous utiliser un prefix dans votre projet?", ['o', 'n']) === 'o';
        if ($reponse1) {
            $prefix = readline($this->msg('Entrer le préfix lié au projet:'));
            $scope = $this->prompt("Voulez vous affecter le prefix au model (1), au module (2) ou au projet (3)?", ['1', '2', '3']);
            if ($scope === '1')
                $this->config->set('prefix', $prefix, $this->name);
            elseif ($scope === '2')
                $this->config->set('prefix', $prefix);
            elseif ($scope === '3')
                $this->config->set('prefix', $prefix, null, true);


        }

        return $prefix;
    }

    protected function getFieldByColumn($columnName)
    {
        if (!isset($this->fields[$columnName])) {
            return false;
        }
        return $this->fields[$columnName];
    }

}