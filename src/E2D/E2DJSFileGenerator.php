<?php

namespace E2D;

use Core\App;
use Core\Config;
use Core\FileGenerator;

class E2DJSFileGenerator extends FileGenerator
{
    /**
     * @var E2DModelMaker
     */
    private E2DModelMaker $model;
    private string $moduleName;
    private string $pascalCaseModuleName;
    protected App $app;

    public function __construct(App $app)
    {
        $this->app                  = $app;
        $this->config               = $app->getConfig();
//        parent::__construct($app->getFileManager());
        $this->model                = $app->getModelMaker();
        $this->moduleName           = $app->getModuleMaker()->getName();
        $this->controllerName       = $app->getModuleMaker()->getControllerName();
        $this->pascalCaseModuleName = $app->getModuleMaker()->getNamespaceName();
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
        $usesRechercheNoCallback = $this->app->getConfig()->get('noCallbackListeElenent') ?? true;
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

        if ($this->model->usesSelect2 && strpos($templatePath, 'Admin') > 0) {

            $select2DefautTemplate = file($this->getTrueTemplatePath($path, 'RechercheSelect2.'));
            $select2RechercheTemplate = array_shift($select2DefautTemplate);
            $select2EditTemplate = file_get_contents($this->getTrueTemplatePath($path, 'EditionSelect2.'));

            $editionFields = $this->model->getFields('edition', ['enum', 'parametre']);
            $searchFields = $this->model->getFields('recherche', ['enum', 'parametre']);

            foreach ($editionFields as $field) {
                $select2EditText .= str_replace(['NAME'], [$field->getName()], $select2EditTemplate).PHP_EOL;
            }

            foreach ($searchFields as $field) {
                $select2SearchText .= str_replace(['NAME'], [$field->getName()], $select2RechercheTemplate);
            }

            $select2SearchText .= implode('', $select2DefautTemplate).PHP_EOL;
        }

        $selectAjaxDefinitionText = '';
        $selectAjaxDefinition = [];
        $personalizeButtons = '';
        $tinyMCE = '';
        $tinyMCEDef = '';
        if (strpos($templatePath, 'Admin') > 0) {
            if ($this->config->get('hasManyToOneRelation')) {
                [$select2SearchText, $select2EditText, $selectAjaxDefinitionText] = $this->model->addSelectAjaxToJavaScript($templatePath, $select2SearchText, $select2EditText, $selectAjaxDefinition);
            }

            $personalizedButtonsTemplateSuffix = array_contains('consultation', $this->model->getActions()) ? 'ConsultationButton.' : 'NoConsultationButtons.';
            $personalizeButtons = file_get_contents($this->getTrueTemplatePath(str_replace('.', $personalizedButtonsTemplateSuffix, $path)));

            $champs = $this->app->getConfig()->get('champsTinyMCE') ?: [];
            foreach ($champs as $champ) {
                $tinyMCE .= str_replace('NAME', $champ, file_get_contents($this->getTrueTemplatePath($path, 'EditionAppelTinyMCE.')));
            }

            $tinyMCEDef = $this->app->getConfig()->has('champsTinyMCE')  ? file_get_contents($this->getTrueTemplatePath($path, 'EditionDefinitionTinyMCE.')) : '';
        }

        $text = str_replace([ '/*PERSONALIZEBUTTONS*/', '/*MULTIJS*/', '/*ACTION*/',  'CLOSECONSULTATIONMODAL', 'mODULE',
            'CONTROLLER', 'TITRE', '/*MULTI*/', 'TABLE', 'SELECT2EDIT' , 'TINYMCEDEF', 'TINYMCE', 'SELECT2', 'SELECTAJAX',],
            [$personalizeButtons, '', $actionMethodText, $closeConsultationModal, $this->moduleName, $this->controllerName,
                $this->model->getTitre(), $multiText, $this->model->getName(), $select2EditText, $tinyMCEDef, $tinyMCE, $select2SearchText, $selectAjaxDefinitionText], $text);

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