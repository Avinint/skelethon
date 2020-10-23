<?php

namespace E2D;

use Core\Field;
use Core\ModelMaker;

class E2DModelMaker extends ModelMaker
{
    use E2DManyToOneMaker;

    private $modalTitle = [];

    public function __construct($fieldClass, $module, $name, $creationMode = 'generate', $app)
    {
        parent::__construct($fieldClass, $module, $name, $creationMode, $app);

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
        $actionHeader = empty($this->actions) ? '' : file_get_contents(str_replace('.', '_actionheader.', $templatePath)) . PHP_EOL;
        return $actionHeader . implode(PHP_EOL, array_map(function (Field $field) use ($templatePath) {
                return $field->getTableHeader($templatePath);
            }, $this->getFields('liste')));
    }

    /**
     * @return string
     */
    public function getTableColumns($templatePath)
    {
        return implode(PHP_EOL, array_map(function (Field $field) use ($templatePath) {
            return $field->getTableColumn($templatePath);
        }, $this->getFields('liste')));
    }

    /**
     * Questions sur details spécifiques au type de projet généré nécessaire à la génération, posées dans le constructeur.
     */
    protected function askSpecifics() : void
    {
        $this->usesMultiCalques           = $this->askMulti();
        $this->usesSelect2                = $this->askSelect2();
        $this->usesSwitches               = $this->askSwitches();
        $this->usesNoCallBackListeElement = $this->askCallbackListe();
        $this->usesCallbackListeLigne     = $this->askCallbackListeLigne();
        $this->usesPagination             = $this->askPagination();
        $this->usesSecurity               = $this->config->get('updateSecurity') ?? $this->askGenerateSecurity();
    }

    private function askMulti() : bool
    {
        return $this->askBool('usesMulti', 'Voulez-vous pouvoir ouvrir plusieurs calques en même temps ? (multi/concurrent)');
    }

    private function askSelect2() : bool
    {
        return $this->askBool('usesSelect2', 'Voulez-vous utiliser les Select2 pour générer les champs Enum ?');
    }

    private function askSwitches() : bool
    {
        return $this->askBool('usesSwitches', 'Voulez-vous pouvoir générer des champs switch plutôt que radio pour les booléens ? (switch/radio)');
    }

    private function askCallbackListe() : bool
    {
        return $this->askBool('noCallbackListeElenent', 'Voulez-vous un template qui n\'utilise pas le callback liste ? (Utile si vous avez des valeurs de recherche par défaut)');
    }

    private function askCallbackListeLigne(): bool
    {
        return $this->askBool('usesCallbackListeLigne', 'Voulez-vous un template qui utilise un callback pour personnaliser les lignes de liste (callbackListeLigne) ?');
    }

    private function askPagination() : bool
    {
        return $this->askBool('usesPagination', 'Souhaitez vous utiliser la pagination ?');
    }

    private function askGenerateSecurity()
    {
        $reponse = (int)$this->prompt("Voulez-vous générer le fichier security.yml? (1), afficher les modifications(3), bloquer la génération de fichier sécurité (3)", [1, 2, 3]);
        if ($reponse === 1)
            $this->config->set('usesSecurity', 'generate', $this->name);
        elseif ($reponse === 2)
            $this->config->set('usesSecurity', 'print', $this->name);
        elseif ($reponse === 3)
            $this->config->set('usesSecurity', false, $this->name);
    }

    /**
     *  Commee askSpécifics mais requiert que tous les champs du modèle soit générés, questions posées à la fin de la génération
     */
    protected function askModifySpecificData()
    {
        $this->usesTinyMCE = $this->config->get('champsTinyMCE') ?? $this->askChampsTinyMCE();
        $this->askAddManyToOneField();
        $this->avecChampsParametres = $this->askChampsParametres();
    }

