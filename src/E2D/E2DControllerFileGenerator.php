<?php

namespace E2D;

use Core\Config;
use Core\FileGenerator;
use E2d\E2DField;
class E2DControllerFileGenerator extends FileGenerator
{
    private string $pascalCaseModuleName;
    /**
     * @var E2DModelMaker
     */
    private E2DModelMaker $model;

    public function __construct(string $moduleName, string $pascalCaseModuleName, E2DModelMaker $model, string $controllerName, Config $config)
    {
        $this->config = $config;
        parent::__construct($model->getFileManager());
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

            $fields = $this->model->getFields('edition');
            $fieldsNotNullableText = '';
            $fieldsNotNullable = [];
            $fieldsNullableText = '';
            $fieldsNullable = [];

            /**
             * @var E2DField $field
             */
            foreach ($fields as $field) {
                $fieldText = $this->handleControllerField($field, $exceptions, $defaults, $fieldTemplatePath,$enumPath, $allEnumEditLines, $allEnumSearchLines);
                if ($field->isNullable() === true) {
                    $fieldsNullable[] = $fieldText.PHP_EOL;
                } else {
                    $fieldsNotNullable[] = $fieldText;
                }
            }

            if (!empty($fieldsNullable)) {
                $fieldsNullableText = PHP_EOL.str_repeat("\x20", 8).'$aChampsNull = [];'.
                    PHP_EOL.str_repeat("\x20", 8).implode(PHP_EOL.str_repeat("\x20", 8), $fieldsNullable).PHP_EOL;
            }

            if (!empty($fieldsNotNullable)) {
                $fieldsNotNullableText = PHP_EOL.str_repeat("\x20", 12).implode(PHP_EOL.str_repeat("\x20", 12), $fieldsNotNullable).PHP_EOL.str_repeat("\x20", 8);
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

                $methodText = str_replace(['MODEL',  '//EDITSELECT', 'EXCEPTIONS', '//SEARCHSELECT', '//DEFAULT', '//CHAMPSNOTNULL', '//CHAMPSNULL', 'IDFIELD'],
                [$this->model->getClassName(), $enumEditText, $exceptionText, $enumSearchText, implode(PHP_EOL, $defaults), $fieldsNotNullableText, $fieldsNullableText, $this->model->getIdField()], $methodText);

            $text .= file_get_contents($path);
            $concurrentText = $this->model->usesMultiCalques ? file_get_contents(str_replace('Action.', 'ActionMulti.', $path)): '';
            $text = str_replace(['MODULE', 'CONTROLLER', 'MODEL', '//CASE', '//MULTI', 'INIT;', '//METHOD'],
                [$this->pascalCaseModuleName, $this->controllerName, $this->model->getClassName(), $switchCaseText, $concurrentText, $rechercheActionInitText, $methodText], $text);
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
        $exceptions['aBooleens'][] = $field->getFormattedName();
        $defaultValue = $field->getDefaultValue() ?? 'nc';
        $defaultLines = file(str_replace('ActionEditionChamps.', 'ActionEditionDefaut.',$fieldTemplatePath));
        $defaults[] = str_replace(['FIELD', 'VALUE'], [$field->getName(), $defaultValue], $defaultLines[0]);
    }

    /**
     * @param $field
     * @param array $exceptions
     * @param array $defaults
     * @param $fieldTemplatePath
     * @param string $fieldsNotNullableText
     * @param $enumPath
     * @param array $allEnumEditLines
     * @param array $allEnumSearchLines
     */
    private function handleControllerField(E2DField $field, array &$exceptions, array &$defaults, $fieldTemplatePath, $enumPath, array &$allEnumEditLines, array &$allEnumSearchLines): string
    {
        $templateFields = file($fieldTemplatePath, FILE_IGNORE_NEW_LINES);
        $nullableFieldTemplatePath = str_replace('Champs.', 'ChampsNullable.', $fieldTemplatePath);
        $templateNullableFields = file_get_contents($nullableFieldTemplatePath);
        if ($field->is('bool')) {
            $this->handleControllerBooleanField($field, $exceptions, $defaults, $fieldTemplatePath);
            $template = [$templateFields[0], $templateFields[1]];
            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);
        } elseif ($field->is('date')) {
            $template = [$templateFields[0], $templateFields[2].$templateFields[3]];
            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);
            $exceptions['aDates'][] = $field->getName();
        }  elseif ($field->is('datetime')) {
            $template = [$templateFields[0], $templateFields[2].$templateFields[4]];
            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);
            $exceptions['aDateTimes'][] = $field->getName();
        } elseif ($field->is('time')) {
            $template = [$templateFields[0], $templateFields[2].$templateFields[5]];
            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);
            $exceptions['aTimes'][] = $field->getName();
        }

        elseif ($field->is('enum')) {
            $template = [$templateFields[0], $templateFields[1]];
            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);
            $this->handleControllerEnumField($enumPath, $field, $allEnumEditLines, $allEnumSearchLines, $defaults);
        } elseif ($field->isInteger()) {
            $template = [$templateFields[0], $templateFields[1]];
            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);
        } elseif ($field->isFloat()) {
            $template = [$templateFields[0], $templateFields[6]];
            $exceptions['aFloats'][] = $field->getName();
            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);
        } else {
            $template = [$templateFields[0], $templateFields[1]];
            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);
        }

        return $fieldsText;
    }

    /**
     * @param $field
     * @param $fieldTemplatePath
     * @param string $fieldsNotNullableText
     * @return string
     */
    protected function generateControllerIntegerField(E2DField $field, $fieldTemplatePath): string
    {
        return str_replace(['COLUMN', 'NAME'], [$field->getColumn(), $field->getName()], file($fieldTemplatePath)[0]);
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
        $default = $enum->getDefaultValue() ?? '';

        if ($default) {
            $enumSearchLines = $enumLines;
            $enumDefault = $enumLines[2];
        } else {
            $enumSearchLines = [$enumLines[0]];
        }

        if ($this->model->usesSelect2) {
            if ($default) {
                $enumSearchLines = array_slice($enumLines, 0, 3);
                $enumDefault = $enumLines[3];
            } else {
                $enumSearchLines = array_slice($enumLines, 0, 1);
            }
        }

        $searches = ['NAME', 'mODULE', 'TABLE', 'COLUMN', 'DEFAULT'];
        $replacements = [$enum->getName(), $this->moduleName, $this->model->getName(), $enum->getColumn(), $default];

        $allEnumEditLines[] = str_replace($searches, $replacements, $enumEditionLine);
        $allEnumSearchLines[] = str_replace($searches, $replacements, implode('', $enumSearchLines));
        if ($default) {
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

            $recherche = array_contains('recherche', $this->model->actions) ? file_get_contents($this->getTrueTemplatePath($path, 'Recherche.class.php', '.class.php')) : '';
            $tinyMCE =  array_contains('edition', $this->model->actions) && $this->model->getConfig()->has('champsTinyMCE') ? file_get_contents($this->getTrueTemplatePath($path, 'TinyMCE.class.php', '.class.php')) : '';

            $text = str_replace(['//RECHERCHE', '//TINYMCE'], [$recherche, $tinyMCE], $text);
            $text = str_replace(['MODULE', 'CONTROLLER', 'mODULE', 'TABLE'], [$this->pascalCaseModuleName, $this->controllerName, $this->moduleName, $this->model->getName()], $text);
        }

        return $text;
    }

    /**
     * @param $field
     * @param string $template
     * @param string $templateNullableFields
     * @param string $fieldsText
     * @return string
     */
    private function buildControllerField($field, array $template, string $templateNullableFields): string
    {
        $indent = str_repeat("\x20", 8);
        if ($field->isNullable()) {
            $fieldsText = str_replace(['VALUE', 'COLUMN', 'NAME', PHP_EOL],
                [$template[1], $field->getColumn(), $field->getName(), PHP_EOL.$indent], $templateNullableFields);
        } else {
            $fieldsText = str_replace(['VALUE', 'COLUMN', 'NAME'], [$template[1], $field->getColumn(), $field->getName()], $template[0]);
        }

        return $fieldsText;
    }
}