<?php

namespace E2D;

use Core\Config;
use \Spyc;
use Core\ModuleMaker;

class E2DModuleMaker extends ModuleMaker
{
    protected $menuPath;

    /**
     * @param mixed $menuPath
     */
    public function setMenuPath($menuPath): void
    {
        $this->menuPath = getcwd() .DS. $menuPath;
    }

    /**
     * @param $modelName
     * @throws \Exception
     */
    public function initializeModule($params): void
    {
        $this->applyChoicesForAllModules = $this->config['memorizeChoices'] ?? $this->askApplyChoiceForAllModules();
        $this->model->applyChoicesForAllModules = $this->applyChoicesForAllModules;

        $this->template = $this->askTemplate();
        $this->setMenuPath($params['menuPath']);
        $this->addMenu();
    }

    protected function executeSpecificModes()
    {
        //if ('AddOneToMany' === $this->creationMode)
        return $this->AddOneToMany();
    }

    /**
     * @param string $path
     * @return string
     */
    protected function getTrueFilePath(string $path) : string
    {
        if (strpos($path, '.yml') === false) {
            $path = str_replace(['CONTROLLER', 'MODEL', 'TABLE'], [$this->getControllerName(), $this->model->getClassName(), $this->model->getName()], $path);
        }

        return $path;
    }

    /**
     * Identifie quels fichiers sont partagés entre plusieurs models et seront mis a jour quand on rajoute un modèle
     *
     * @param $path
     * @return false|int
     */
    protected function fileIsUpdateable($path)
    {
        if ('generate' === $this->creationMode) {
            return false;
        }
        $modes = ['addModel' =>['yml', 'js'], 'AddOneToMany' => 'js', ''];
        return 'generate' !== $this->creationMode && preg_match('/\.'.(is_array($modes[$this->creationMode]) ?
                    implode('|', $modes[$this->creationMode]) :
                    $modes[$this->creationMode]).'$/', $path);
    }

    /**
     * @param string $templatePath
     * @return string
     */
    protected function generateFileContent(string $templatePath, string $path) : string
    {
        $text = '';
        if (strpos($templatePath, '.yml')) {
            if ($this->creationMode === 'addModel' && file_exists($path)) {
                $text = $this->modifyConfigFiles($templatePath, $path);
            } else {
                $text = $this->generateConfigFiles($templatePath);
            }

        } elseif (strpos($templatePath, 'Action.class.php')) {
            $text = $this->generateActionController($templatePath);
        } elseif (strpos($templatePath, 'HTML.class.php')) {
            $text = $this->generateHTMLController($templatePath);
        } elseif (strpos($templatePath, 'MODEL.class')) {
            $text = $this->generateModel($templatePath);
        } elseif (strpos($templatePath, '.js')) {
            if ($this->creationMode === 'addModel' && strpos($templatePath, 'CONTROLLER.js')) {
               [$message, $type] = $this->modifyJSFiles($templatePath, $path);
               $this->msg($message, $type);
            }
            $text = $this->generateJSFiles($templatePath);

        } elseif (strpos($templatePath, 'accueil_TABLE.html')) {
            $text = file_get_contents($this->getTrueTemplatePath($templatePath));
        } elseif (strpos($templatePath, 'liste_TABLE.html')) {
            $text = $this->generateListView($templatePath);
        } elseif (strpos($templatePath, 'consultation_TABLE.html')) {
            $text = $this->generateConsultationView($templatePath);
        } elseif (strpos($templatePath, 'edition_TABLE.html')) {
            $text = $this->generateEditionView($templatePath);
        } elseif (strpos($templatePath, 'recherche_TABLE.html')) {
            $text = $this->generateSearchView($templatePath);
        }

        return $text;
    }