    /**
     * @return array
     */
    private function askChampsTinyMCE()
    {
        $champsTexte        = $this->getFields('', 'text');
        $champsSelectionnes = [];

        foreach ($champsTexte as $unChamp) {
            $reponse = $this->prompt('Voulez vous transformer le champ "' . $unChamp->getColumn() . '" en champs tinyMCE ?', ['o', 'n']);

            if ('o' === $reponse) {
                $champsSelectionnes[] = $unChamp->getColumn();
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
    public function getSqlSelectFields($template) : string
    {
        $indent = str_repeat("\x20", 16);
        $fields = '\'' . PHP_EOL . parent::getSqlSelectFields($template) . PHP_EOL . $indent . '\'';

        return $fields;
    }

    /**
     * @param string $alias
     * @return string
     */
    protected function generateAlias(string $alias) : string
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
            $alias         = strtoupper(substr_replace($alias, '', 2) . $lastCharacter);
        }
        return $alias;
    }

    public function getJoins(string $template)
    {
        $joinText = '';
        $joinList = $this->config->get('manyToOne');

        if (!empty($joinList)) {
            $joins = array_map(function ($join) use ($template) {
                return str_replace(['FKTABLE', 'FKALIAS', 'ALIAS', 'FK'],
                    [$join['table'], $join['alias'], $this->getAlias(), $join['pk']], $template);
            }, $joinList);

            $joinText = PHP_EOL . implode(PHP_EOL, $joins);
        }

        return $joinText;
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

        return PHP_EOL . str_repeat("\x20", 8) . '$this->aTitreLibelle = [\'' . implode(',', $this->modalTitle) . '\'];' . PHP_EOL;
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
            } elseif (strpos($data->Field, 'nom') !== false || strpos($data->Field, 'name')) {
                array_push($this->modalTitle, $data->Field);
            }
        }
    }

    /**
     * @return bool
     */
    protected function askChampsParametres()
    {
        if ($this->config->has('avecChampsParametres') && !$this->config->get('avecChampsParametres')) {
            return false;
        }

        if ($this->config->has('avecChampsParametres') && $this->config->has('champsParametres')) {
            return $this->initialiserParametresDepuisConfig();
        }

        $reponse1 = $this->askBool('avecChampsParametres', 'Voulez vous transformer certains champs en paramètre?');
        if ($reponse1) {
            $this->convertirChampsEnParametres();
        }

        $presenceModuleParametre = count(glob(getcwd(). '/modules/parametre')) > 0;
        $this->addModuleParametreIfMissing($presenceModuleParametre);

        return $reponse1;
    }

    /**
     * @return array|mixed|null
     */
    protected function initialiserParametresDepuisConfig()
    {
        $champsParametres = $this->config->get('champsParametres') ?: [];

        foreach ($champsParametres as $colonne => $parametre) {
            $field     = $this->getFieldByColumn($colonne);
            $type      = key($parametre);
            $parametre = $parametre[$type];
            $lignes    = [];
            foreach ($parametre as $code => $valeur) {
                $lignes[] = ['code' => $code, 'valeur' => $valeur];
            }

            $field->changerEnChampParametre($type, $lignes);
        }
        return $this->config->get('avecChampsParametres');
    }

    /**
     * @param $champsSelectionnes
     */
    protected function convertirChampsEnParametres() : void
    {
        $champsSelectionnes = [];
        $champs             = $this->getFields('', 'varchar');

        foreach ($champs as $champ) {

            $colonne      = $champ->getColumn();
            $nomParametre = $this->name . '_' . $colonne;
            $resultat     = $this->getChampsParametresPotentiels($nomParametre);

            if (!empty($resultat)) {
                $reponse = $this->prompt('Voulez vous transformer le champ "' . $colonne . '" en champs paramètre ?', ['o', 'n']) === 'o';
                if ($reponse) {
                    $unChamp = [$nomParametre => []];
                    foreach ($resultat as $ligne) {
                        $unChamp[$nomParametre][$ligne['code']] = $ligne['valeur'];
                    }
                    $champsSelectionnes[$colonne] = $unChamp;
                }
            }

            $champ->changerEnChampParametre($nomParametre, $resultat);
        }

        if (!empty($champsSelectionnes)) {
            $this->config->set('champsParametres', $champsSelectionnes, $this->name);
        } else {
            $this->config->set('champsParametres', false, $this->name);
        }
    }

    /**
     * @param $colonne
     * @param $nomParametre
     * @return array
     */
    protected function getChampsParametresPotentiels($nomParametre) : array
    {
        $select = "SELECT type, code, valeur FROM parametre WHERE archive =  0 AND type = '" . $nomParametre . "'";

        return $this->databaseAccess->query($select, null, false, true);
    }

    /**
     * @param bool $presenceModuleParametre
     */
    protected function addModuleParametreIfMissing(bool $presenceModuleParametre) : void
    {
        if ($presenceModuleParametre === false) {
            shell_exec('cp -r ' . dirname(dirname(__DIR__)) . '/modules/parametre  ' . getcwd() . DS . 'modules' . DS . '.');
        } else {
            $this->msg("Le module paramètre est présent, pas besoin de le générer", 'important');
        }
    }

}