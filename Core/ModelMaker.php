<?php

namespace Core;

class ModelMaker extends CommandLineMaker
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

    protected $alias;

    protected $fieldClass;

    protected function __construct($name, $module)
    {
        if (!is_dir('modules')) {
            $this->msg('Répertoire \'modules\' inexistant, veuillez vérifier que vous travaillez dans le répertoire racine de votre projet', 'error');
            throw new Exception();
        }

        $this->module = $module;
        $this->name = $this->askName($name);
        $this->table = $this->getDbTable();
        $this->className = $this->conversionPascalCase($this->name);
        $this->actions = $this->askActions();
        $this->askSpecifics();

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

    private function generate()
    {
        $this->alias = strtoupper(substr(str_replace('_', '', $this->name), 0, 3));

        foreach ($this->table as $field => $data) {
            if ('PRI' === $data->Key) {
                $this->primaryKey = $data->Field;
                $this->idField = $data->sChamp;
            }

            $this->addField($data);

            $this->addModalTitle($data);
        }
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

        new $this->fieldClass(
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

    /**
     *  initialiser l'accès à la base de données
     */
    protected function setDbParams()
    {
        // initialiser la base de données en fonction du fichier config du projet ou la main
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
        return implode(PHP_EOL, $this->fieldClass::getAttributes());
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
        return implode(PHP_EOL, $this->fieldClass::getSearchCriteria());
    }

    /**
     * @return array
     */
    public function getValidationCriteria(): string
    {
        return implode(PHP_EOL, $this->fieldClass::getValidationCriteria());
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

        return $actionHeader.implode(PHP_EOL, $this->fieldClass::getTableHeaders());
    }

    /**
     * @return string
     */
    public function getTableColumns()
    {
        return implode(PHP_EOL, $this->fieldClass::getTableColumns());
    }

    public function getColumnNumber()
    {
        return count($this->table);
    }

    public function getViewFields($showIdField = false)
    {
        return $this->fieldClass::getViewFields($showIdField);
    }

    public function getViewFieldsByType($type)
    {
        return array_filter($this->fieldClass::getViewFields(), function($field) use ($type) {
            if (is_array($type)) {
                return array_contains($field['type'], $type);
            }
            return $field['type'] === $type;
        });
    }

    public function getViewFieldsExcludingType($type)
    {
        return array_filter($this->fieldClass::getViewFields(), function($field) use ($type) {
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
        $fields =  implode(','.PHP_EOL, $this->fieldClass::getSelectFields());

        return $fields;
    }


    protected function getDbTable(): array
    {
        $this->setDbParams();
        $tables = $this->aListeTables();
        if (!isset($tables[$this->name])) {
            $this->msg('Erreur: Il faut créer la table \'' . $this->name . '\' avant de générer le code', 'error');
            die();
        }
        return $tables[$this->name];
    }

    public function getTitre() : string
    {

        return 'Mes '.$this->labelize($this->name).'s';
    }

    /**
     * Details spécifiques au projet
     */
    protected function askSpecifics(): void
    {

    }

}