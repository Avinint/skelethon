<?php

use Core\ModuleMaker;

class E2DModuleMaker extends ModuleMaker
{
    /**
     * @param $modelName
     * @throws \Exception
     */
    protected function initializeModule($modelName): void
    {
        $this->model = E2DModelMaker::create($modelName, $this);
        $this->template = $this->askTemplate();
        $this->addMenu();
    }

    /**
     * @param string $templatePath
     * @return false|string|string[]
     */
    protected function generateFileContent(string $templatePath)
    {
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

    private function generateConfigFiles(string $selectedTemplatePath) : string
    {
        $texts = [];

        $templatePath = $this->getTrueTemplatePath($selectedTemplatePath);
        if (file_exists($templatePath)) {
            $texts[] = file_get_contents($templatePath);
        }

        foreach ($this->model->actions as $action) {
            $templatePerActionPath = $this->getTrueTemplatePath(str_replace('.', '_'.$action.'.', $selectedTemplatePath));
            if (file_exists($templatePerActionPath)) {
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
     * @param string $selectedTemplatePath
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
            $schemaMethodsPerActionPath = $this->getTrueTemplatePath(str_replace('Action.', 'Action' . $this->conversionPascalCase($action) . '.', $templatePath));
            if (file_exists($schemaMethodsPerActionPath)) {
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

            $fields = $this->model->getViewFields();
            $fieldsText = '';
            foreach ($fields as $field) {
                if ($field['type'] === 'tinyint') {
                    $this->handleControllerBooleanField($field, $exceptions, $defaults);
                    $fieldsText .= str_replace(['COLUMN', 'NAME'], [$field['column'], $field['name']], file($fieldTemplatePath)[0]);

                } elseif (array_contains($field['type'], ['date', 'datetime'])) {
                    $exceptions['aDates'][] = $field['name'];
                    $fieldsText .= str_replace(['COLUMN', 'NAME'], [$field['column'], $field['name']], file($fieldTemplatePath)[1]);
                } elseif ($field['type'] === 'enum') {
                    $this->handleControllerEnumField($enumPath, $field, $allEnumEditLines, $allEnumSearchLines, $defaults);
                    $fieldsText .= str_replace(['COLUMN', 'NAME'], [$field['column'], $field['name']], file($fieldTemplatePath)[0]);

                } elseif (array_contains($field['type'], ['float', 'double', 'decimal'])) {
                    $exceptions['aFloats'][] = $field['name'];
                    $fieldsText .= str_replace(['COLUMN', 'NAME'], [$field['column'], $field['name']], file($fieldTemplatePath)[2]);
                } else {
                    $fieldsText .= str_replace(['COLUMN', 'NAME'], [$field['column'], $field['name']], file($fieldTemplatePath)[0]);
                }
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

            $methodText = str_replace(['MODEL',  '//EDITSELECT', 'EXCEPTIONS', '//SEARCHSELECT', '//DEFAULT', 'CHAMPS'],
                [$this->model->getClassName(), $enumEditText, $exceptionText, $enumSearchText, implode(PHP_EOL, $defaults), $fieldsText], $methodText);
            $text .= file_get_contents($templatePath);
            $concurrentText = $this->model->usesMultiCalques ? file_get_contents(str_replace('Action.', 'ActionMulti.', $templatePath)): '';
            $text = str_replace(['MODULE', 'MODEL', '//CASE', '//MULTI', 'INIT;', '//METHOD'],
                [$this->namespaceName, $this->model->getClassName(), $switchCaseText, $concurrentText, $rechercheActionInitText, $methodText], $text);
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
        if (file_exists($templatePath = $this->getTrueTemplatePath($templatePath))) {
            $text = file_get_contents($templatePath);
        }

        $text = str_replace(['MODULE', 'MODEL', 'TABLE', 'ALIAS', 'PK', 'IDFIELD', '//CHAMPS','//TITRELIBELLE', 'CHAMPS_SELECT', '//RECHERCHE', '//VALIDATION'], [
            $this->namespaceName,
            $this->model->getClassName(),
            $this->model->getName(),
            $this->model->getAlias(),
            $this->model->getPrimaryKey(), $this->model->getIdField(),
            $this->model->getAttributes(), $this->model->getModalTitle(),
            $this->model->getSqlSelectFields(),
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
        if (array_contains(['edition', 'consultation'], $this->model->actions, ARRAY_ANY)) {
            if ($this->model->usesMultiCalques) {
                $multiText = " + '_' + nIdElement";
            } else {
                $multiText = '';
            }
        }

        $noRecherche = true;
        foreach ($this->model->actions as $action) {
            $templatePerActionPath =  $this->getTrueTemplatePath(str_replace('.', $this->conversionPascalCase($action) . '.', $selectedTemplatePath));
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

        $select2Text = PHP_EOL;
        $select2EditText = '';
        if ($fields = $this->model->getViewFieldsByType('enum')) {
            if ($this->model->usesSelect2 && strpos($templatePath, 'Admin') > 0) {

                $select2DefautTemplate = file($this->getTrueTemplatePath(str_replace('.', 'RechercheSelect2.', $selectedTemplatePath)));
                $select2RechercheTemplate = array_shift($select2DefautTemplate);

                $select2EditTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', 'EditionSelect2.', $selectedTemplatePath)));

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
    private function generateListView(string $templatePath)
    {
        $actionBarText = '';
        $actionText = str_repeat("\x20", 16) . '<td class="centre">' . PHP_EOL;

        if (array_contains('edition', $this->model->getActions())) {
            $actionBarTemplatePath = $this->getTrueTemplatePath(str_replace('.', '_actionbar.', $templatePath));
            $actionBarText = file_get_contents($actionBarTemplatePath);
        }

        if (array_contains('consultation', $this->model->getActions())) {
            $consultationTemplatePath = $this->getTrueTemplatePath(str_replace('.', '_consultation.', $templatePath));
            $actionText .= file_get_contents($consultationTemplatePath);
        } else {

            if (array_contains('edition', $this->model->getActions())) {
                $editionTemplatePath = $this->getTrueTemplatePath(str_replace('.', '_edition.', $templatePath));
                $actionText .= file_get_contents($editionTemplatePath);
            }

            if (array_contains('suppression', $this->model->getActions())) {
                $suppressionTemplatePath = $this->getTrueTemplatePath(str_replace('.', '_suppression.', $templatePath));
                $actionText .= file_get_contents($suppressionTemplatePath);
            }
        }

        $actionText .= PHP_EOL . str_repeat("\x20", 16) . '</td>';
        $callbackLigne = '';
        if (array_contains(['consultation', 'edition', 'suppression'], $this->model->getActions())) {
            $callbackLigne = " ligne_callback_TABLE_vCallbackLigneListe";
        }
        $text = file_get_contents($this->getTrueTemplatePath($templatePath));
        $text = str_replace(['ACTION_BAR', 'JSFILE', 'CALLBACKLIGNE', 'MODEL', 'HEADERS', 'ACTION', 'COLUMNS', 'mODULE', 'TABLE', 'NUMCOL'],
            [$actionBarText, $this->model->GetName(), $callbackLigne, $this->model->getClassname(), $this->model->getTableHeaders(),
                $actionText, $this->model->getTableColumns(), $this->name, $this->model->GetName(), $this->model->getColumnNumber()], $text);
        return $text;
    }

    /**
     * @param string $templatePath
     * @return false|string|string[]
     */
    private function generateConsultationView(string $templatePath)
    {
        $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_field.', $templatePath)));
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
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_enum_select2.', $templatePath)));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_enum.', $templatePath)));
                }
            } elseif (array_contains($field['type'], ['tinyint'])) {
                if ($this->model->usesSwitches) {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_tinyint_switch.', $templatePath)));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_tinyint_radio.', $templatePath)));
                }

            } elseif (array_contains($field['type'], ['date', 'datetime'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_date.', $templatePath)));
            } elseif (array_contains($field['type'], ['text', 'mediumtext', 'longtext'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_tinyint.', $templatePath)));
            } elseif (array_contains($field['type'], ['float', 'decimal', 'int', 'smallint', 'double'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_number.', $templatePath)));
            } else {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_string.', $templatePath)));
            }

            $fieldText[] = str_replace(['LABEL', 'FIELD', 'TYPE', 'NAME', 'COLUMN'],
                [$field['label'], $field['field'], $field['type'], $field['name'], $field['column']], $fieldTemplate);
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
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_enum_select2.', $templatePath)));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_enum.', $templatePath)));
                }
            } elseif (array_contains($field['type'], ['tinyint'])) {
                if ($this->model->usesSwitches) {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_tinyint_switch.', $templatePath)));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_tinyint_radio.', $templatePath)));
                }
            } elseif (array_contains($field['type'], ['date', 'datetime'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_date.', $templatePath)));
            } elseif (array_contains($field['type'], ['text', 'mediumtext', 'longtext'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_tinyint.', $templatePath)));
            } elseif (array_contains($field['type'], ['float', 'decimal', 'int', 'smallint', 'double'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_number.', $templatePath)));
            } else {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', '_string.', $templatePath)));
            }

            $defautOui = $field['default'] === '1' ? ' checked' : '';
            $defautNon = $field['default'] === '0' ? ' checked' : '';
            $fieldText[] = str_replace(['LABEL', 'FIELD', 'TYPE', 'DEFAULT', 'NAME', 'COLUMN', 'DEFAUT_OUI', 'DEFAUT_NON'],
                [$field['label'], $field['field'], $field['type'], $field['default'], $field['name'], $field['column'], $defautOui, $defautNon], $fieldTemplate);
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
        $menuPath = 'config/menu.yml';
        if (file_exists($menuPath)) {
            $menu = Spyc::YAMLLoad($menuPath);

        } else {
            $menu = [];
        }

        $subMenu = $this->getSubMenu();

        if (!empty($menu)) {
            if (isset($menu['admin'][$this->name]) && !array_contains($menu['admin'][$this->name], $subMenu['admin'][$this->name], false, true)) {
                unset($menu['admin'][$this->name]);
            }

            if (!isset($menu['admin'][$this->name])) {
                $menu = Spyc::YAMLDump(array_merge_recursive($menu, $subMenu), false, false, true);
                $this->createFile($menuPath, $menu, true);
            }
        } else {
            $menu = Spyc::YAMLDump($subMenu, false, false, true);
            $this->createFile($menuPath, $menu, true);
        }
    }

    /**
     * Retourne le sous-menu intégrant le module au menu principal
     *
     * @return array
     */
    private function getSubMenu(): array
    {
        $template = file_exists(__DIR__ . DS . 'templates' . DS . $this->template . DS . 'menu.yml') ? $this->template : 'standard';
        $label = isset($this->config['titreMenu']) && !empty($this->config['titreMenu']) ? $this->config['titreMenu'] :
            $this->model->labelize('Mes '.$this->model->getName().'s');

        return Spyc::YAMLLoadString(str_replace(['mODULE', 'TABLE', 'LABEL'],
            [$this->name, $this->model->getName(), $label],
            file_get_contents(__DIR__ . DS . 'templates' . DS . $template . DS . 'menu.yml')));
    }

}