    private function generateConfigFiles(string $templatePath) : string
    {
        $modelName = '';
        $enums = '';

        if (strpos($templatePath, 'conf.yml') === false ) {
            $texts = [];
            foreach ($this->model->actions as $action) {
                $templatePerActionPath = $this->getTrueTemplatePath(str_replace('.', '_' . $action . '.', $templatePath));
                if (file_exists($templatePerActionPath)) {
                    $texts[] = file_get_contents($templatePerActionPath) .
                        ($this->model->usesMultiCalques && strpos($templatePath, 'blocs') !== false ?
                            file_get_contents(str_replace($action, 'multi', $templatePerActionPath)) :
                            '');
                }
            }

            $text = implode(PHP_EOL, $texts);

        } else {
            $text = '';
            $templatePath = $this->getTrueTemplatePath($templatePath);
            if (file_exists($templatePath)) {
                $text = file_get_contents($templatePath);
            }

            $modelName = $this->model->getClassName() ;
            $fields = $this->model->getViewFieldsByType('enum');


            if (!empty($fields)) {
                foreach ($fields as $field) {
                    $enums .= PHP_EOL."aListe-{$this->model->getName()}-{$field['column']}:".PHP_EOL;
                    foreach ($field['enum'] as $value) {
                        $enums .= str_repeat("\x20", 4)."$value: {$this->labelize($value)}".PHP_EOL;
                    }
                }
            }
        }

        $text = str_replace(['mODULE', 'TABLE', 'MODEL', 'MODULE', 'CONTROLLER', 'cONTROLLER', 'ENUMS'],
            [$this->name, $this->model->getName(), $modelName, $this->namespaceName, $this->getControllerName(), $this->getControllerName('snake_case'), $enums], $text);

        return $text;
    }

    private function modifyConfigFiles(string $templatePath, $path) : string
    {
        $config = Spyc::YAMLLoad($path);


        if (strpos($templatePath, 'conf.yml') === false ) {
            foreach ($this->model->actions as $action) {
                $templatePerActionPath = $this->getTrueTemplatePath(str_replace('.', '_' . $action . '.', $templatePath));
                if (file_exists($templatePerActionPath)) {
                    $template = file_get_contents($templatePerActionPath).($this->model->usesMultiCalques && strpos($templatePath, 'blocs') !== false ?
                        file_get_contents(str_replace($action, 'multi', $templatePerActionPath)) : '');

                    $newConfig = Spyc::YAMLLoadString(str_replace(['mODULE', 'TABLE', 'cONTROLLER', 'MODEL'],
                        [$this->name, $this->model->getName(), $this->getControllerName('snake_case'), ''], $template));
                    $config = array_replace_recursive($config, $newConfig);
                }
            }

        } else {

            $templatePath = $this->getTrueTemplatePath($templatePath);
            if (file_exists($templatePath)) {

                $enums = '';
                $fields = $this->model->getViewFieldsByType('enum');
                if (!empty($fields)) {
                    foreach ($fields as $field) {
                        $enums .= PHP_EOL . "aListe-{$this->model->getName()}-{$field['column']}:" . PHP_EOL;
                        foreach ($field['enum'] as $value) {
                            $enums .= str_repeat("\x20", 4) . "$value: {$this->labelize($value)}" . PHP_EOL;
                        }
                    }
                }
            }


            $template = file_get_contents($templatePath);

            $newConfig = Spyc::YAMLLoadString(str_replace(['mODULE', 'TABLE', 'MODEL', 'MODULE', 'CONTROLLER', 'cONTROLLER', 'ENUMS'],
                [$this->name, $this->model->getName(), $this->model->getClassName(), $this->namespaceName, $this->getControllerName(), $this->getControllerName('snake_case'), $enums], $template));

            $baseJSFilePath = $this->name.'/JS/'.$this->getControllerName().'.js';
            array_unshift($newConfig['aVues'][$this->model->getName()]['admin']['formulaires']['edition_'.$this->model->getName()]['ressources']['JS']['modules'], $baseJSFilePath);
            array_unshift($newConfig['aVues'][$this->model->getName()]['admin']['simples']['accueil_'.$this->model->getName()]['ressources']['JS']['modules'], $baseJSFilePath);

            $config = array_replace_recursive($config, $newConfig);
        }

        $text = Spyc::YAMLDump($config, false, 0, true);
        //$text = str_replace(['mODULE', 'TABLE', 'MODEL', 'MODULE', 'ENUMS'], [$this->name, $this->model->getName(), $this->getControllerName(), $this->namespaceName, $enums], $text);

        return $text;
    }

