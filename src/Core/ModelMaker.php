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
    private $actions;
    protected string $module;
    private $table = [];
    private $mappingChamps = [];
    /**
     * @var Field[] $fields
     */
    protected $fields;
    protected $primaryKey;
    protected $idField;
    protected $alias;

    protected $fieldClass;
    protected $actionsDisponibles = ['recherche', 'edition', 'suppression', 'consultation'];


    public function __construct($fieldClass, string $module, $name, $mode, App $app)
    {
        $this->name = $name;
        $this->app = $app;
        $app->setModeleMaker($this);
        $this->setConfig($app->getConfig());

        $this->databaseAccess = $app->getDatabaseAccess();

        static::$verbose = $this->config->get('verbose') ?? true;
        $this->creationMode = $mode;
        $this->fieldClass = $fieldClass;
        $this->module = Field::$module = $module;

        $this->setClassName($this->name);
        $this->setDbTable();

        $this->actions = $this->askActions();

        $this->askSpecifics();
    }

    private function askTableName()
    {
        if ($this->config->has('tableName')) {
            $this->tableName = $this->config->get('tableName');
            return;
        }

        $prefix = $this->config->get('prefix') ?? $this->askPrefix();

        $tempTableName = ($prefix ? $prefix . '_' : '') . $this->name;

        $tableName = $this->config->get('tableName') ?? readline($this->msg('Si le nom de la table en base est différent de '. $this->highlight($tempTableName , 'success'). ' entrer le nom de la table :').'');

        if (empty($tableName)) $tableName = $tempTableName;

        $this->config->set('tableName', $tableName, $this->name);

        $this->tableName = $tableName;
    }


    public function generate()
    {
        $this->fields = [];
        $this->alias =  $this->generateAlias($this->tableName);
        foreach ($this->table as $field => $data) {
            if ('PRI' === $data->Key) {
                $this->primaryKey = $data->Field;
                $data->sType = 'primaryKey';
                $this->idField = $data->sChamp;
            }

            $this->addField($data);

            $this->addModalTitle($data);
        }
        $this->config->get('champs') ?? $this->askFieldsPerView();
        $this->askModifySpecificData();
    }

    private function addField($data)
    {
        $params = [
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
            $this,
            $params
        );

        if ($this->config->has('champs', $this->name)) {
            $this->fields[$data->Field]->setViews($this->config->get('champs')[$data->Field]);
        }

    }

    /**
     * Demande quelles actions générer pour ce modèle dans le domaine
     * @return string[]
     */
    private function askActions() : array
    {
        if ($this->config->has('actions')) {
            $actions = $this->config->get('actions');
        } else {
            $reponse1 = $this->prompt('Voulez vous sélectionner toutes les actions disponibles? (' . implode(', ', array_map([$this, 'highlight'], $this->actionsDisponibles, array_fill(0, 4, 'info'))) . ')', ['o', 'n']);

            if ('o' === $reponse1) {
                $actions = $this->actionsDisponibles;
            } else {
                $actions = [];
                foreach ($this->actionsDisponibles as $action) {
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
                $this->config->saveChoice('actions', $actions, $this->name);
            }

//            return $actions;
        }

        return $this->initializeActions($actions);
    }

    protected function initializeActions($actions)
    {
        return $actions;
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

    public function hasAction($action) : bool
    {
        return isset($this->actions[$action]);
    }

    public function hasActions(array $actions) : bool
    {
        return array_contains_array($actions, $this->actions,  ARRAY_ANY);
    }

    public function getAttributes($template, $view = '') :string
    {
        return implode(PHP_EOL, array_map(function (Field $field)  use ($template) {return $field->getFieldMapping($template);}, $this->getFields('base')));
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

    /**`
     * Retourne la liste des champs en fonction de la vue et autres paramètres
     * @param false $showIdField
     * @return Field[]
     */
    public function getFields($view = '',  $type = '', $showIdField  = false) : array
    {
        return array_filter($this->fields, function ($field) use ($view, $type, $showIdField) {
            return $this->filterFieldsAccordingToViewOrType($field, $view, $type, $showIdField);
        });
    }

    /**
     *  Filtre le champs en fonction de la vue et du type et si pas précisé si on veut afficher le champ id
     * @param Field $field
     * @param string|array $view
     * @param $type
     * @param bool $showIdField
     * @return bool
     */
    public function filterFieldsAccordingToViewOrType(Field $field, $view = '', $type = '', bool $showIdField) : bool
    {
        return ($view === '' || $field->hasView($view))
        && (($type === '' &&  (!$field->isPrimaryKey || $showIdField)) || $field->is($type));
    }

    /**
     * @return array
     */
    public function getSearchCriteria($path): string
    {
        return implode(PHP_EOL, array_filter(array_map(function (Field $field) use($path) {return $field->getSearchCriterion($path);}, $this->getFields('recherche'))));
    }

    /**
     * @return array
     */
    public function getValidationCriteria($path): string
    {
        return implode(PHP_EOL, array_filter(array_map(function (Field $field) use ($path) {return $field->getValidationCriterion($path);}, $this->getFields('edition'))));
    }

    /**
     * @param string $view
     * @param $type
     * @param bool $showIdField
     * @return array|array[]
     */
    public function getViewFields(string $view = '',  $type = '', bool $showIdField = false)
    {
        return array_map(function (Field $field) {
            return $field->getViewField();}, $this->getFields($view, $type, $showIdField));
    }

    /**
     * @return array|Field[]
     */
    public function getInsertFields()
    {
        return array_map(function (Field $field) {
            return $field;}, array_filter($this->getFields('edition'), function ($field)  { return !$field->isPrimaryKey;}));
    }

    // TODO rendre liste de champs parametrrables independamment de getInsertFields
    public function getUpdateFields()
    {
        return $this->getInsertFields();
    }

    public function getFieldsByType($type, $view = '')
    {
        return $this->getFields($view, $type);
    }

    public function getFieldsExcludingType($type, $view = '')
    {
        return array_filter($this->getFields($view), function ($field) use ($type) {
            return !$field->is($type);
        });
    }

    /**
     * @return array
     */
    public function getSqlSelectFields($template): string
    {
        return  implode(','.PHP_EOL, array_map(function (Field $field) use ($template) {return $field->getSelectField($template);}, $this->getFields('base', '', true)));
    }

    /**
     * @return mixed
     */
    public function getAlias()
    {
        return $this->alias;
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

        $this->addSpecificActions();

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
        $prefix = readline($this->msg('Renseigner le prefix du projet ou laisser vide'));

        if (!empty($prefix)) {
            $scope = $this->prompt("Voulez vous affecter le prefix au model (1), au module (2) ?", ['1', '2']);
            if ($scope === '1')
                $this->config->set('prefix', $prefix, $this->name);
            elseif ($scope === '2')
                $this->config->set('prefix', $prefix);

        } else {
            $this->config->set('prefix', '', $this->name);
        }

        return $prefix;
    }

    protected function getFieldByColumn($columnName)
    {
        return $this->fields[$columnName] ?? false;
    }

    /**
     * @param $param
     * @param $message
     * @return bool
     */
    protected function askBool($param, $message, $emptyArray = false) : bool
    {
        if ($this->config->has($param)) {
            $res = $this->config->get($param);
        } else {
            $res = $this->prompt($message, ['o', 'n']) === 'o';
            $this->config->saveChoice($param, $res, $this->name, $emptyArray);
        }

        return $res;
    }

    /**
     * Pour demander a l'utilisateur dans quelles vues le champ apparait et sauvegarder cette information également cette information
     * @return array
     */
    public function askFieldsPerView()
    {
        $listeChamps = [];
        $reponseFiltrageChamps = $this->prompt('Voulez vous sélectionner quels champs seront utilisés dans chaque vue ?',  ['o', 'n']) === 'o';
        $this->msg("Le questionnaire vous permettra également de renseigner quels champs sont liés à une action spéciale");
        if ($reponseFiltrageChamps) {
            foreach ($this->fields as $field) {
                $views = $field->askViews(array_keys($this->actions));
                if (isset($views))
                    $listeChamps[$field->getColumn()] = $views;
            }

            $this->config->set('champs', $listeChamps, $this->name);
        } else {
            $this->config->set('champs', false, $this->name);
        }

        return $listeChamps;
    }


}