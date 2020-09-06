<?php

namespace E2D;

use Core\FileGenerator;

class E2DControllerFileGenerator extends FileGenerator
{
    private string $pascalCaseModuleName;

    public function __construct(string $moduleName, string $pascalCaseModuleName, object $model, string $controllerName)
    {
        parent::__construct($model->fileManager);
        $this->model = $model;
        $this->moduleName = $moduleName;
        $this->controllerName = $controllerName;
        $this->pascalCaseModuleName = $pascalCaseModuleName;
    }
    
    public function generate(string $path) : string
    {
        
        $text = '';
        $methodText = '';
        $noRecherche = true;
        $switchCaseList = [file_get_contents($this->getTrueTemplatePath(str_replace('Action.', 'ActionSwitchCaseAccueil.', $path)))];

        foreach ($this->model->actions as $action) {
            $schemaMethodsPerActionPath = $this->getTrueTemplatePath(str_replace('Action.', 'Action' . $this->pascalize($action) . '.', $path));
            if (file_exists($schemaMethodsPerActionPath)) {
                $methodText .= file_get_contents($schemaMethodsPerActionPath) . PHP_EOL;
            }

            if ($action !== 'accueil') {
                $switchCaseList[] =  file_get_contents($this->getTrueTemplatePath(str_replace('Action.', 'ActionSwitchCase' . $this->pascalize($action) . '.', $path)));
            }

            if ($action === 'recherche') {
                $noRecherche = false;
            }
        }

        $rechercheActionInitPathHandle = $noRecherche ? 'SansFormulaireRecherche' : 'AvecFormulaireRecherche';
        $rechercheActionInitText = file_get_contents($this->getTrueTemplatePath(str_replace('Action.', 'Action' .  $rechercheActionInitPathHandle  . '.', $path)));

        $switchCaseText = PHP_EOL.implode(PHP_EOL, $switchCaseList);

        if ($this->model->usesSelect2) {
            $enumPath = $this->getTrueTemplatePath(str_replace('Action.', 'ActionEnumSelect2.', $path));
        } else {
            $enumPath = $this->getTrueTemplatePath(str_replace('Action.', 'ActionEnum.', $path));
        }

        $fieldTemplatePath = $this->getTrueTemplatePath(str_replace('Action.', 'ActionEditionChamps.', $path));

        if (file_exists($path = $this->getTrueTemplatePath($path))) {
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

            $text .= file_get_contents($path);
            $concurrentText = $this->model->usesMultiCalques ? file_get_contents(str_replace('Action.', 'ActionMulti.', $path)): '';
            $text = str_replace(['MODULE', 'CONTROLLER', 'MODEL', '//CASE', '//MULTI', 'INIT;', '//METHOD'],
                [$this->namespaceName, $this->controllerName, $this->model->getClassName(), $switchCaseText, $concurrentText, $rechercheActionInitText, $methodText], $text);
        }

        return $text;
    }

    /**
     * @param $field
     * @param array $exceptions
     * @param array $defaults
     * @return array
     */
    protected function handleControllerBooleanField($field, array &$exceptions, array &$defaults, $fieldTemplatePath)
    {
        $exceptions['aBooleens'][] = $field['field'];
        $defaultValue = isset($field['default']) ? $field['default'] : 'nc';
        $defaultLines = file(str_replace('ActionEditionChamps.', 'ActionEditionDefaut.',$fieldTemplatePath));
        $defaults[] = str_replace(['FIELD', 'VALUE'], [$field['name'], $defaultValue], $defaultLines[0]);
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
            $this->handleControllerBooleanField($field, $exceptions, $defaults, $fieldTemplatePath);
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

    /**
     * @param $enumPath
     * @param $enum
     * @param array $allEnumEditLines
     * @param array $allEnumSearchLines
     * @param array $enumDefaults
     * @return array
     */
    protected function handleControllerEnumField($enumPath, $enum, array &$allEnumEditLines, array &$allEnumSearchLines, array &$enumDefaults)
    {
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

        $allEnumEditLines[] = str_replace($searches, $replacements, $enumEditionLine);
        $allEnumSearchLines[] = str_replace($searches, $replacements, implode('', $enumSearchLines));
        if ($enum['default']) {
            $enumDefaults[] = str_replace($searches, $replacements, $enumDefault);
        }

        //return $enumSearchLines;
    }

    /**
     * @param $path
     * @return string
     * @throws \Exception
     */
    public function generateHTMLController($path)
    {
        $text = '';
        if (file_exists($templatePath = $this->getTrueTemplatePath($path))) {
            $text = file_get_contents($templatePath);

            $recherche = array_contains('recherche', $this->model->actions) ? '$sFichierContenu = $this->szGetFichierPourInclusion(\'modules\', \'mODULE/vues/recherche_TABLE.html\');
            $oContenu = $this->oGetVue($sFichierContenu);
            $this->objQpModele->find(\'#zone_navigation_2\')->html($oContenu->find(\'body\')->html());' : '';

            $text = str_replace('//RECHERCHE', $recherche, $text);
            $text = str_replace(['MODULE', 'CONTROLLER', 'mODULE', 'TABLE'], [$this->pascalCaseModuleName, $this->controllerName, $this->name, $this->model->getName()], $text);
        }

        return $text;
    }
}