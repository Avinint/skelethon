<?php

namespace E2D;

use Core\{App, FileGenerator, FilePath, PathNode};

class E2DControllerFileGenerator extends FileGenerator
{
    private string $pascalCaseModuleName;
    /**
     * @var E2DModelMaker
     */
    protected E2DModelMaker $model;
    protected App $app;

    public function __construct(App $app)
    {
        $this->app                  = $app;
        $this->model                = $app->getModelMaker();
        $this->moduleName           = $app->getModuleMaker()->getName();
        $this->controllerName       = $app->getModuleMaker()->getControllerName();
        $this->pascalCaseModuleName = $app->getModuleMaker()->getNamespaceName();
    }
    
    public function generate(FilePath $path) : string
    {
        $text = '';
        $methodText = '';
        $rechercheActionInitPathHandle = 'sansFormulaireRecherche';

        $switchCaseList = [file_get_contents($this->getTrueTemplatePath($path->add('switchCase')->add('accueil')))];

        foreach ($this->model->getActions() as $actionName => $action) {
            $schemaMethodsPerActionPath = $this->getTrueTemplatePath($path->add($this->camelize($action))) ?? null;
            if (isset($schemaMethodsPerActionPath)) {
                $methodText .= file_get_contents($schemaMethodsPerActionPath) . PHP_EOL;
            }

            if ($actionName !== 'accueil') {
                $actionPath = $this->getTrueTemplatePath($path->get('switchCase')->add($this->camelize($action)));
                if (isset($actionPath)) {
                    $switchCaseList[] = file_get_contents($actionPath);
                }
            }

            if ($actionName === 'recherche') {
                $rechercheActionInitPathHandle = 'avecFormulaireRecherche';
            }
        }

        $rechercheActionInitText = file_get_contents($this->getTrueTemplatePath($path->add($rechercheActionInitPathHandle)));

        if (file_exists($path = $this->getTrueTemplatePath($path))) {

            $switchCaseText = PHP_EOL.implode(PHP_EOL, $switchCaseList);

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

            if ($this->model->usesSelect2) {
                $this->enumPath = $this->getTrueTemplatePath($path->add('enum_select2'));
                $this->parametrePath = $this->getTrueTemplatePath($path->add('parametre_select2'));
            } else {
                $this->enumPath = $this->getTrueTemplatePath($path->add('enum_selectMenu'));
                $this->parametrePath = $this->getTrueTemplatePath($path->add('parametre_selectMenu'));
            }

            /**
             * @var E2DField $field
             */
            foreach ($fields as $field) {
                $field->getType()->getDefaultValueForControllerField($field, $defaults, $path->get('edition')->add('champs'));

                $fieldText = $this->handleControllerField($field, $exceptions, $defaults, $path, $allEnumEditLines, $allEnumSearchLines);
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

            $parametreInitLine = str_repeat("\x20", 8) . "\$oParametre = \$this->oNew('Parametre');";
            if ($this->model->getFields('recherche', 'parametre')) {
                array_unshift($allEnumSearchLines, $parametreInitLine);
            }

            if ($this->model->getFields('edition', 'parametre')) {
                array_unshift($allEnumEditLines, $parametreInitLine);
            }
            $enumEditText = implode(PHP_EOL, $allEnumEditLines);
            $enumSearchText = implode(PHP_EOL, $allEnumSearchLines);

//            if ($exceptions) {
//                $exceptionText = ', [';
//                $exceptionArr = [];
//                foreach ($exceptions as $key => $list) {
//                    $exceptionArr[] = "'$key' => ['".implode('\', \'', $list).'\']';
//                }
//                $exceptionText .= implode(',', $exceptionArr).']';
//            }

            $methodText = str_replace(['MODEL',  '//EDITSELECT', 'EXCEPTIONS', '//SEARCHSELECT', '//DEFAULT', '//CHAMPSNOTNULL', '//CHAMPSNULL', 'IDFIELD'],
            [$this->model->getClassName(), $enumEditText, $exceptionText, $enumSearchText, implode(PHP_EOL, $defaults), $fieldsNotNullableText, $fieldsNullableText, $this->model->getIdField()], $methodText);

            $text .= file_get_contents($path);
            if ($this->app->get('usesPagination') ?? true)  {
                $paginationText = file_get_contents($path->add('avecPagination'));
            } else {
                $paginationText = str_replace('PK', $this->model->getPrimaryKey(),file_get_contents($path->add('sansPagination')));
            }
            $concurrentText = $this->model->usesMultiCalques ? file_get_contents($this->getTrueTemplatePath($path->add('multi'))): '';
            $text = str_replace(['//PAGINATION', 'MODULE', 'CONTROLLER', 'MODEL', '//CASE', '//MULTI', 'INIT;', '//METHOD'],
                [$paginationText, $this->pascalCaseModuleName, $this->controllerName, $this->model->getClassName(), $switchCaseText, $concurrentText, $rechercheActionInitText, $methodText], $text);
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
        $defaultLines = file($this->getTrueTemplatePath($fieldTemplatePath->add('defaut')));
        $defaults[] = str_replace(['FIELD', 'VALUE'], [$field->getName(), $defaultValue], $defaultLines[0]);
    }

    /**
     * @param E2DField $field
     * @param array $exceptions
     * @param array $defaults
     * @param PathNode $path
     * @param array $allEnumEditLines
     * @param array $allEnumSearchLines
     * @return string
     */
    private function handleControllerField(E2DField $field, array &$exceptions, array &$defaults, PathNode $path, array &$allEnumEditLines, array &$allEnumSearchLines): string
    {
        $fieldTemplatePath = $path->get('edition')->get('champs');
//        $templateFields = file($this->fieldTemplatePath, FILE_IGNORE_NEW_LINES);
//        $nullableFieldTemplatePath = $this->getTrueTemplatePath($this->fieldTemplatePath->add('nullable'));
//        $templateNullableFields = file_get_contents($nullableFieldTemplatePath);

        $templateRequiredFields = $field->getType()->getRequiredFieldTemplate($fieldTemplatePath );
        $templateNullableFields = $field->getType()->getNullableFieldTemplate($fieldTemplatePath );
//        $defaults[] = $field->getType()->getDefaultValueForControllerField($field, $defaults, $path);
        $fieldsText = $this->buildControllerField($field, $templateRequiredFields, $templateNullableFields);

//        if ($field->is('bool')) {
//            $this->handleControllerBooleanField($field, $exceptions, $defaults, $this->fieldTemplatePath);
//
//            $template = [$templateFields[0], $templateFields[1]];
//            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);
//        } elseif

//        if ($field->is('date')) {
//            $template = [$templateFields[0], $templateFields[2].$templateFields[3]];
//            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);
//            $exceptions['aDates'][] = $field->getName();
//        }  else
//        if ($field->is('datetime')) {
//            $template = [$templateFields[0], $templateFields[2].$templateFields[4]];
//            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);
//            $exceptions['aDateTimes'][] = $field->getName();
//        } elseif ($field->is('time')) {
//            $template = [$templateFields[0], $templateFields[2].$templateFields[5]];
//            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);
//            $exceptions['aTimes'][] = $field->getName();
//        }

//        else
        if ($field->is('enum') || $field->is('parametre')) {


//            $template = [$templateFields[0], $templateFields[1]];
//            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);
            $path = $field->is('enum') ? $this->enumPath : $this->parametrePath;
            $this->handleControllerEnumField($path, $field, $allEnumEditLines, $allEnumSearchLines, $defaults);
//        } elseif ($field->isInteger()) {
//            $template = [$templateFields[0], $templateFields[1]];
//            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);

//        } elseif ($field->isFloat()) {
            //$template = [$templateFields[0], $templateFields[6]];
//            $exceptions['aFloats'][] = $field->getName();
//            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);
        }
//        else {
//            $template = [$templateFields[0], $templateFields[1]];
//            $fieldsText = $this->buildControllerField($field, $template, $templateNullableFields);
//        }

        return $fieldsText;
    }

    /**
     * @param $enumPath
     * @param E2DField $field
     * @param array $allEnumEditLines
     * @param array $allEnumSearchLines
     * @param array $enumDefaults
     * @return array
     */
    protected function handleControllerEnumField($enumPath, E2DField $field, array &$allEnumEditLines, array &$allEnumSearchLines, array &$enumDefaults)
    {
        $enumLines = $enumSearchLines = file($enumPath);
        $enumEditionLines = $enumLines[0];
        $default = $field->getDefaultValue() ?? '';

        // TODO finit d'intégrer les differences pour les champs parametres
        if ($this->model->usesSelect2) {
            if ($default) {
                $enumSearchLines = array_slice($enumLines, 0, 3);
                $enumDefault = $enumLines[3];
            } else {
                $enumSearchLines = array_slice($enumLines, 0, 1);
            }
        } else {
            if ($default) {
                $enumSearchLines = $enumLines;
                $enumDefault = $enumLines[2];
            } else {
                $enumSearchLines = [$enumLines[0]];
            }
        }

        $searches = ['NAME', 'mODULE', 'TABLE', 'COLUMN', 'DEFAULT'];
        $replacements = [$field->getName(), $this->moduleName, $this->model->getName(), $field->getColumn(), $default];

        if ($field->is('parametre')) {
            $searches[] = 'TYPE';
            $replacements[] = $field->getParametre('type');
        }

        $allEnumEditLines[] = str_replace($searches, $replacements, $enumEditionLines);
        $allEnumSearchLines[] = str_replace($searches, $replacements, implode('', $enumSearchLines));


        if ($default) {
            $enumDefaults[] = str_replace($searches, $replacements, $enumDefault);
        }

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
     * @param $path
     * @return string
     * @throws \Exception
     */
    public function generateHTMLController($path)
    {
        $text = '';
        if (file_exists($templatePath = $this->getTrueTemplatePath($path))) {
            $text = file_get_contents($templatePath);

            $recherche = $this->model->hasAction('recherche') ? file_get_contents($this->getTrueTemplatePath($path->add('recherche'))) : '';
            $tinyMCE = $this->model->hasAction('edition') && $this->app->has('champsTinyMCE') ? file_get_contents($this->getTrueTemplatePath($path->add('tinyMCE'))) : '';

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