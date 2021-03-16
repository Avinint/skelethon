<?php

namespace E2D;

use Core\App;
use Core\Config;
use Core\FileGenerator;
use Core\FilePath;

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
        $this->model                = $app->getModelMaker();
        $this->moduleName           = $app->getModuleMaker()->getName();
        $this->controllerName       = $app->getModuleMaker()->getControllerName();
        $this->pascalCaseModuleName = $app->getModuleMaker()->getNamespaceName();
    }
    
    public function generate(FilePath $path) : string
    {
        $text = '';
        $templatePath = $this->getTrueTemplatePath($path);
        if (isset($templatePath)) {
            $text = file_get_contents($templatePath);
        }

        $multiText = '';

        if ($this->model->usesMultiCalques && $this->model->hasActions(['edition', 'consultation'])) {
            $multiText = " + '_' + nIdElement";
        }

        $actionMethodText = [];
        $usesCallbackListeElement = $this->app->get('avecCallbackListeElenent') ?? true;
        foreach ($this->model->getActions() as $action) {
            $actionMethodText[] = $action->getJavaScriptMethods($path, $usesCallbackListeElement);
        }
        $actionMethodText = implode('', $actionMethodText);

        if ($path->getName() === 'CONTROLLERAdmin') {

            /* Gén code fermeture calque */
            $closeConsultationModal = '';
            if ($this->model->hasActions(['consultation'])) {
                $closeConsultationModal = PHP_EOL.file_get_contents($this->getTrueTemplatePath($path->add('edition')->add('fermetureCalqueConsultation')));
            }

            /* Gén code si pas d'action recherche */
            if (!$this->model->hasAction('recherche')) {
                $actions = $this->model->getActions();
                $noRechercheText = array_shift($actions)->getNoRechercheText($path);
                $actionMethodText = $noRechercheText.$actionMethodText;
            }

            $select2SearchText = PHP_EOL;
            $select2EditText = '';

            /* Ajout code select2 */
            if ($this->model->usesSelect2) {

                if ($this->model->hasAction('recherche')) {
                    $select2DefautTemplate    = file($this->getTrueTemplatePath($path->get('recherche')->add('select2')));
                    $select2RechercheTemplate = array_shift($select2DefautTemplate);
                    $searchFields = $this->model->getFields('recherche', ['enum', 'parametre']);

                    foreach ($searchFields as $field) {
                        $select2SearchText .= str_replace(['NAME'], [$field->getName()], $select2RechercheTemplate);
                    }

                    $select2SearchText .= implode('', $select2DefautTemplate).PHP_EOL;
                }

                if ($this->model->hasAction('edition')) {
                    $select2EditTemplate = file_get_contents($this->getTrueTemplatePath($path->add('edition')->add('select2')));
                    $editionFields       = $this->model->getFields('edition', ['enum', 'parametre']);
                    foreach ($editionFields as $field) {
                        $select2EditText .= str_replace(['NAME'], [$field->getName()], $select2EditTemplate) . PHP_EOL;
                    }
                }

            }

            $selectAjaxDefinitionText = '';
            $selectAjaxDefinition = [];
            $tinyMCE = '';

            if ($this->app->get('hasManyToOneRelation')) {
                [$select2SearchText, $select2EditText, $selectAjaxDefinitionText] = $this->model->addSelectAjaxToJavaScript($templatePath, $select2SearchText, $select2EditText, $selectAjaxDefinition);
            }

            $callbackLigneListeText = $this->app->get('usesCallbackListeLigne') ? file_get_contents($path->add('avecCallbackLigneListe')) : '';

            $callbackLigneListeText = implode(PHP_EOL,  array_merge([$callbackLigneListeText, $selectAjaxDefinitionText]));
            $bHasConsultation = $this->model->hasAction('consultation');
            $personalizedButtons = file_get_contents($this->getTrueTemplatePath($path->add($bHasConsultation ? 'consultationButton' : 'noConsultationButtons')));

            $champs = $this->app->get('champsTinyMCE') ?: [];

            foreach ($champs as $champ) {
                $tinyMCE .= str_replace('NAME', $champ, file_get_contents($this->getTrueTemplatePath($path->add('edition')->add('appelTinyMCE'))));
            }

            $tinyMCEDef = $this->app->has('champsTinyMCE')  ? file_get_contents($this->getTrueTemplatePath($path->add('edition')->add('definitionTinyMCE'))) : '';



            $text = str_replace([ '/*CALLBACKLIGNELISTE*/', '/*PERSONALIZEBUTTONS*/', '/*MULTIJS*/', '/*ACTION*/',  'CLOSECONSULTATIONMODAL', 'mODULE',
                'CONTROLLER', 'TITRE', '/*MULTI*/', 'TABLE', 'SELECT2EDIT' , 'TINYMCEDEF', 'TINYMCE', 'SELECT2'],
                [$callbackLigneListeText, $personalizedButtons, '', $actionMethodText, $closeConsultationModal, $this->moduleName, $this->controllerName,
                    $this->model->getTitre(), $multiText, $this->model->getName(), $select2EditText, $tinyMCEDef, $tinyMCE, $select2SearchText], $text);

        } else {
            $text = str_replace(['CONTROLLER'],[$this->controllerName], $text);
        }

        return $text;
    }

    public function modify($templatePath, $filePath)
    {
        $textApplyNewJSClass = str_replace('MODEL', $this->model->getClassName(), file_get_contents($this->getTrueTemplatePath($templatePath->add('multiFichier'))));

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