    /**
     * @param string $selectedTemplatePath
     * @return string|string[]
     */
    private function generateActionController(string $templatePath)
    {
        $text = '';
        $methodText = '';
        $noRecherche = true;
        $switchCaseList = [file_get_contents($this->getTrueTemplatePath(str_replace('Action.', 'ActionSwitchCaseAccueil.', $templatePath)))];

        foreach ($this->model->actions as $action) {
            $schemaMethodsPerActionPath = $this->getTrueTemplatePath(str_replace('Action.', 'Action' . $this->pascalize($action) . '.', $templatePath));
            if (file_exists($schemaMethodsPerActionPath)) {
                $methodText .= file_get_contents($schemaMethodsPerActionPath) . PHP_EOL;
            }

            if ($action !== 'accueil') {
                $switchCaseList[] =  file_get_contents($this->getTrueTemplatePath(str_replace('Action.', 'ActionSwitchCase' . $this->pascalize($action) . '.', $templatePath)));
            }

            if ($action === 'recherche') {
                $noRecherche = false;
            }
        }

        $rechercheActionInitPathHandle = $noRecherche ? 'SansFormulaireRecherche' : 'AvecFormulaireRecherche';
        $rechercheActionInitText = file_get_contents($this->getTrueTemplatePath(str_replace('Action.', 'Action' .  $rechercheActionInitPathHandle  . '.', $templatePath)));

        $switchCaseText = PHP_EOL.implode(PHP_EOL, $switchCaseList);

        if ($this->model->usesSelect2) {
            $enumPath = $this->getTrueTemplatePath(str_replace('Action.', 'ActionEnumSelect2.', $templatePath));
        } else {
            $enumPath = $this->getTrueTemplatePath(str_replace('Action.', 'ActionEnum.', $templatePath));
        }

        $fieldTemplatePath = $this->getTrueTemplatePath(str_replace('Action.', 'ActionEditionChamps.', $templatePath));

        if (file_exists($templatePath = $this->getTrueTemplatePath($templatePath))) {
            $exceptions = [];
            $defaults = [];
            $allEnumEditLines = [];
            $allEnumSearchLines = [];
            $exceptionText = '';

            $fields = $this->model->getViewFields();
            $fieldsText = '';

            foreach ($fields as $field) {
                $this->handleControllerField($field, $exceptions, $defaults, $fieldTemplatePath, $fieldsText, $enumPath, $allEnumEditLines, $allEnumSearchLines);
            }

            if (!empty($fieldsText)) {
                $fieldsText = PHP_EOL.$fieldsText.str_repeat("\x20", 8);
            }

            $enumEditText = implode(PHP_EOL, $allEnumEditLines);
            $enumSearchText = implode(PHP_EOL, $allEnumSearchLines);
            //$enumSearchText .= implode('', $defaults);
            //$defaults = array_merge($enumDefaults, $defaults);

            if ($exceptions) {
                $exceptionText = ', [';
                $exceptionArr = [];
                foreach ($exceptions as $key => $list) {
                    $exceptionArr[] = "'$key' => ['".implode('\', \'', $list).'\']';
                }
                $exceptionText .= implode(',', $exceptionArr).']';
            }

            $methodText = str_replace(['MODEL',  '//EDITSELECT', 'EXCEPTIONS', '//SEARCHSELECT', '//DEFAULT', 'CHAMPS', 'IDFIELD'],
                [$this->model->getClassName(), $enumEditText, $exceptionText, $enumSearchText, implode(PHP_EOL, $defaults), $fieldsText, $this->model->getIdField()], $methodText);
            $text .= file_get_contents($templatePath);
            $concurrentText = $this->model->usesMultiCalques ? file_get_contents(str_replace('Action.', 'ActionMulti.', $templatePath)): '';
            $text = str_replace(['MODULE', 'CONTROLLER', 'MODEL', '//CASE', '//MULTI', 'INIT;', '//METHOD'],
                [$this->namespaceName, $this->getControllerName(), $this->model->getClassName(), $switchCaseText, $concurrentText, $rechercheActionInitText, $methodText], $text);
        }

        return $text;
    }

