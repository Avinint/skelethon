<?php

require_once 'BaseFactory.php';
require 'Model.php';

class Module extends BaseFactory
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
        $this->namespaceName = $this->conversionPascalCase($this->name);

        $this->generate($modelName);
    }

    public function generate($modelName)
    {
        $verbose = true;

        $this->model = Model::create($modelName, $this);

//        if (!isset($this->model)) {
//            $this->model = $this->getModelName();
//        }
//        $this->model->getClassName() = $this->conversionPascalCase($this->model);

        if (!$this->addModule()) {
            $this->msg('Création de répertoire impossible. Processus interrompu', 'error');
            return false;
        }

        $moduleStructure = Spyc::YAMLLoad(__DIR__.DS.'module.yml');
        $this->addSubDirectories('modules'.DS.$this->name, $moduleStructure, $verbose); //TODO this model

        $path = 'config/menu.yml';
        if (!file_exists($path)) {
            $path = '';
        }

        $menu = Spyc::YAMLLoad($path);
        $modelName = $this->model->getName();
        $newMenu = ['admin' => [$this->name =>['html_accueil_'.$modelName => ['titre' => 'Mes '.$this->model->labelize($modelName).'s']]]];
        //var_dump (strpos(serialize($menu['admin'][$this->name]), serialize($newMenu['admin'][$this->name])) !== false);

        if (!isset($menu['admin'][$this->name]) || strpos(serialize($menu['admin'][$this->name]), serialize($newMenu['admin'][$this->name])) === false) {
            unset($menu['admin'][$this->name]);
            $menu = Spyc::YAMLDump(array_merge_recursive($menu, $newMenu), false, false, true);
            $this->createFile($path, $menu, true);
        }
    }

    function askName() : string
    {
        $name = '';
        while($name === '' || $name === null) {
            $name = readline($this->msg('Veuillez renseigner le nom du module :'));
        }

        return $name;
    }

    function addSubDirectories($path, $structure, $verbose = false)
    {
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                if ($this->ensureDirExists($path.DS.$key, true, $verbose)) {
                    $this->addSubDirectories($path.DS.$key, $value, $verbose);
                }
            } else {
                // crée fichier
                if ($error = $this->ensureFileExists($path.DS.$value, $verbose)) {
                    $this->msg($error, 'error');
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
            if ($verbose) {
               $this->msg('Création du répertoire: '.$dirname, 'success');
            }
            return mkdir($dirname, 0777, $recursive) && is_dir($dirname);
        }
        if ($verbose) {
            $this->msg('*** Répertoire: '.$dirname. ' déja existant', 'neutral');
        }

        return true;
    }

    function ensureFileExists(string $path, $verbose) : string
    {
        $commonPath = str_replace('modules'.DS.$this->name, '', $path);
        $templatePath = __DIR__.DS.'module'.$commonPath;

        if (strpos($path, '.yml') === false) {
            $path = str_replace(['MODULE', 'MODEL', 'TABLE'], [$this->namespaceName, $this->model->getClassName(), $this->model->getName()], $path);
        }

        if (glob($path)) {
            return "Le fichier $path existe déja";
        } else {
            if ($verbose) {
                $this->msg('Création du fichier: '.$path, 'success');
            }
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

            return $this->createFile($path, $text, true);
        }
    }

    function generateConfigFiles(string $templatePath) : string
    {
        $text = '';
        if (glob($templatePath)) {
            $text = file_get_contents($templatePath);
        }

        foreach ($this->model->actions as $action) {
            $templatePerActionPath = str_replace('.', '_'.$action.'.', $templatePath);
            if (glob($templatePerActionPath)) {
                $text .= file_get_contents($templatePerActionPath).PHP_EOL;
            }
        }

        $modelName = '';
        $enums = '';
        if (strpos($templatePath, 'conf.yml') !== false ) {
            $modelName = $this->model->getClassName() ;
            $fields = $this->model->getViewFieldsByType('enum');
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    $enums .= PHP_EOL."aListe-{$this->model->getName()}-{$field['field']}:".PHP_EOL;
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

        foreach ($this->model->actions as $action) {
            $schemaMethodsPerActionPath = str_replace(['MODULE', 'Action.'], ['Module', 'Action' . $this->conversionPascalCase($action) . '.'], $templatePath);
            if (glob($schemaMethodsPerActionPath)) {
                $methodText .= file_get_contents($schemaMethodsPerActionPath) . PHP_EOL;
            }
            if ($action !== 'accueil') {
                $switchCaseList[] = '                ' . $switchCases[$action];
            }
        }

        $switchCaseText = PHP_EOL.implode(PHP_EOL, $switchCaseList);

        if (glob($templatePath)) {

            $default = '';
            $fields = $this->model->getViewFieldsByType('tinyint');
            if (!empty($fields)) {
                $default = ' else {'.PHP_EOL;
                foreach ($fields as $field) {
                    $default .=  str_repeat("\x20", 12)."\$aRetour['oElement']->{$field['name']} = '{$field['default']}';".PHP_EOL;
                }
                $default .= str_repeat("\x20", 8).'}';
            }

            $enumText = '';
            $enumDefault = '';
            $enums = $this->model->getViewFieldsByType('enum');
            if (!empty($enums)) {
                foreach ($enums as $enum) {
                    $enumText .= str_repeat("\x20", 8)."\$aRetour['aSelects']['{$enum['name']}'] = \$this->aGetValeursListeConf('$this->name', '{$this->model->getName()}', '{$enum['field']}');".PHP_EOL;
                    if (isset($enum['default'])) {
//                        $enumDefault .=
//                            str_repeat("\x20", 8). 'foreach ($aRetour[\'aSelects\'][\'' . $enum['name'] . '\'] as $oInfos) {' .PHP_EOL.
//                            str_repeat("\x20", 12).'if ($oInfos[\'valeur\'] == \''.$enum['default'].'\') {'.PHP_EOL.
//                            str_repeat("\x20", 16).'$aRetour[\'oElement\'] = new \\StdClass();'.PHP_EOL.
//                            str_repeat("\x20", 16).'$aRetour[\'oElement\']->'.$enum['name'].' = $oInfos[\'valeur\'];'.PHP_EOL.
//                            str_repeat("\x20", 16).'break;'.PHP_EOL.
//                            str_repeat("\x20", 12).'}'.PHP_EOL.
//                            str_repeat("\x20", 8).'}'.PHP_EOL.PHP_EOL;

                        $enumDefault .=
                            str_repeat("\x20", 8).'$aRetour[\'oElement\'] = new \\StdClass();'.PHP_EOL.
                            str_repeat("\x20", 12).'$aRetour[\'oElement\']->'.$enum['name'].' = \''.$enum['default'].'\';'.PHP_EOL;
                    }
                }
            }


            $methodText = str_replace(['MODEL', 'DEFAULT', 'SELECT'], [$this->model->getClassName(), $default, $enumText.$enumDefault], $methodText);
            $text .= file_get_contents($templatePath);
            $text = str_replace(['MODULE', 'MODEL', '//CASE', '//METHOD'], [$this->namespaceName, $this->model->getClassName(), $switchCaseText, $methodText], $text);
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

        $text = str_replace(['MODULE', 'MODEL', 'TABLE', 'ALIAS', 'PK', 'IDFIELD', '//CHAMPS', 'CHAMPS_SELECT', '//RECHERCHE', '//VALIDATION'], [
            $this->namespaceName,
            $this->model->getClassName(),
            $this->model->getName(),
            $this->model->getAlias(),
            $this->model->getPrimaryKey(), $this->model->getIdField(),
            $this->model->GetMappingChamps(), $this->model->getSqlSelectFields(),
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
            if (array_contains(['edition_multi', 'consultation_multi'], $this->model->actions, ARRAY_ANY)) {
                $multiText = " + '_' + nIdElement";
            } else {
                $multiText = '';
            }


            $templateSpecialPath = str_replace('.', 'Special.', $templatePath);
            if (glob($templateSpecialPath)) {
                $actionMethodText .= file_get_contents($templateSpecialPath);
            }
        }

        foreach ($this->model->actions as $action) {
            $templatePerActionPath = str_replace('.', $this->conversionPascalCase($action) . '.', $templatePath);
            if (glob($templatePerActionPath)) {
                $actionMethodText .= file_get_contents($templatePerActionPath);
            }
        }

        $text = str_replace(['//ACTION', 'MODEL', '/*MULTI*/', 'TABLE'],
            [$actionMethodText, $this->model->getClassName(), $multiText, $this->model->getName()], $text);

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
            $fieldText[] = str_replace(['LABEL', 'FIELD'], [$field['label'], $field['name']], $fieldTemplate);
        }
        $text = file_get_contents($templatePath);
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
                $fieldTemplate = file_get_contents(str_replace('.', '_enum.', $templatePath));
            } elseif (array_contains($field['type'], ['tinyint'])) {
                $fieldTemplate = file_get_contents(str_replace('.', '_tinyint.', $templatePath));
            } elseif (array_contains($field['type'], ['date', 'datetime'])) {
                $fieldTemplate = file_get_contents(str_replace('.', '_date.', $templatePath));
            } elseif (array_contains($field['type'], ['text', 'mediumtext', 'longtext'])) {
                $fieldTemplate = file_get_contents(str_replace('.', '_tinyint.', $templatePath));
            } elseif (array_contains($field['type'], ['float', 'decimal', 'int', 'smallint', 'double'])) {
                $fieldTemplate = file_get_contents(str_replace('.', '_number.', $templatePath));
            } else {
                $fieldTemplate = file_get_contents(str_replace('.', '_string.', $templatePath));
            }

            $fieldText[] = str_replace(['LABEL', 'FIELD', 'TYPE'],
                [$field['label'], $field['name'], $field['type']], $fieldTemplate);
        }

        $text = file_get_contents($templatePath);
        $text = str_replace(['TABLE', 'mODULE', 'FIELDS'], [$this->model->getName(), $this->name, implode(PHP_EOL, $fieldText)], $text);
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
                $fieldTemplate = file_get_contents(str_replace('.', '_enum.', $templatePath));
            } elseif (array_contains($field['type'], ['tinyint'])) {
                $fieldTemplate = file_get_contents(str_replace('.', '_tinyint.', $templatePath));
            } elseif (array_contains($field['type'], ['date', 'datetime'])) {
                $fieldTemplate = file_get_contents(str_replace('.', '_date.', $templatePath));
            } elseif (array_contains($field['type'], ['text', 'mediumtext', 'longtext'])) {
                $fieldTemplate = file_get_contents(str_replace('.', '_tinyint.', $templatePath));
            } elseif (array_contains($field['type'], ['float', 'decimal', 'int', 'smallint', 'double'])) {
                $fieldTemplate = file_get_contents(str_replace('.', '_number.', $templatePath));
            } else {
                $fieldTemplate = file_get_contents(str_replace('.', '_string.', $templatePath));
            }

            $fieldText[] = str_replace(['LABEL', 'FIELD', 'TYPE', 'DEFAULT', 'DEFAUT_OUI', 'DEFAUT_NON'],
                [$field['label'], $field['name'], $field['type'], $field['default'], $field['default_yes'], $field['default_no']], $fieldTemplate);
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

}