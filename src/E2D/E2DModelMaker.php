<?php

namespace E2D;

use Core\Field;
use Core\ModelMaker;

class E2DModelMaker extends ModelMaker
{
    use E2DManyToOneMaker;

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
    }

    /**
     * @return string
     */
     public function getTableColumns($templatePath)
     {
         return implode(PHP_EOL, array_map(function (Field $field) use ($templatePath) {return $field->getTableColumn($templatePath);}, $this->fields));
     }

    /**
     * Questions sur details spécifiques au type de projet généré nécessaire à la génération, posées dans le constructeur.
     */
    protected function askSpecifics(): void
    {
        $this->usesMultiCalques = $this->askMulti();
        $this->usesSelect2 = $this->askSelect2();
        $this->usesSwitches = $this->askSwitches();
        $this->usesNoCallBackListeElement = $this->askCallbackListe();
    }

    private function askMulti() : bool
    {
        return $this->askBool('usesMulti', 'Voulez-vous pouvoir ouvrir plusieurs calques en même temps ? (multi/concurrent)');
    }

    private function askSelect2()
    {
        return $this->askBool('usesSelect2', 'Voulez-vous utiliser les Select2 pour générer les champs Enum ?');
    }

    private function askSwitches() : bool
    {
        return $this->askBool('usesSwitches', 'Voulez-vous pouvoir générer des champs switch plutôt que radio pour les booléens ? (switch/radio)');
    }

    private function askCallbackListe()
    {
        return $this->askBool('noCallbackListeElenent', 'Voulez-vous un template qui n\'utilise pas le callback liste ? (Utile si vous avez des valeurs de recherche par défaut)');
    }

    /**
     *  Commee askSpécifics mais requiert que tous les champs du modèle soit généré, questions posée à la fin de la génération
     */
    protected function askModifySpecificData()
    {
        $this->usesTinyMCE = $this->config->get('champsTinyMCE') ?? $this->askChampTinyMCE();
        $this->askAddManyToOneField();
    }


    /**
     * @return array
     */
    private function askChampTinyMCE()
    {
        $champsTexte = $this->getViewFieldsByType('text');
        $champsSelectionnes = [];

        foreach ($champsTexte as $unChamp) {
                $unChamp = $unChamp['column'];
                $reponse = $this->prompt('Voulez vous transformer le champ "' . $unChamp . '" en champs tinyMCE ?', ['o', 'n']);

            if ('o' === $reponse) {
                $champsSelectionnes[] = $unChamp;
            }
        }

        if (!empty($champsSelectionnes)) {
            $this->config->set('champsTinyMCE', $champsSelectionnes, $this->name);
        } else {
            $this->config->set('champsTinyMCE', false, $this->name);
        }

        return $champsSelectionnes;
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
}