    private function generateHTMLController(string $selectedTemplatePath) : string
    {
        $text = '';
        if (file_exists($templatePath = $this->getTrueTemplatePath($selectedTemplatePath))) {
            $text = file_get_contents($templatePath);

            $recherche = array_contains('recherche', $this->model->actions) ? '$sFichierContenu = $this->szGetFichierPourInclusion(\'modules\', \'mODULE/vues/recherche_TABLE.html\');
            $oContenu = $this->oGetVue($sFichierContenu);
            $this->objQpModele->find(\'#zone_navigation_2\')->html($oContenu->find(\'body\')->html());' : '';

            $text = str_replace('//RECHERCHE', $recherche, $text);
            $text = str_replace(['MODULE', 'CONTROLLER', 'mODULE', 'TABLE'], [$this->namespaceName, $this->getControllerName(), $this->name, $this->model->getName()], $text);
        }

        return $text;
    }

    /**
     * @param string $templatePath
     * @return string|string[]
     */
    protected function generateModel(string $templatePath)
    {
        $text = '';

        if (file_exists($templatePath = $this->getTrueTemplatePath($templatePath))) {
            $text = file_get_contents($templatePath);
        }

        $joinTemplate = file_get_contents($this->getTrueTemplatePath(str_replace_first('.', 'Joins.', $templatePath)));
        $text = str_replace(['MODULE', 'MODEL', 'TABLE', 'ALIAS', 'PK', 'IDFIELD', '//MAPPINGCHAMPS','//TITRELIBELLE', 'CHAMPS_SELECT', 'LEFTJOINS', '//RECHERCHE', '//VALIDATION'], [
            $this->namespaceName,
            $this->model->getClassName(),
            $this->model->getTableName(),
            $this->model->getAlias(),
            $this->model->getPrimaryKey(), $this->model->getIdField(),
            $this->model->getAttributes(), $this->model->getModalTitle(),
            $this->model->getSqlSelectFields(), $this->model->getJoins($joinTemplate),
            $this->model->getSearchCriteria(),
            $this->model->getValidationCriteria()], $text);

        return $text;
    }

    /**
     * @param string $templatePath
     * @return string|string[]
     */
    private function generateJSFiles(string $selectedTemplatePath)
    {
        $text = '';
        $templatePath = $this->getTrueTemplatePath($selectedTemplatePath);
        if (file_exists($templatePath)) {
            $text = file_get_contents($templatePath);
        }

        $multiText = '';
        $actionMethodText = '';
        if (array_contains_array(['edition', 'consultation'], $this->model->actions, ARRAY_ANY)) {
            if ($this->model->usesMultiCalques) {
                $multiText = " + '_' + nIdElement";
            } else {
                $multiText = '';
            }
        }

        if (array_contains_array(['edition', 'consultation'], $this->model->actions, ARRAY_ALL)) {
            $closeConsultationModal = PHP_EOL.file_get_contents($this->getTrueTemplatePath(str_replace('.', 'EditionFermetureCalqueConsultation.', $selectedTemplatePath)));
        } else {
            $closeConsultationModal = '';
        }

        $noRecherche = true;
        foreach ($this->model->actions as $action) {
            $templatePerActionPath =  $this->getTrueTemplatePath(str_replace('.', $this->pascalize($action) . '.', $selectedTemplatePath));
            if (file_exists($templatePerActionPath)) {
                $actionMethodText .= file_get_contents($templatePerActionPath);
            }

            if ($action === 'recherche') {
                $noRecherche = false;
            }
        }

        if ($noRecherche) {
            $noRechercheText = file_get_contents($this->getTrueTemplatePath(str_replace('.', 'NoRecherche.', $selectedTemplatePath)));
            $actionMethodText = $noRechercheText.$actionMethodText;
        }

        $select2SearchText = PHP_EOL;
        $select2EditText = '';
        if ($fields = $this->model->getViewFieldsByType('enum')) {
            if ($this->model->usesSelect2 && strpos($templatePath, 'Admin') > 0) {

                $select2DefautTemplate = file($this->getTrueTemplatePath(str_replace('.', 'RechercheSelect2.', $selectedTemplatePath)));
                $select2RechercheTemplate = array_shift($select2DefautTemplate);

                $select2EditTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', 'EditionSelect2.', $selectedTemplatePath)));

                foreach ($fields as $field) {

                    $select2SearchText .= str_replace(['NAME'], [$field['name']], $select2RechercheTemplate);
                    $select2EditText .= str_replace(['NAME'], [$field['name']], $select2EditTemplate).PHP_EOL;
                }
                $select2SearchText .= implode('', $select2DefautTemplate).PHP_EOL;
            }
            // [$select2Template, $selectClass] = $this->model->usesSelect2 ? [file_get_contents(str_replace('.', 'Select2.', $templatePath)), 'select2'] : ['', 'selectmenu'];
        }

        $selectAjaxDefinitionText = '';
        $personalizeButtons = '';
        if (strpos($templatePath, 'Admin') > 0) {
           if ($this->model->hasOneRelations) {

                if ($fields = $this->model->getViewFieldsByType('foreignKey')) {

                    $selectAjaxDefinition = [];
                    foreach ($fields as $field) {
                        $foreignClassName = substr($field['oneToMany']['childTableAlias'], 1);
                        $label = $field['oneToMany']['label'];
                        if (is_array($label)) {
                            $label = $this->generateConcatenatedColumn($label);
                        }

                        $ajaxSearchTextTemp = file_get_contents($this->getTrueTemplatePath(str_replace('.', 'RechercheSelectAjaxCall.', $selectedTemplatePath)));
                        $select2SearchText .= str_replace(['MODEL', 'FORM', 'NAME', 'ALLOWCLEAR'], [$foreignClassName, 'eFormulaire', $field['name'], 'true'], $ajaxSearchTextTemp);
                        $allowClear = $field['is_nullable'] ? 'true' : 'false';

                        $selectAjaxCallEditTextTemp = file_get_contents($this->getTrueTemplatePath(str_replace('.', 'EditionSelectAjaxCall.', $selectedTemplatePath)));


                        $select2EditText .= str_replace(['MODEL', 'FORM', 'NAME', 'FIELD', 'ALLOWCLEAR'], [$foreignClassName, 'oParams.eFormulaire', $field['name'], $field['field'], $allowClear], $selectAjaxCallEditTextTemp);

                        $selectAjaxDefinitionTemp = file_get_contents($this->getTrueTemplatePath(str_replace('.', 'SelectAjaxDefinition.', $selectedTemplatePath)));
                        $selectAjaxDefinition[] = str_replace(['MODEL', 'PK', 'LABEL', 'TABLE', 'ORDERBY'],
                            [$foreignClassName, $field['column'], $label, $field['oneToMany']['table'], $field['column']], $selectAjaxDefinitionTemp);
                    }

                    $selectAjaxDefinitionText = PHP_EOL . implode(PHP_EOL, $selectAjaxDefinition) . PHP_EOL;


                }
            }

            $personalizedButtonsTemplateSuffix = array_contains('consultation', $this->model->getActions()) ? 'ConsultationButton.' : 'NoConsultationButtons.';
            $personalizeButtons = file_get_contents($this->getTrueTemplatePath(str_replace('.', $personalizedButtonsTemplateSuffix, $selectedTemplatePath)));
        }


        $text = str_replace([ '/*PERSONALIZEBUTTONS*/', '/*MULTIJS*/', '/*ACTION*/',  'CLOSECONSULTATIONMODAL', 'mODULE',
            'CONTROLLER', 'TITRE', '/*MULTI*/', 'TABLE', 'SELECT2EDIT', 'SELECT2', 'SELECTAJAX',],
            [$personalizeButtons, '', $actionMethodText, $closeConsultationModal, $this->name, $this->getControllerName(),
                $this->model->getTitre(), $multiText, $this->model->getName(), $select2EditText, $select2SearchText, $selectAjaxDefinitionText], $text);

        return $text;
    }

    /**
     * @param string $templatePath
     * @return string|string[]
     */
    private function modifyJSFiles(string $templatePath, string $path)
    {
        $text = '';
        $multiText = str_replace('MODEL', $this->model->getClassName(), file_get_contents($this->getTrueTemplatePath($templatePath, 'MultiFichier.')));


        $filePath = $this->getTrueTemplatePath($path, $this->namespaceName, $this->model->getClassName());

        if (file_exists($filePath)) {
            $text = file_get_contents($filePath);
            if (strpos($text, $this->model->getClassName()) === false) {
                $text = str_replace_first('if', $multiText, $text);
                return $this->saveFile($filePath, $text);
            } else {
                return ['Le fichier '.$this->highlight($filePath, 'info').' n\'est pas mis à jour', 'warning'];
            }
        }

        return $this->saveFile($filePath);
    }

    /**
     * @param string $templatePath
     * @return false|string|string[]
     */
    protected function generateListView(string $templatePath)
    {
        $actionBarText = '';
        $actionText = str_repeat("\x20", 16) . '<td class="centre">' . PHP_EOL;

        if (array_contains('edition', $this->model->getActions())) {
            $actionBarTemplatePath = $this->getTrueTemplatePath($templatePath, '_actionbar.');
            $actionBarText = file_get_contents($actionBarTemplatePath);
        }

        if (array_contains('consultation', $this->model->getActions())) {
            $consultationTemplatePath = $this->getTrueTemplatePath($templatePath, '_consultation.');
            $actionText .= file_get_contents($consultationTemplatePath);
        } else {

            if (array_contains('edition', $this->model->getActions())) {
                $editionTemplatePath = $this->getTrueTemplatePath($templatePath, '_edition.');
                $actionText .= file_get_contents($editionTemplatePath);
            }

            if (array_contains('suppression', $this->model->getActions())) {
                $suppressionTemplatePath = $this->getTrueTemplatePath($templatePath, '_suppression.');
                $actionText .= file_get_contents($suppressionTemplatePath);
            }
        }

        $actionText .= PHP_EOL . str_repeat("\x20", 16) . '</td>';
        $callbackLigne = '';
        if (array_contains_array(['consultation', 'edition', 'suppression'], $this->model->getActions(), ARRAY_ANY)) {
            $callbackLigne = " ligne_callback_cONTROLLER_vCallbackLigneListe";
        }
        $text = file_get_contents($this->getTrueTemplatePath($templatePath));
//     <table id="liste_salle_creneau_reservation" class="material-table tableau_donnees liste_salle_creneau_reservation align_middle route_parametrageesm_json_recherche_salle_creneau_reservation variable_1_0 callback_salleCreneauReservation_vCallbackListeElement ligne_callback_salleCreneauReservation_vCallbackLigneListe">
        $text = str_replace(['ACTION_BAR', 'CALLBACKLIGNE', 'cONTROLLER', 'MODEL', 'HEADERS', 'ACTION', 'COLUMNS', 'mODULE', 'TABLE', 'NUMCOL'],
            [$actionBarText, $callbackLigne, $this->getControllerName('camel_case'), $this->model->getClassname(), $this->model->getTableHeaders(),
                $actionText, $this->model->getTableColumns(), $this->name, $this->model->GetName(), $this->model->getColumnNumber()], $text);
        return $text;
    }

    /**
     * @param string $templatePath
     * @return false|string|string[]
     */
    private function generateConsultationView(string $templatePath)
    {
        $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_field.'));
        $fieldText = [];
        foreach ($this->model->getViewFields() as $field) {
            $fieldText[] = str_replace(['LABEL', 'FIELD'], [$field['label'], $field['field']], $fieldTemplate);
        }
        $text = file_get_contents($this->getTrueTemplatePath($templatePath));
        $text = $this->addModalTitle($text);

        $text = str_replace(['TABLE', 'mODULE', 'FIELDS'], [$this->model->getName(), $this->name, implode(PHP_EOL, $fieldText)], $text);
        return $text;
    }

    /**
     * @param string $templatePath
     * @return false|string|string[]
     */
    private function generateEditionView(string $templatePath)
    {
        $fieldText = [];
        foreach ($this->model->getViewFields() as $field) {
            if (array_contains($field['type'], ['enum'])) {
                if ($this->model->usesSelect2) {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_enum_select2.'));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_enum.'));
                }
            } elseif (array_contains($field['type'], ['bool'])) {
                if ($this->model->usesSwitches) {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_bool_switch.'));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_bool_radio.'));
                }
            } elseif (array_contains($field['type'], ['foreignKey'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_enum_select_ajax.'));
            } elseif (array_contains($field['type'], ['date', 'datetime'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_date.'));
            } elseif (array_contains($field['type'], ['text', 'mediumtext', 'longtext'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_text.'));
            } elseif (array_contains($field['type'], ['float', 'decimal', 'tinyint', 'int', 'smallint', 'double'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_number.'));
            } else {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_string.'));
            }

            $fieldText[] = str_replace(['LABEL', 'FIELD', 'TYPE', 'NAME', 'COLUMN', 'STEP'],
                [$field['label'], $field['field'], $field['type'], $field['name'], $field['column'],
                    (isset($field['step']) ? ' step="'.$field['step'].'"' : '')], $fieldTemplate);
        }

        $text = file_get_contents($this->getTrueTemplatePath($templatePath));
        $text = $this->addModalTitle($text);

        $text = str_replace(['TABLE', 'mODULE', 'FIELDS'], [$this->model->getName(), $this->name, implode(PHP_EOL, $fieldText)], $text);
        return $text;
    }

    /**
     * @param string $templatePath
     * @return false|string|string[]
     */
    private function generateSearchView(string $templatePath)
    {
        $fieldText = [];
        foreach ($this->model->getViewFields() as $field) {
            if (array_contains($field['type'], ['enum'])) {
                if ($this->model->usesSelect2) {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_enum_select2.'));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_enum.'));
                }
            } elseif (array_contains($field['type'], ['bool'])) {
                if ($this->model->usesSwitches) {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_bool_switch.'));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_bool_radio.'));
                }
            } elseif (array_contains($field['type'], ['foreignKey'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_enum_select2.'));
            } elseif (array_contains($field['type'], ['date', 'datetime'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_date.'));
            } elseif (array_contains($field['type'], ['text', 'mediumtext', 'longtext'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_string.'));
            } elseif (array_contains($field['type'], ['float', 'decimal', 'int', 'smallint', 'tinyint', 'double'])) {

                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_number.'));

            } else {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($templatePath, '_string.'));
            }

            $defautOui = $field['default'] === '1' ? ' checked' : '';
            $defautNon = $field['default'] === '0' ? ' checked' : '';
            $fieldText[] = str_replace(['LABEL', 'FIELD', 'TYPE', 'DEFAULT', 'NAME', 'COLUMN', 'STEP', 'DEFAUT_OUI', 'DEFAUT_NON'],
                [$field['label'], $field['field'], $field['type'], $field['default'], $field['name'], $field['column'],
                    (isset($field['step']) ? ' step="'.$field['step'].'"' : ''), $defautOui, $defautNon], $fieldTemplate);


        }

        $text = file_get_contents($this->getTrueTemplatePath($templatePath));
        $text = str_replace(['TABLE', 'FIELDS'], [$this->model->getName(), implode(PHP_EOL, $fieldText)], $text);
        return $text;
    }

    private function addModalTitle($text)
    {
        if ($this->model->usesMultiCalques) {
            list($search, $replace) = ['h2', 'h2 class="sTitreLibelle"'];
            $pos = strpos($text, $search);
            if ($pos !== false) {
                return substr_replace($text, $replace, $pos, strlen($search));
            }
        }

        return $text;
    }

    /**
     * Vérifie qu'un sous menu correspondant au module existe dans menu.yml et soit conforme
     * Sinon on ajoute le sous-menu idoine
     */
    protected function addMenu(): void
    {
        if (isset($this->config['updateMenu']) && !$this->config['updateMenu']) {
            return;
        }

        if (!file_exists($this->menuPath)) {
            return;
        }

        $menu = Spyc::YAMLLoad($this->menuPath);
        $subMenu = $this->getSubMenu();

        if (!empty($menu)) {
            if (isset($menu['admin'][$this->name]['html_accueil_'.$this->model->getName()]) && !array_contains_array($menu['admin'][$this->name]['html_accueil_'.$this->model->getName()], $subMenu['admin'][$this->name]['html_accueil_'.$this->model->getName()], ARRAY_ALL, true)) {
                unset($menu['admin'][$this->name]['html_accueil_'.$this->model->getName()]);
            }

            if (!isset($menu['admin'][$this->name]['html_accueil_'.$this->model->getName()])) {
                $menu = Spyc::YAMLDump(array_merge_recursive($menu, $subMenu), false, 0, true);
                $this->fileManager->createFile($this->menuPath, $menu, true);
            }
        } else {
            $menu = Spyc::YAMLDump($subMenu, false, 0, true);
            $this->createFile($$this->menuPath, $menu, true);
        }
    }

    /**
     * Retourne le sous-menu intégrant le module au menu principal
     *
     * @return array
     */
    protected function getSubMenu(): array
    {
        $template = file_exists(dirname(dirname(__DIR__)) . DS . 'templates' . DS . $this->template . DS . 'menu.yml') ? $this->template : 'standard';
        $label = isset($this->config['titreMenu']) && !empty($this->config['titreMenu']) ? $this->config['titreMenu'] :
            $this->model->getTitre();

        return Spyc::YAMLLoadString(str_replace(['mODULE', 'TABLE', 'LABEL'],
            [$this->name, $this->model->getName(), $label], file_get_contents(dirname(dirname(__DIR__)) . DS . 'templates' . DS . $template . DS . 'menu.yml')));
    }

    protected function getControllerName($case = 'pascal_case'): string
    {
        if ('pascal_case' === $case) {
            return $this->creationMode === 'generate' ? $this->namespaceName : $this->model->getClassName();
        } elseif ('camel_case' === $case) {
            return $this->creationMode === 'generate' ? lcfirst($this->namespaceName) : lcfirst($this->model->getClassName());
        } elseif ('url_case' === $case) {
            return $this->creationMode === 'generate' ? $this->urlize($this->namespaceName) : $this->urlize($this->model->getClassName());
        } else {
            return $this->creationMode === 'generate' ? $this->name : $this->model->getName();
        }

    }

    private function AddOneToMany()
    {
        // Get fields that can become select ajax
    }

    /**
     * @param $field
     * @param array $exceptions
     * @param array $defaults
     * @param $fieldTemplatePath
     * @param string $fieldsText
     * @param $enumPath
     * @param array $allEnumEditLines
     * @param array $allEnumSearchLines
     */
    private function handleControllerField($field, array &$exceptions, array &$defaults, $fieldTemplatePath, string &$fieldsText, $enumPath, array &$allEnumEditLines, array &$allEnumSearchLines): void
    {
        if ($field['type'] === 'bool') {
            $this->handleControllerBooleanField($field, $exceptions, $defaults);
            $fieldsText .= str_replace(['COLUMN', 'NAME'], [$field['column'], $field['name']], file($fieldTemplatePath)[0]);

        } elseif ($field['type'] === 'date') {
            $exceptions['aDates'][] = $field['name'];
            $fieldsText .= str_replace(['COLUMN', 'NAME'], [$field['column'], $field['name']], file($fieldTemplatePath)[1]);
        }  elseif ($field['type'] === 'datetime') {
            $exceptions['aDateTimes'][] = $field['name'];
            $fieldsText .= str_replace(['COLUMN', 'NAME'], [$field['column'], $field['name']], file($fieldTemplatePath)[2]);
        } elseif ($field['type'] === 'time') {
            $exceptions['aDateTimes'][] = $field['name'];
            $fieldsText .= str_replace(['COLUMN', 'NAME'], [$field['column'], $field['name']], file($fieldTemplatePath)[3]);
        }

        elseif ($field['type'] === 'enum') {
            $this->handleControllerEnumField($enumPath, $field, $allEnumEditLines, $allEnumSearchLines, $defaults);
            $fieldsText .= str_replace(['COLUMN', 'NAME'], [$field['column'], $field['name']], file($fieldTemplatePath)[0]);
        } elseif (array_contains($field['type'], ['int', 'smallint', 'tinyint', 'bigint'])) {
            $fieldsText .= $this->generateControllerIntegerField($field, $fieldTemplatePath);

        } elseif (array_contains($field['type'], ['float', 'double', 'decimal'])) {
            $exceptions['aFloats'][] = $field['name'];
            $fieldsText .= str_replace(['COLUMN', 'NAME', 'FIELD'], [$field['column'], $field['name'], $field['field']], file($fieldTemplatePath)[4]);
        } else {
            $fieldsText .= str_replace(['COLUMN', 'NAME', 'FIELD'], [$field['column'], $field['name'], $field['field']], file($fieldTemplatePath)[0]);
        }
    }

    /**
     * @param $field
     * @param $fieldTemplatePath
     * @param string $fieldsText
     * @return string
     */
    protected function generateControllerIntegerField($field, $fieldTemplatePath): string
    {
        return str_replace(['COLUMN', 'NAME'], [$field['column'], $field['name']], file($fieldTemplatePath)[0]);
    }



}