<?php

require_once 'BaseFactory.php';
require 'ModelFactory.php';

class ModuleFactory extends BaseFactory
{
    private $name;
    private $model;
    private $namespaceName;

    protected function __construct($name, $modelName)
    {
        if (!is_dir('modules')) {
            $this->msg('Répertoire \'modules\' inexistant, veuillez vérifier que vous travaillez dans le répertoire racine de votre projet', 'error');
            throw new Exception();
        }

        if (!isset($name)) {
            $name = $this->askName();
        }

        $this->name = $name;
        $this->template = 'standard';
        $this->namespaceName = $this->conversionPascalCase($this->name);

        $this->generate($modelName);
    }

    public function generate($modelName)
    {
        $verbose = true;

        $this->model = ModelFactory::create($modelName, $this);

//        if (!isset($this->model)) {
//            $this->model = $this->getModelName();
//        }
//        $this->model->getClassName() = $this->conversionPascalCase($this->model);

        if ($this->addModule() === false) {
            $this->msg('Création de répertoire impossible. Processus interrompu', 'error');
            return false;
        }

        $moduleStructure = Spyc::YAMLLoad(__DIR__.DS.'module.yml');
        $this->addSubDirectories('modules'.DS.$this->name, $moduleStructure, $verbose);

        $this->addModuleToMenu();
    }

    function askName() : string
    {
        $name = $this->prompt($this->msg('Veuillez renseigner le nom du module :'));
//        $name = '';
//        while($name === '' || $name === null) {
//            $name = readline(;
//        }

        return $name;
    }

