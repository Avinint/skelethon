<?php

namespace E2D;

use Core\FileGenerator;

class E2DJSFileGenerator extends FileGenerator
{
    /**
     * @var E2DModelMaker
     */
    private E2DModelMaker $model;
    private string $moduleName;
    private string $pascalCaseModuleName;

    public function __construct(string $moduleName, string $pascalCaseModuleName, E2DModelMaker $model, string $controllerName)
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
        $templatePath = $this->getTrueTemplatePath($path);
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

        if (array_contains_array(['edition', 'consultation'], $this->model->actions, ARRAY_ALL) && strpos($path, 'Admin')) {
            $closeConsultationModal = PHP_EOL.file_get_contents($this->getTrueTemplatePath(str_replace('.', 'EditionFermetureCalqueConsultation.', $path)));
        } else {
            $closeConsultationModal = '';
        }

        $noRecherche = true;
        $usesRechercheNoCallback = $this->model->getConfig()->get('noCallbackListeElenent') ?? true;
        foreach ($this->model->actions as $action) {
            $templatePerActionPath =  $this->getTrueTemplatePath($path, $this->pascalize($action) . '.');
            if ($action === 'recherche') {
                $noRecherche = false;
                if ($usesRechercheNoCallback) {
                    $templatePerActionPath =  $this->getTrueTemplatePath($templatePerActionPath, '_nocallback.');
                }
            }

            if (file_exists($templatePerActionPath)) {
                $actionMethodText .= file_get_contents($templatePerActionPath);
            }
        }

        if ($noRecherche) {
            $noRechercheText = file_get_contents($this->getTrueTemplatePath($path, 'NoRecherche.'));
            $actionMethodText = $noRechercheText.$actionMethodText;
        }

        $select2SearchText = PHP_EOL;
        $select2EditText = '';
        if ($fields = $this->model->getViewFieldsByType('enum')) {
            if ($this->model->usesSelect2 && strpos($templatePath, 'Admin') > 0) {

                $select2DefautTemplate = file($this->getTrueTemplatePath(str_replace('.', 'RechercheSelect2.', $path)));
                $select2RechercheTemplate = array_shift($select2DefautTemplate);

                $select2EditTemplate = file_get_contents($this->getTrueTemplatePath(str_replace('.', 'EditionSelect2.', $path)));

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
                        $foreignClassName = substr($field['manyToOne']['childTableAlias'], 1);
                        $label = $field['manyToOne']['label'];
                        if (is_array($label)) {
                            $label = $this->model->generateConcatenatedColumn($label);
                        }

                        $ajaxSearchTextTemp = PHP_EOL.file_get_contents($this->getTrueTemplatePath(str_replace('.', 'RechercheSelectAjaxCall.', $path)));
                        $select2SearchText .= str_replace(['MODEL', 'FORM', 'NAME', 'ALLOWCLEAR'], [$foreignClassName, 'eFormulaire', $field['name'], 'true'], $ajaxSearchTextTemp);
                        $allowClear = $field['is_nullable'] ? 'true' : 'false';

                        $selectAjaxCallEditTextTemp = PHP_EOL.file_get_contents($this->getTrueTemplatePath(str_replace('.', 'EditionSelectAjaxCall.', $path)));


                        $select2EditText .= str_replace(['MODEL', 'FORM', 'NAME', 'FIELD', 'ALLOWCLEAR'], [$foreignClassName, 'oParams.eFormulaire', $field['name'], $field['field'], $allowClear], $selectAjaxCallEditTextTemp);

                        $selectAjaxDefinitionTemp = file_get_contents($this->getTrueTemplatePath(str_replace('.', 'SelectAjaxDefinition.', $path)));
                        $selectAjaxDefinition[] = str_replace(['MODEL', 'PK', 'LABEL', 'TABLE', 'ORDERBY'],
                            [$foreignClassName, $field['column'], $label, $field['manyToOne']['table'], $field['column']], $selectAjaxDefinitionTemp);
                    }

                    $selectAjaxDefinitionText = PHP_EOL . implode(PHP_EOL, $selectAjaxDefinition) . PHP_EOL;


                }
            }

            $personalizedButtonsTemplateSuffix = array_contains('consultation', $this->model->getActions()) ? 'ConsultationButton.' : 'NoConsultationButtons.';
            $personalizeButtons = file_get_contents($this->getTrueTemplatePath(str_replace('.', $personalizedButtonsTemplateSuffix, $path)));
        }

        $text = str_replace([ '/*PERSONALIZEBUTTONS*/', '/*MULTIJS*/', '/*ACTION*/',  'CLOSECONSULTATIONMODAL', 'mODULE',
            'CONTROLLER', 'TITRE', '/*MULTI*/', 'TABLE', 'SELECT2EDIT', 'SELECT2', 'SELECTAJAX',],
            [$personalizeButtons, '', $actionMethodText, $closeConsultationModal, $this->moduleName, $this->controllerName,
                $this->model->getTitre(), $multiText, $this->model->getName(), $select2EditText, $select2SearchText, $selectAjaxDefinitionText], $text);

        return $text;
    }

    public function modify($templatePath, $filePath)
    {
        $textApplyNewJSClass = str_replace('MODEL', $this->model->getClassName(), file_get_contents($this->getTrueTemplatePath($templatePath, 'MultiFichier.')));
        $filePath  = str_replace($this->model->getClassName(), $this->pascalCaseModuleName, $filePath);

        if (file_exists($filePath)) {
            $text = file_get_contents($filePath);
            if (strpos($text, $this->model->getClassName()) === false) {
                $text = str_replace_first('if', $textApplyNewJSClass, $text);

                return [$filePath, $text];
            } else {

                return ['Le fichier '.$this->highlight($filePath, 'info').' n\'est pas mis à jour', 'warning'];
            }
        }

        return ['Fichier invalide', 'error'];
    }
}