<?php

use Core\Field;
use Core\ModelMaker;

class E2DModelMaker extends ModelMaker
{
    private $modalTitle = [];

    protected function __construct($name, $module, $fieldClass = 'Core\Field')
    {
        $this->fieldClass = 'E2DField';
        parent::__construct($name, $module, $fieldClass);
    }

    /**
     * @param $data
     */
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

        new E2DField(
            $data->sType,
            $data->sChamp,
            $data->Field,
            $data->Default,
            $this->alias,
            $params
        );
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

        return  $useSelect2 === 'o';
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
}