    function addSubDirectories($path, $structure, $verbose = false)
    {
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                if ($this->ensureDirExists($path.DS.$key, true, $verbose) === true) {
                    $this->addSubDirectories($path.DS.$key, $value, $verbose);
                }
            } else {
                // crée fichier
                $error = $this->ensureFileExists($path.DS.$value, $verbose);
                $filename = str_replace(['MODULE', 'MODEL', 'TABLE'], [$this->namespaceName, $this->model->getClassName(), $this->model->getName()], $value);
                if ($error === true) {
                   $this->msg('Le '. $this->highlight('fichier ', 'error') . $path.DS. $filename . ' existe déja', 'warning');
                } elseif ($error  !== '') {
                    $this->msg($error, 'error');
                } else {
                    if ($verbose) {
                        $this->msg('Création du fichier: '.$path.DS. $filename, 'success');
                    }
                }
            }
        }
    }

    private function addModule() : bool
    {
        return $this->ensureDirExists('modules/'.$this->name);
    }

    private function ensureDirExists(string $dirname, bool $recursive = false, $verbose = false) : bool
    {
        if(!is_dir($dirname)) {
            return mkdir($dirname, 0777, $recursive) && is_dir($dirname) && $this->msg('Création du répertoire: '.$dirname, 'success');;
        }

        if ($verbose) {
            return $this->msg('Le ' . $this->highlight('répertoire: ', 'info').''.$dirname. ' existe déja.', 'warning');
        }
        return true;
    }

    function ensureFileExists(string $path, $verbose)
    {
        $commonPath = str_replace('modules'.DS.$this->name, '', $path);
        $templatePath = __DIR__.DS.'templates'.DS.$this->template.DS.'module'.$commonPath;
        var_dump($templatePath);

        if (strpos($path, '.yml') === false) {
            $path = str_replace(['MODULE', 'MODEL', 'TABLE'], [$this->namespaceName, $this->model->getClassName(), $this->model->getName()], $path);
        }

        if (glob($path)) {
            return true;
        } else {
            $text = '';

            if (strpos($templatePath, '.yml')) {
                $text = $this->generateConfigFiles($templatePath);
            } elseif (strpos($templatePath, 'Action.class.php')) {
                $text = $this->generateActionController($templatePath);
            } elseif (strpos($templatePath, 'HTML.class.php')) {
                $text = $this->generateHTMLController($templatePath);
            } elseif (strpos($templatePath, 'MODEL.class')) {
                $text = $this->generateModel($templatePath);
            } elseif (strpos($templatePath, '.js')) {
                $text = $this->generateJSFiles($templatePath);
            } elseif (strpos($templatePath, 'accueil_TABLE.html')) {
                $text = file_get_contents($templatePath);
            }elseif (strpos($templatePath, 'liste_TABLE.html')) {
                $text = $this->getListView($templatePath);
            } elseif (strpos($templatePath, 'consultation_TABLE.html')) {
                $text = $this->getConsultationView($templatePath);
            } elseif (strpos($templatePath, 'edition_TABLE.html')) {
                $text = $this->getEditionView($templatePath);
            } elseif (strpos($templatePath, 'recherche_TABLE.html')) {
                $text = $this->getSearchView($templatePath);
            }
            //$this->msg("Template path: ".$templatePath, self::Color['White']);

            return $this->createFile($path, $text, true, $verbose);
        }
    }

    private function generateConfigFiles(string $templatePath) : string
    {
        $texts = [];
        if (glob($templatePath)) {
            $texts[] = file_get_contents($templatePath);
        }

        foreach ($this->model->actions as $action) {
            $templatePerActionPath = str_replace('.', '_'.$action.'.', $templatePath);
            if (glob($templatePerActionPath)) {
                $texts[] = file_get_contents($templatePerActionPath) .
                    ($this->model->usesMultiCalques && strpos($templatePath, 'blocs') !== false  ?
                        file_get_contents(str_replace($action, 'multi' ,$templatePerActionPath)) :
                        '' );
            }
        }

        $text = implode(PHP_EOL, $texts);

        $modelName = '';
        $enums = '';
        if (strpos($templatePath, 'conf.yml') !== false ) {
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


        //$text = sprintf($text, $this->name, $this->model->getName(), $modelName, $this->namespaceName);
        $text = str_replace(['mODULE', 'TABLE', 'MODEL', 'MODULE', 'ENUMS'], [$this->name, $this->model->getName(), $modelName, $this->namespaceName, $enums], $text);

        return $text;
    }

    /**
     * @param string $templatePath
     * @return string|string[]
     */
    private function generateActionController(string $templatePath)
    {
        $switchCases = [
            'recherche' => 'case \'dynamisation_recherche\':
                    $aRetour = $this->aDynamisationRecherche();
                    break;
                ',
            'edition' => 'case \'dynamisation_edition\':
                    $aRetour = $this->aDynamisationEdition($nIdElement);
                    break;
                
                case \'enregistre_edition\':
                    $aRetour = $this->aEnregistreEdition($nIdElement);
                    break;
                ',
            'suppression' => 'case \'suppression\':
                    $aRetour = $this->aSuppression($nIdElement);
                    break;
                ',
            'consultation' => 'case \'dynamisation_consultation\':
                    $aRetour = $this->aDynamisationConsultation($nIdElement);
                    break;
                ',
        ];

        $text = '';
        $methodText = '';
        $switchCaseList = [];
        $noRecherche = true;
        foreach ($this->model->actions as $action) {
            $schemaMethodsPerActionPath = str_replace('Action.', 'Action' . $this->conversionPascalCase($action) . '.', $templatePath);
            if (glob($schemaMethodsPerActionPath)) {
                $methodText .= file_get_contents($schemaMethodsPerActionPath) . PHP_EOL;
            }

            if ($action !== 'accueil') {
                $switchCaseList[] = '                ' . $switchCases[$action];
            }

            if ($action === 'recherche') {
                $noRecherche = false;
            }
        }

        $rechercheActionInitPathHandle = $noRecherche ? 'SansFormulaireRecherche' : 'AvecFormulaireRecherche';
        $rechercheActionInitText = file_get_contents(str_replace('Action.', 'Action' .  $rechercheActionInitPathHandle  . '.', $templatePath));

        $switchCaseText = PHP_EOL.implode(PHP_EOL, $switchCaseList);

        if (glob($templatePath)) {
            $exceptions = [];
            $boolFields = $this->model->getViewFieldsByType('tinyint');
            $default = '';
            $defaults = [];
            if (!empty($boolFields)) {
                foreach ($boolFields as $field) {
                    $exceptions['aBooleens'][] = $field['field'];
                    if (isset($field['default'])) {
                        $defaultValue = $field['default'];
                    } else {
                        $defaultValue  =  'nc';
                    }
                    $defaults[] = str_repeat("\x20", 8)."\$aRetour['aRadios']['{$field['name']}'] = '$defaultValue';";
                }

            }

            $dateFields = $this->model->getViewFieldsByType([ 'date', 'datetime']);
            if (!empty($dateFields)) {
                foreach ($dateFields as $field) {
                    $exceptions['aDates'][] = $field['name'];
                }
            }

            if ($exceptions) {
                $exceptionText = ', [';
                $exceptionArr = [];
                foreach ($exceptions as $key => $list) {
                    $exceptionArr[] = "'$key' => ['".implode('\', \'', $list).'\']';
                }
                $exceptionText .= implode(',', $exceptionArr).']';
            }

            $enums = $this->model->getViewFieldsByType('enum');
            $enumEditText = '';
            $enumSearchText = '';
            $enumDefaults = [];
            $allEnumEditLines = [];
            $allEnumSearchLines = [];
            if (!empty($enums)) {
                if ($this->model->usesSelect2) {
                    $enumPath = str_replace('Action.', 'ActionEnumSelect2.', $templatePath);
                } else {
                    $enumPath = str_replace('Action.', 'ActionEnum.', $templatePath);
                }

                foreach ($enums as $enum) {
                    $enumLines = $enumSearchLines = file($enumPath);
                    $enumEditionLine = $enumLines[0];

                    if ($enum['default']) {
                        $enumSearchLines = $enumLines;
                        $enumDefault = $enumLines[2];
                    } else {
                        $enumSearchLines = [$enumLines[0]];
                    }

                    if ($this->model->usesSelect2) {
                        if ($enum['default']) {
                            $enumSearchLines = array_slice($enumLines, 0, 3);
                            $enumDefault = $enumLines[3];
                        } else {
                            $enumSearchLines = array_slice($enumLines, 0, 1);
                        }
                        if ($enum['default'] === null) {
                           $enum['default'] = '';
                        }
                    }


                    $searches = ['NAME', 'mODULE', 'TABLE', 'COLUMN', 'DEFAULT'];
                    $replacements = [$enum['name'], $this->name, $this->model->getName(), $enum['column'], $enum['default']];

                    $allEnumEditLines[] = str_replace($searches, $replacements , $enumEditionLine);
                    $allEnumSearchLines[] = str_replace($searches, $replacements , implode('', $enumSearchLines));
                    $enumDefaults[] = str_replace($searches, $replacements , $enumDefault);
                }

                $enumEditText = implode(PHP_EOL, $allEnumEditLines);
                $enumSearchText = implode(PHP_EOL, $allEnumSearchLines);
                $enumSearchText .= implode('', $defaults);
                $defaults = array_merge($enumDefaults, $defaults);
            }

            $enumSearchLines = array_merge($enumSearchLines, $defaults);

            $methodText = str_replace(['MODEL',  '//EDITSELECT', 'EXCEPTIONS', '//SEARCHSELECT', '//DEFAULT'],
                [$this->model->getClassName(), $enumEditText, $exceptionText, $enumSearchText, implode(PHP_EOL, $defaults)], $methodText);
            $text .= file_get_contents($templatePath);
            $concurrentText = $this->model->usesMultiCalques ? file_get_contents(str_replace('Action.', 'ActionMulti.', $templatePath)): '';
            $text = str_replace(['MODULE', 'MODEL', '//CASE', '//MULTI', 'INIT;', '//METHOD'],
                [$this->namespaceName, $this->model->getClassName(), $switchCaseText, $concurrentText, $rechercheActionInitText, $methodText], $text);
        }
        return $text;
    }

    private function generateHTMLController(string $templatePath) : string
    {
        $text = '';

        if (glob($templatePath)) {
            $text = file_get_contents($templatePath);

            $recherche = array_contains('recherche', $this->model->actions) ? '$sFichierContenu = $this->szGetFichierPourInclusion(\'modules\', \'mODULE/vues/recherche_TABLE.html\');
            $oContenu = $this->oGetVue($sFichierContenu);
            $this->objQpModele->find(\'#zone_navigation_2\')->html($oContenu->find(\'body\')->html());' : '';

            $text = str_replace('//RECHERCHE', $recherche, $text);
            $text = str_replace(['MODULE', 'mODULE', 'TABLE'], [$this->namespaceName, $this->name, $this->model->getName()], $text);
        }

        return $text;
    }

    /**
     * @param string $templatePath
     * @return string|string[]
     */
    private function generateModel(string $templatePath)
    {
        $text = '';
        //$templatePath = str_replace('MODEL', 'Model', $templatePath);
        if (glob($templatePath)) {
            $text = file_get_contents($templatePath);
        }

        $text = str_replace(['MODULE', 'MODEL', 'TABLE', 'ALIAS', 'PK', 'IDFIELD', '//CHAMPS','//TITRELIBELLE', 'CHAMPS_SELECT', '//RECHERCHE', '//VALIDATION'], [
            $this->namespaceName,
            $this->model->getClassName(),
            $this->model->getName(),
            $this->model->getAlias(),
            $this->model->getPrimaryKey(), $this->model->getIdField(),
            $this->model->GetMappingChamps(), $this->model->getModalTitle(),
            $this->model->getSqlSelectFields(),
            $this->model->getSearchCriteria(),
            $this->model->getValidationCriteria()], $text);

        return $text;
    }

    /**
     * @param string $templatePath
     * @return string|string[]
     */
    private function generateJSFiles(string $templatePath)
    {
        $text = '';
        if (glob($templatePath)) {
            $text = file_get_contents($templatePath);
        }

        $multiText = '';
        $actionMethodText = '';
        if (array_contains(['edition', 'consultation'], $this->model->actions, ARRAY_ANY)) {
            if ($this->model->usesMultiCalques) {
                $multiText = " + '_' + nIdElement";
            } else {
                $multiText = '';
            }
        }

        $noRecherche = true;
        foreach ($this->model->actions as $action) {
            $templatePerActionPath = str_replace('.', $this->conversionPascalCase($action) . '.', $templatePath);
            if (glob($templatePerActionPath)) {
                $actionMethodText .= file_get_contents($templatePerActionPath);
            }

            if ($action === 'recherche') {
                $noRecherche = false;
            }
        }

        if ($noRecherche) {
            $noRechecheText = file_get_contents(str_replace('.', 'NoRecherche.', $templatePath));
            $actionMethodText = $noRechecheText.$actionMethodText;
        }

        $select2Text = PHP_EOL;
        $select2EditText = '';
        if ($fields = $this->model->getViewFieldsByType('enum')) {
            if ($this->model->usesSelect2 && strpos($templatePath, 'Admin') > 0) {
                if (strpos($templatePath, 'Admin') > 0) {

                }
                $select2DefautTemplate = file(str_replace('.', 'RechercheSelect2.', $templatePath));
                $select2RechercheTemplate = array_shift($select2DefautTemplate);

                $select2EditTemplate = file_get_contents(str_replace('.', 'EditionSelect2.', $templatePath));

                foreach ($fields as $field) {

                    $select2Text .= str_replace('NAME', $field['name'], $select2RechercheTemplate);
                    $select2EditText .= str_replace('NAME', $field['name'], $select2EditTemplate).PHP_EOL;
                }
                $select2Text .= implode('', $select2DefautTemplate);

            }
           // [$select2Template, $selectClass] = $this->model->usesSelect2 ? [file_get_contents(str_replace('.', 'Select2.', $templatePath)), 'select2'] : ['', 'selectmenu'];
        }

        $text = str_replace(['/*ACTION*/', 'mODULE', 'MODEL', '/*MULTI*/', 'TABLE', 'SELECT2EDIT', 'SELECT2'],
            [$actionMethodText, $this->name, $this->model->getClassName(), $multiText, $this->model->getName(), $select2EditText, $select2Text], $text);

        return $text;
    }

    /**
     * @param string $templatePath
     * @return false|string|string[]
     */
    private function getListView(string $templatePath)
    {
        $actionBarText = '';
        $actionText = str_repeat("\x20", 16) . '<td class="centre">' . PHP_EOL;

        if (array_contains('edition', $this->model->getActions())) {
            $actionBarTemplatePath = str_replace('.', '_actionbar.', $templatePath);
            $actionBarText = file_get_contents($actionBarTemplatePath);
        }

        if (array_contains('consultation', $this->model->getActions())) {
            $consultationTemplatePath = str_replace('.', '_consultation.', $templatePath);
            $actionText .= file_get_contents($consultationTemplatePath);
        } else {

            if (array_contains('edition', $this->model->getActions())) {
                $editionTemplatePath = str_replace('.', '_edition.', $templatePath);
                $actionText .= file_get_contents($editionTemplatePath);
            }

            if (array_contains('suppression', $this->model->getActions())) {
                $suppressionTemplatePath = str_replace('.', '_suppression.', $templatePath);
                $actionText .= file_get_contents($suppressionTemplatePath);
            }
        }

        $actionText .= PHP_EOL . str_repeat("\x20", 16) . '</td>';
        $callbackLigne = '';
        if (array_contains(['consultation', 'edition', 'suppression'], $this->model->getActions())) {
            $callbackLigne = " ligne_callback_TABLE_vCallbackLigneListe";
        }
        $text = file_get_contents($templatePath);
        $text = str_replace(['ACTION_BAR', 'JSFILE', 'CALLBACKLIGNE', 'MODEL', 'HEADERS', 'ACTION', 'COLUMNS', 'mODULE', 'TABLE', 'NUMCOL'],
            [$actionBarText, $this->model->GetName(), $callbackLigne, $this->model->getClassname(), $this->model->getTableHeaders(),
                $actionText, $this->model->getTableColumns(), $this->name, $this->model->GetName(), $this->model->getColumnNumber()], $text);
        return $text;
    }

    /**
     * @param string $templatePath
     * @return false|string|string[]
     */
    private function getConsultationView(string $templatePath)
    {
        $fieldTemplate = file_get_contents(str_replace('.', '_field.', $templatePath));
        $fieldText = [];
        foreach ($this->model->getViewFields() as $field) {
            $fieldText[] = str_replace(['LABEL', 'FIELD'], [$field['label'], $field['field']], $fieldTemplate);
        }
        $text = file_get_contents($templatePath);
        $text = $this->addModalTitle($text);

        $text = str_replace(['TABLE', 'mODULE', 'FIELDS'], [$this->model->getName(), $this->name, implode(PHP_EOL, $fieldText)], $text);
        return $text;
    }

    /**
     * @param string $templatePath
     * @return false|string|string[]
     */
    private function getEditionView(string $templatePath)
    {
        $fieldText = [];
        foreach ($this->model->getViewFields() as $field) {
            if (array_contains($field['type'], ['enum'])) {
                if ($this->model->usesSelect2) {
                    $fieldTemplate = file_get_contents(str_replace('.', '_enum_select2.', $templatePath));
                } else {
                    $fieldTemplate = file_get_contents(str_replace('.', '_enum.', $templatePath));
                }
            } elseif (array_contains($field['type'], ['tinyint'])) {
                if ($this->model->usesSwitches) {
                    $fieldTemplate = file_get_contents(str_replace('.', '_tinyint_switch.', $templatePath));
                } else {
                    $fieldTemplate = file_get_contents(str_replace('.', '_tinyint_radio.', $templatePath));
                }

            } elseif (array_contains($field['type'], ['date', 'datetime'])) {
                $fieldTemplate = file_get_contents(str_replace('.', '_date.', $templatePath));
            } elseif (array_contains($field['type'], ['text', 'mediumtext', 'longtext'])) {
                $fieldTemplate = file_get_contents(str_replace('.', '_tinyint.', $templatePath));
            } elseif (array_contains($field['type'], ['float', 'decimal', 'int', 'smallint', 'double'])) {
                $fieldTemplate = file_get_contents(str_replace('.', '_number.', $templatePath));
            } else {
                $fieldTemplate = file_get_contents(str_replace('.', '_string.', $templatePath));
            }

            $fieldText[] = str_replace(['LABEL', 'FIELD', 'TYPE', 'NAME'],
                [$field['label'], $field['field'], $field['type'], $field['name']], $fieldTemplate);
        }

        $text = file_get_contents($templatePath);
        $text = $this->addModalTitle($text);

        $text = str_replace(['TABLE', 'mODULE', 'FIELDS'], [$this->model->getName(), $this->name, implode(PHP_EOL, $fieldText)], $text);
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
     * @param string $templatePath
     * @return false|string|string[]
     */
    private function getSearchView(string $templatePath)
    {
        $fieldText = [];
        foreach ($this->model->getViewFields() as $field) {
            if (array_contains($field['type'], ['enum'])) {
                if ($this->model->usesSelect2) {
                    $fieldTemplate = file_get_contents(str_replace('.', '_enum_select2.', $templatePath));
                } else {
                    $fieldTemplate = file_get_contents(str_replace('.', '_enum.', $templatePath));
                }
            } elseif (array_contains($field['type'], ['tinyint'])) {
                if ($this->model->usesSwitches) {
                    $fieldTemplate = file_get_contents(str_replace('.', '_tinyint_switch.', $templatePath));
                } else {
                    $fieldTemplate = file_get_contents(str_replace('.', '_tinyint_radio.', $templatePath));
                }
            } elseif (array_contains($field['type'], ['date', 'datetime'])) {
                $fieldTemplate = file_get_contents(str_replace('.', '_date.', $templatePath));
            } elseif (array_contains($field['type'], ['text', 'mediumtext', 'longtext'])) {
                $fieldTemplate = file_get_contents(str_replace('.', '_tinyint.', $templatePath));
            } elseif (array_contains($field['type'], ['float', 'decimal', 'int', 'smallint', 'double'])) {
                $fieldTemplate = file_get_contents(str_replace('.', '_number.', $templatePath));
            } else {
                $fieldTemplate = file_get_contents(str_replace('.', '_string.', $templatePath));
            }

            $defautOui = $field['default'] === '1' ? ' checked' : '';
            $defautNon = $field['default'] === '0' ? ' checked' : '';
            $fieldText[] = str_replace(['LABEL', 'FIELD', 'TYPE', 'DEFAULT', 'DEFAUT_OUI', 'DEFAUT_NON'],
                [$field['label'], $field['field'], $field['type'], $field['default'], $defautOui, $defautNon], $fieldTemplate);
        }

        $text = file_get_contents($templatePath);
        $text = str_replace(['TABLE', 'FIELDS'], [$this->model->getName(), implode(PHP_EOL, $fieldText)], $text);
        return $text;
    }

    private function getModelName()
    {
        // TODO regler CAMEL CASE conversions
        $model = readline($this->msg('Veuillez renseigner le nom du modèle :'.
            PHP_EOL.'Si vous envoyez un nom de modèle vide, le nom du modèle sera le nom du module ['.$this->name.']'));
        if (!$model) {
            $model = $this->name;
        }

        return $model;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNamespace()
    {
        return $this->namespaceName;
    }

    /**
     * @param string $modelName
     */
    private function addModuleToMenu(): void
    {
        $menuPath = 'config/menu.yml';
        if (!file_exists($menuPath)) {
            $menuPath = '';
        }

        $menu = Spyc::YAMLLoad($menuPath);
        $modelName = $this->model->getName();
        $newMenu = Spyc::YAMLLoadString(str_replace(['mODULE', 'TABLE', 'LABEL'],
            [$this->name, $modelName, $this->model->labelize($modelName) . 's'],
            file_get_contents(__DIR__.DS.'templates'.DS.$this->template.DS.'menu.yml')));
        //$newMenu = ['admin' => [$this->name => ['html_accueil_' . $modelName => ['titre' => 'Mes ' . $this->model->labelize($modelName) . 's']]]];

        if (!isset($menu['admin'][$this->name]) || strpos(serialize($menu['admin'][$this->name]), serialize($newMenu['admin'][$this->name])) === false) {
            unset($menu['admin'][$this->name]);
            $menu = Spyc::YAMLDump(array_merge_recursive($menu, $newMenu), false, false, true);
            $this->createFile($menuPath, $menu, true);
        }
    }

}