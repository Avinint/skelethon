<?php

namespace E2D;

use Core\Field;
use Core\FilePath;
use Core\ModelMaker;

class E2DModelMaker extends ModelMaker
{
    use E2DManyToOneMaker;

    private $modalTitle = [];

    public function __construct($fieldClass, string $module, $name, $creationMode = 'generate', $app)
    {
        parent::__construct($fieldClass, $module, $name, $creationMode, $app);
    }

    protected function addSpecificActions()
    {

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
        $actionHeader = empty($this->getActions()) ? '' : file_get_contents($this->getTrueTemplatePath($templatePath->add('actionheader'))) . PHP_EOL;
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

    public function getActionsDisponibles()
    {
        if (!array_contains('export', $this->actionsDisponibles)) {
            $this->actionsDisponibles[] = 'export';
        }

//        if (!array_contains('export', $this->actionsDisponibles)) {
//            if ($this->hasAction('export') && !$this->app->has('avecRessourceExport') ) {
//                $this->askAvecRessourceExport();
//            }
//
//            if ($this->ressourceExportInstallee() && $this->tablesExportCreees()) {
//                $this->actionsDisponibles[] = 'export';
//            } else {
//                $this->msg('fonctionnalité export indisponible : table et/ou ressource export manquante(s)', 'important');
//            }
//        }

        return $this->actionsDisponibles;
    }

    public function tablesExportCreees() : bool
    {
        return array_contains_array(['export', 'export_champ'], $this->databaseAccess->getSimilarTableList('export'), ARRAY_ALL) || $this->askCreateTableExport();
    }

    public function ressourceExportInstallee() : bool
    {
        return $this->app->getFileManager()->getRessourcePath('export')->exists() || $this->askInstallRessourceExport();
    }

    /**
     * Questions sur details spécifiques au type de projet généré nécessaire à la génération, posées dans le constructeur.
     */
    protected function askSpecificsPreData() : void
    {
//        if ($this->hasAction('export')) {
//
//            if (!is_dir($this->app->getFileManager()->getRessourcePath('export'))) {
//               $this->msg("Ressource manquante: export", 'error');
//               $this->removeAction('export');
//            }
//        }

        $this->usesMultiCalques           = $this->askMulti();
        $this->usesSelect2                = $this->askSelect2();
        $this->usesSwitches               = $this->askSwitches();
        $this->usesNoCallBackListeElement = $this->askCallbackListe();
        $this->usesCallbackListeLigne     = $this->askCallbackListeLigne();
        $this->usesPagination             = $this->askPagination();
        $this->usesSecurity               = $this->app->get('updateSecurity') ?? $this->askGenerateSecurity();

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
        return $this->askBool('avecCallbackListeElenent', 'Voulez-vous un template qui utilise callbackListeElement)');
    }

    private function askCallbackListeLigne(): bool
    {
        return $this->askBool('usesCallbackListeLigne', 'Voulez-vous un template qui utilise un callback pour personnaliser les lignes de liste (callbackListeLigne) ?');
    }

    private function askPagination() : bool
    {
        return $this->askBool('usesPagination', 'Souhaitez vous utiliser la pagination ?');
    }

    private function askInstallRessourceExport()
    {
        $installExportRessource = $this->prompt('Souhaitez vous installer la ressource Export?', ['o', 'n']) === 'o';
        if ($installExportRessource) {
            $command = 'git submodule add -b release-0.1 --force git@bitbucket.org:doingfr/export.git ressources/export';
            $execOutput = [];
            $res = 0;

            exec($command, $execOutput, $res);
            $this->msg($res ? 'erreur git' :  'succès git', 'info');
            return !$res;
        }

        return false;
    }

    private function askCreateTableExport()
    {
        $createExportTable =  $this->prompt('Souhaitez vous créer une table Export?', ['o', 'n']) === 'o';
        if ($createExportTable) {

            $requetePath = $this->app->getFileManager()->getRessourcePath('export')->addChild('installation')->addFile('installation.sql');
            if ($requetePath->exists()) {
                $requete = file_get_contents($requetePath);
                $this->databaseAccess->query($requete);
            } else {
                $this->msg('Il faut installer la ressource Export avant de lancer la création de la table', 'error');
            }

        }
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
    protected function askSpecificsPostData()
    {
        $this->usesTinyMCE = $this->config->get('champsTinyMCE') ?? $this->askChampsTinyMCE();
        $this->askAddManyToOneField();
        if ($this->hasAction('export') && !$this->app->has('avecRessourceExport') ) {
            $this->askAvecRessourceExport();
        }

        if ($this->app->get('avecRessourceExport')) {
            if (!$this->app->get('id_export') || $this->app->get('afficher_requete_export') === 'toujours') {
                $this->insertExportData();
            }
        }

        // INUTILISE $this->champsOneToMany = $this->config->get('oneToManyFields') ?? $this->askOneToManyFields();

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

    public function initializeActions($actions)
    {
        $actionCollection = [];

        foreach ($actions as $action) {
            $className = 'E2D\\'.ucfirst($action).'Action';
            $actionCollection[$action] = new $className($this->app);
        }

        return $actionCollection;
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

    public function getFieldsForExport()
    {
        return $this->getFields('export');
    }

    /**
     * @param string $alias
     * @return string
     */
    protected function generateAlias(string $alias) : string
    {
        if ($this->app->has('alias', $this->name)) {
            return $this->app->get('alias', $this->name);
        }

        if ($this->config->get('prefix') ?? false) {
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

    public function getJoins(array $template)
    {
        $joinText = '';
        $joins = [];
        $paramJoins = [];
        $joinList = $this->config->get('manyToOne') ?? false;
        $joinParametreList = $this->config->get('champsParametres') ?? false;

        if (!empty($joinList)) {
            $joinTemplate = $template[0]. $template[1];
            $joins = array_map(function ($join) use ($joinTemplate) {
                return str_replace(['FKTABLE', 'FKALIAS', 'ALIAS', 'FK'],
                    [$join['table'], $join['alias'], $this->getAlias(), $join['pk']], $joinTemplate);
            }, $joinList);
        }
        if ($joinParametreList) {
            $joinTemplate = $template[0]. $template[2];

            $paramJoins = array_map(function ($join, $key) use ($joinTemplate) {
                return str_replace(['FKTABLE', 'FKALIAS', 'ALIAS', 'COLUMN', 'TYPE'],
                    ['parametre', strtoupper(substr($key, 0, 3)), $this->getAlias(), $key, key($join)], $joinTemplate);
            }, $joinParametreList, array_keys($joinParametreList));

        }

        $joins = array_merge($joins, $paramJoins);
        if ($joins) {
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

        $reponse1 = $this->askBool('avecChampsParametres', 'Voulez vous transformer certains champs en paramètres ?');
        if ($reponse1) {
            $this->convertirChampsEnParametres();
        }

//        if ($this->config->has('avecChampsParametres')) {
//            $presenceModuleParametre = count(glob(getcwd() . '/modules/parametre')) > 0;
//            $this->addModuleParametreIfMissing($presenceModuleParametre);
//        }

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
        $champs             = $this->getFieldsByType('varchar');

        foreach ($champs as $champ) {

            $colonne      = $champ->getColumn();
            $nomParametre = $this->app->get('champsParametreMatch')[$colonne] ?? $this->name . '_' . $colonne;
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

    protected function insertExportData()
    {
        $exportIdRes = $this->databaseAccess->query('
            SELECT MAX(id_export) + 1 id FROM export;
        ');
        $exportChampIdRes = $this->databaseAccess->query('
            SELECT MAX(id_export_champ) + 1  id FROM export_champ;
        ');

        $exportId = array_shift($exportIdRes)->id ?? 1;

        $exportChampId = array_shift($exportChampIdRes)->id ?? 1;
        $champsExport = array_map(function($field) {return $field->getFormattedName();}, $this->getFields('export'));

        [$tri1, $tri2, $tri3] = array_values($champsExport);

        $sInsertQuery = "
            INSERT INTO `export` (`id_export`, `id_utilisateur`, `libelle`, `contexte`, `partage`, `format`, `libelle_colonne`, `tri_1`, `tri_2`, `tri_3`) VALUES
            ($exportId, 1, 'Export Excel par défaut', '{$this->module}', 1, 'xls', 1, '$tri1', '$tri2', '$tri3');
            ";

        $sParametresRequete =
            'libellé : Export Excel par défaut'. PHP_EOL .
            'format  : xls' . PHP_EOL .
            'tri 1   : ' . $tri1 . PHP_EOL.
            'tri 2   : ' . $tri2 . PHP_EOL.
            'tri 3   : ' . $tri3 . PHP_EOL . PHP_EOL;


        if (($this->app->get('exportAskCustom') ?? true) && $this->prompt('Voulez vous insérer l\'export de '.
                $this->highlight($this->name, 'success').
                ' avec les paramètres suivants?'.PHP_EOL. $sParametresRequete, ['o', 'n']) === 'n') {

            $sInsertQuery = $this->modifierRequeteExportParDefaut($sInsertQuery, [$tri1, $tri2, $tri3], $champsExport);
        }


        $sInsertQuery2 = "INSERT INTO `export_champ` (`id_export_champ`, `id_export`, `nom_champ`, `ordre`) VALUES". PHP_EOL;

        $queryLines = [];
        foreach (array_values($champsExport) as $index => $field) {

            $queryLines[]  = "            ('$exportChampId', '$exportId', '$field', '$index')";
            $exportChampId++;
        }
        $sInsertQuery .= $sInsertQuery2 . implode(','.PHP_EOL, $queryLines).';';
        if ($this->app->get('afficher_requete_export') ?? false) {
            echo PHP_EOL.$sInsertQuery.PHP_EOL.PHP_EOL;
            $this->app->set('id_export', (int)$exportId, $this->name);
        } else {
            if ($this->databaseAccess->query($sInsertQuery, null, 'insert')) {
                $this->app->set('id_export', (int)$exportId, $this->name);
            }
        }

    }

    /**
     * @param string $sInsertQuery
     * @param $tris
     * @param array $champsExport
     * @return string
     */
    protected function modifierRequeteExportParDefaut(string $sInsertQuery, $tris, array $champsExport) : string
    {
        if (($this->app->get('exportAskLibelle') ?? true) && $this->prompt('Voulez-vous modifier le libellé ?', ['o', 'n']) === 'o') {
            $continue = false;
            do {
                $libelle = $this->prompt("Veuillez entrer un nouveau libellé");
                if (empty($libelle)) {
                    $continue = !($this->prompt('Libellé vide, voulez vous garder le libellé d\'origine ? ', ['o', 'n']) === 'o');
                } else {
                    $sInsertQuery = str_replace('Export Excel par défaut', $libelle, $sInsertQuery);
                }
            } while ($continue);
        }

        if (($this->app->get('exportAskChampsTri') ?? true) && $this->prompt('Voulez-vous modifier les champs de tri ?', ['o', 'n']) === 'o') {
            foreach ($tris as $champTri) {
                $nouveauChampTri = $this->askMultipleChoices('champ', array_keys($champsExport), '');
                $sInsertQuery = str_replace_last($champTri, $nouveauChampTri, $sInsertQuery);
            }
        }

        if (($this->app->get('exportAskCSV') ?? true) && $this->prompt('Voulez-vous créer un export CSV ? Par défaut, un export XLS sera créé', ['o', 'n']) === 'o') {
            $sInsertQuery = str_replace_last('xls', 'csv', $sInsertQuery);
        }

        return $sInsertQuery;
    }

    public function askAvecRessourceExport()
    {
        return $this->askBool('avecRessourceExport', 'Voulez-vous utiliser la ressource export? (permet à l\'utilisateur de personnaliser les exports dans l\'application)');
    }

    //////// LEGACY !!!!!!!!!!!!!!!!!!!!!!!
    ///
    public function getEditFields() :string
    {
        return implode(','.PHP_EOL, array_map(function($field) { return str_repeat("\x20", 12) .$field->getUpdateFieldLegacy();}, $this->getUpdateFields()));
    }

    /**
     * Pour générer le bInsert
     */
    public function getInsertColumns()
    {
       return implode(','.PHP_EOL, array_map(function(E2DField $field) { return str_repeat("\x20", 16) .$field->getColumn();}, $this->getInsertFields()));
    }

    public function getInsertValues()
    {
        return implode(','.PHP_EOL, array_map(function(E2DField $field) {return str_repeat("\x20", 16) .$field->getInsertValueLegacy();}, $this->getInsertFields()));
    }

    private function askOneToManyFields()
    {
        $this->withOneToManyFields =  $this->app->get('>withOneToManyFields') ?? $this->askBool('withOneToManyFields', 'Voulez vous ajouter des listes dynamiques dans les formulaires? (champs One To Many)');

        if ($this->withOneToManyFields) {
            $choix = '';
            while($choix === '') {
                $filtre            = readline($this->msg('Entrer du texte vous souhaitez filtrer la liste de tables pour affiche rseulement les tables contenant ce texte, (ex: salle affichera salle_creneau, salle_reservation'));
                $tablesDisponibles = $this->databaseAccess->getSimilarTableList($filtre);
                if ($this->app->get('enlever_many2one_des_one2many') ?? true) {
                    $tablesManyToOne   = array_values(array_flip(array_flip(array_map(function ($manyToOne) { return $manyToOne['table']; }, $this->app->get('manyToOne')))));
                    $tablesDisponibles = array_diff($tablesDisponibles, $tablesManyToOne);
                }

                $choix = $this->askMultipleChoices('table', $tablesDisponibles);
            }
        }
    }

}