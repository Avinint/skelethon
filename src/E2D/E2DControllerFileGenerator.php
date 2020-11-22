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
            if ($this->app->get('legacy') ?? false) {

                $fieldsText = $this->genererChampsEnregistrementLegacy($fields, $path);
            } else {
                $fieldsText = $this->genererChampsEnregistrement($fields, $path);
            }

            foreach ($fields as $field) {
                if ($field->is('enum') || $field->is('parametre')) {
                    if ($field->getDefaultValue()) {
                        $defaults[] = $field->getType()->getValeurParDefautChampPourDynamisationEditionController($field, $path);
                    }

                    $allEnumEditLines[] = $field->getType()->getChampsPourDynamisationEdition($field, $path);
                    $allEnumSearchLines[] = $field->getType()->getChampsPourDynamisationRecherche($field, $path);
                }
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

            $methodText = str_replace(['MODEL',  '//EDITSELECT', 'EXCEPTIONS', '//SEARCHSELECT', '//DEFAULT', '//CHAMPS', 'IDFIELD'],
            [$this->model->getClassName(), $enumEditText, $exceptionText, $enumSearchText, implode(PHP_EOL, $defaults), $fieldsText, $this->model->getIdField()], $methodText);

            $text .= file_get_contents($path);
            if ($this->app->get('usesPagination') ?? true)  {
                $paginationText = file_get_contents($path->add('avecPagination'));
            } else {
                $paginationText = str_replace('PK', $this->model->getPrimaryKey(),file_get_contents($path->add('sansPagination')));
            }
            $concurrentText = $this->model->usesMultiCalques ? file_get_contents($this->getTrueTemplatePath($path->add('multi'))): '';
            $label = $this->labelize($this->model->getName());

            $text = str_replace(['//PAGINATION', 'MODULE', 'CONTROLLER', 'MODEL', '//CASE', '//MULTI', 'INIT;', '//METHOD', 'LABEL', 'TABLE'],
                [$paginationText, $this->pascalCaseModuleName, $this->controllerName, $this->model->getClassName(),
                    $switchCaseText, $concurrentText, $rechercheActionInitText, $methodText, $label, $this->model->getTableName()], $text);
        }

        return $text;
    }

    /**
     * @param array $fields
     * @param FilePath $path
     * @return string
     */
    private function genererChampsEnregistrement(array $fields, FilePath $path) : string
    {
        $fieldsNotNullableText = '';
        $fieldsNullableText    = '';
        $fieldsNotNullable     = [];
        $fieldsNullable        = [];


        [$fieldsNullable, $fieldsNotNullable] = $this->ajouterChampEnregistrement($fields, $path, $fieldsNullable, $fieldsNotNullable);

        if (!empty($fieldsNullable)) {
            $fieldsNullableText = PHP_EOL . str_repeat("\x20", 8) . '$aChampsNull = [];' .
                PHP_EOL . str_repeat("\x20", 8) . implode(PHP_EOL . str_repeat("\x20", 8), $fieldsNullable) . PHP_EOL;
        }

        if (!empty($fieldsNotNullable)) {
            $fieldsNotNullableText = PHP_EOL . str_repeat("\x20", 12) . implode(PHP_EOL . str_repeat("\x20", 12), $fieldsNotNullable) . PHP_EOL . str_repeat("\x20", 8);
        }

        $templateListeChamps = file_get_contents($this->getTrueTemplatePath($path->add('enregistrement_champs')));
        if (!isset($templateListeChamps)) {
            throw new \Exception('Template inexistant');
        }

        return str_replace(['//CHAMPSNOTNULL', '//CHAMPSNULL'], [$fieldsNotNullableText, $fieldsNullableText], $templateListeChamps);
    }

    /**
     * @param array $fields
     * @param FilePath $path
     * @param array $fieldsNullable
     * @param array $fieldsNotNullable
     * @return array[]
     */
    private function ajouterChampEnregistrement(array $fields, FilePath $path, array $fieldsNullable, array $fieldsNotNullable) : array
    {
        $index = 1;
        foreach ($fields as $field) {
            $fieldTemplatePath      = $this->getTrueTemplatePath($path->add('edition')->add('champs'));
            $templateRequiredFields = $field->getType()->getTemplateChampObligatoire($fieldTemplatePath);
            $templateNullableFields = $field->getType()->getTemplateChampNullable($fieldTemplatePath);

            $indent = str_repeat("\x20", 8);
            if ($field->isNullable()) {
                $fieldsNullable[] = str_replace(['VALUE', 'COLUMN', 'NAME', PHP_EOL],
                        [$templateRequiredFields[$index], $field->getColumn(), $field->getName(), PHP_EOL . $indent], $templateNullableFields) . PHP_EOL;
            } else {
                $fieldsNotNullable[] = str_replace(['VALUE', 'COLUMN', 'NAME'], [$templateRequiredFields[$index], $field->getColumn(), $field->getName()], $templateRequiredFields[0]);
            }
        }

        return [$fieldsNullable, $fieldsNotNullable];
    }

    //////// LEGACY !!!!!!!!!!!!!!!!!!!!!!!

    /**
     * @param array $fields
     * @param FilePath $path
     * @return string
     */
    private function genererChampsEnregistrementLegacy(array $fields, FilePath $path) : string
    {
        $fieldsNotNullableText = '';
        $fieldsNullableText    = '';
        $fieldsNotNullable     = [];
        $fieldsNullable        = [];

        /**
         * @var E2DField $field
         */
        [$fieldsNullable, $fieldsNotNullable] = $this->ajouterChampEnregistrementLegacy($fields, $path, $fieldsNullable, $fieldsNotNullable);

        if (!empty($fieldsNullable)) {
            $fieldsNullableText = PHP_EOL . str_repeat("\x20", 8) . implode(PHP_EOL . str_repeat("\x20", 8), $fieldsNullable) . PHP_EOL;
        }

        if (!empty($fieldsNotNullable)) {
            $fieldsNotNullableText = PHP_EOL . str_repeat("\x20", 8) . implode(PHP_EOL . str_repeat("\x20", 8), $fieldsNotNullable) . PHP_EOL . str_repeat("\x20", 8);
        }

        return $fieldsNotNullableText . $fieldsNullableText;
    }

    /**
     * @param array $fields
     * @param FilePath $path
     * @param array $fieldsNullable
     * @param array $fieldsNotNullable
     * @return array[]
     */
    private function ajouterChampEnregistrementLegacy(array $fields, FilePath $path, array $fieldsNullable, array $fieldsNotNullable) : array
    {
        $index = 0;
        foreach ($fields as $field) {
            $fieldTemplatePath      = $this->getTrueTemplatePath($path->add('edition')->add('champs'));
            $templateRequiredFields = $field->getType()->getTemplateChampObligatoireLegacy($fieldTemplatePath);
            $templateNullableFields = $field->getType()->getTemplateChampNullableLegacy($fieldTemplatePath);

            $indent = str_repeat("\x20", 8);
            if ($field->isNullable()) {
                $fieldsNullable[] = str_replace(['VALUE', 'COLUMN', 'NAME', PHP_EOL],
                    [$templateRequiredFields[$index], $field->getColumn(), $field->getName(), PHP_EOL . $indent], $templateNullableFields);
            } else {
                $fieldsNotNullable[] = str_replace(['VALUE', 'COLUMN', 'NAME'], [$templateRequiredFields[$index], $field->getColumn(), $field->getName()], $templateRequiredFields[0]);
            }
        }
        return [$fieldsNullable, $fieldsNotNullable];
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

}