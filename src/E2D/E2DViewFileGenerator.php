<?php


namespace E2D;


use Core\App;
use Core\Config;
use Core\FileGenerator;
use Core\FilePath;

class E2DViewFileGenerator extends FileGenerator
{
    protected string $moduleName;
    protected string $controllerName;
    protected E2DModelMaker $model;
    protected App $app;

    public function __construct(App $app)
    {
        $this->app                  = $app;
        $this->model                = $app->getModelMaker();
        $this->moduleName           = $app->getModuleMaker()->getName();
        $this->controllerName       = $app->getModuleMaker()->getControllerName();
    }

    public function generate(FilePath $path) : string
    {
        $actionBarText = $this->generateListActionBarText($path);
        $paginationText = '';
        if ($this->app->get('usesPagination') ?? true) {
            $paginationText = file_get_contents($this->getTrueTemplatePath($path->add('pagination')));
        }

        $actionText = $this->generateListActionText($path);

        $tabletagText = $this->generateListTableTag($path);

        $templatePath = $this->getTrueTemplatePath($path);

        $text = file_get_contents($templatePath);
        $text = str_replace(['TABLETAG','ACTION_BAR', 'cONTROLLER', 'MODEL', 'HEADERS', 'ACTION', 'COLUMNS', 'mODULE', 'mODEL', 'NUMCOL', 'PAGINATION'],
            [$tabletagText, $actionBarText, $this->camelize($this->controllerName), $this->model->getClassname(), $this->model->getTableHeaders($templatePath),
                $actionText, $this->model->getTableColumns( $templatePath), $this->moduleName, $this->model->GetName(), $this->model->getColumnNumber(), $paginationText], $text);
        return $text;
    }

    /**
     * @param FilePath $path
     * @return false|string|string[]
     */
    public function generateConsultationView(FilePath $path)
    {
        $text = file_get_contents($this->getTrueTemplatePath($path));
        $text = $this->addModalTitle($text);

        $fieldText = [];
        $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path->add('field')));
        foreach ($this->model->getFields('consultation') as $field) {
            $fieldText[] = $field->getType()->getConsultationView($field, $fieldTemplate);
        }

        $text = str_replace(['mODEL', 'mODULE', 'FIELDS'], [$this->model->getName(), $this->moduleName, implode(PHP_EOL, $fieldText)], $text);
        return $text;
    }

    /**
     * @param FilePath $path
     * @return false|string|string[]
     */
    public function generateEditionView(FilePath $path)
    {
        $fieldText = [];
        foreach ($this->model->getFields('edition') as $field) {
            $fieldTemplate = $field->getType()->getEditionView($path);
            $fieldText[] = str_replace(['LABEL', 'FIELD', 'TYPE', 'NAME', 'COLUMN', 'STEP'],
                [$field->getLabel(), $field->getFormattedName(), $field->getType(), $field->getName(), $field->getColumn(),
                 $field->getStep()], $fieldTemplate);
        }

        $text = file_get_contents($this->getTrueTemplatePath($path));
        $text = $this->addModalTitle($text);
        $text = str_replace(['mODEL', 'MODEL', 'mODULE', 'FIELDS'], [$this->model->getName(), $this->model->getClassname(), $this->moduleName, implode(PHP_EOL, $fieldText)], $text);
        return $text;
    }

    /**
     * @param FilePath $path
     * @return false|string|string[]
     */
    public function generateSearchView(FilePath $path)
    {
        $fieldText = [];
        foreach ($this->model->getFields('recherche') as $field) {
            $fieldTemplate = $field->getType()->getVueRecherche($path);
            $defautOui = $field->getDefaultValue() === '1' ? ' checked' : '';
            $defautNon = $field->getDefaultValue() === '0' ? ' checked' : '';

            $fieldText[] = str_replace(['LABEL', 'FIELD', 'TYPE', 'DEFAULT', 'NAME', 'COLUMN', 'STEP', 'DEFAUT_OUI', 'DEFAUT_NON'],
                [$field->getLabel(), $field->getFormattedName(), $field->getType(), $field->getDefaultValue(), $field->getName(), $field->getColumn(),
                    $field->getStep(), $defautOui, $defautNon], $fieldTemplate);
        }

        $text = file_get_contents($this->getTrueTemplatePath($path));
        $text = str_replace(['mODEL', 'FIELDS'], [$this->model->getName(), implode(PHP_EOL, $fieldText)], $text);
        return $text;
    }

    private function addModalTitle($text)
    {
        if ($this->model->usesMultiCalques) {
            [$search, $replace] = ['h2', 'h2 class="sTitreLibelle"'];
            $pos = strpos($text, $search);
            if ($pos !== false) {
                return substr_replace($text, $replace, $pos, strlen($search));
            }
        }

        return $text;
    }

    /**
     * @param FilePath $path
     * @return string
     * @throws \Exception
     */
    private function generateListActionText(FilePath $path): string
    {
        $actionText = [];
        if ($this->model->hasAction('consultation')) {
            $consultationTemplatePath = $this->getTrueTemplatePath($path->add('consultation'));
            $actionText[] = file_get_contents($consultationTemplatePath);
        } else {
            if ($this->model->hasAction('edition')) {
                $editionTemplatePath = $this->getTrueTemplatePath($path->add('edition'));
                $actionText[] = file_get_contents($editionTemplatePath);
            }

            if ($this->model->hasAction('suppression')) {
                $suppressionTemplatePath = $this->getTrueTemplatePath($path->add('suppression'));
                $actionText[] = file_get_contents($suppressionTemplatePath);
            }
        }

        return str_replace('ACTION', implode(PHP_EOL, $actionText), file_get_contents($this->getTrueTemplatePath($path->add('actionblock'))));
    }

    /**
     * @param FilePath $path
     * @return false|string
     * @throws \Exception
     */
    private function generateListActionBarText(FilePath $path)
    {
        $actionBarText = '';
        if ($this->model->hasActions(['edition', 'consultation'])) {
            $actionBarTemplatePath = $this->getTrueTemplatePath($path->add('actionbar'));
            $actionBarText = file_get_contents($actionBarTemplatePath);

            $actionBarActionsText = [];
            if ($this->model->hasAction('edition')) {
                $actionBarActionsText[] = file_get_contents($this->getTrueTemplatePath($actionBarTemplatePath ->add('ajout')));
            }

            if ($this->model->hasAction('export')) {
                $actionBarActionsText[] = file_get_contents($this->getTrueTemplatePath($actionBarTemplatePath ->add('export')));
            }

            $actionBarText = str_replace('//ACTION_BAR_ACTIONS', $actionBarActionsText ?
                implode(PHP_EOL, $actionBarActionsText).PHP_EOL.str_repeat("\x20", 4) : '', $actionBarText);
        }

        return $actionBarText;
    }

    /**
     * @param FilePath $path
     * @return false|string|string[]
     * @throws \Exception
     */
    private function generateListTableTag(FilePath $path)
    {
        $callbackLigne = '';
        if ($this->model->hasActions(['consultation', 'edition', 'suppression']) && ($this->app->get('usesCallbackListeLigne') ?? true)) {
            $callbackLigne = " ligne_callback_cONTROLLER_vCallbackLigneListe";
        }

        $tabletagSubTemplate = ($this->model->getConfig()->get('avecCallbackListeElenent') ?? true) ?
            'tabletag' : 'tabletag_nocallback';
        $tabletagText = str_replace('CALLBACKLIGNE', $callbackLigne, file_get_contents($this->getTrueTemplatePath($path->add($tabletagSubTemplate))));

        return $tabletagText;
    }

}    