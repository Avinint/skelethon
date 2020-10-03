<?php


namespace E2D;


use Core\Config;
use Core\FileGenerator;

class E2DViewFileGenerator extends FileGenerator
{
    protected string $moduleName;
    protected string $controllerName;
    protected E2DModelMaker $model;

    public function __construct(string $moduleName, E2DModelMaker $model, string $controllerName, Config $config)
    {
        $this->config = $config;
        parent::__construct($model->getFileManager());
        $this->model = $model;
        $this->moduleName = $moduleName;
        $this->controllerName = $controllerName;
    }

    public function generate(string $path) : string
    {
        $actionBarText = $this->generateListActionBarText($path);

        $actionText = $this->generateListActionText($path);

        $tabletagText = $this->generateListTableTag($path);

        $templatePath = $this->getTrueTemplatePath($path);

        $text = file_get_contents($templatePath);
        //$templatePath = str_replace( '.', '_actionheader.', $path);
        $text = str_replace(['TABLETAG','ACTION_BAR', 'cONTROLLER', 'MODEL', 'HEADERS', 'ACTION', 'COLUMNS', 'mODULE', 'TABLE', 'NUMCOL'],
            [$tabletagText, $actionBarText, $this->camelize($this->controllerName), $this->model->getClassname(), $this->model->getTableHeaders($templatePath),
                $actionText, $this->model->getTableColumns( $templatePath), $this->moduleName, $this->model->GetName(), $this->model->getColumnNumber()], $text);
        return $text;
    }

    /**
     * @param string $path
     * @return false|string|string[]
     */
    public function generateConsultationView(string $path)
    {
        $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_field.'));
        $fieldText = [];
        foreach ($this->model->getFields('consultation') as $field) {
            $fieldText[] = str_replace(['LABEL', 'FIELD'], [$field->getLabel(), $field->getFormattedName()], $fieldTemplate);
        }
        $text = file_get_contents($this->getTrueTemplatePath($path));
        $text = $this->addModalTitle($text);

        $text = str_replace(['TABLE', 'mODULE', 'FIELDS'], [$this->model->getName(), $this->moduleName, implode(PHP_EOL, $fieldText)], $text);
        return $text;
    }

    /**
     * @param string $path
     * @return false|string|string[]
     */
    public function generateEditionView(string $path)
    {
        $fieldText = [];
        foreach ($this->model->getFields('edition') as $field) {
            if ($field->is('enum')) {
                if ($this->model->usesSelect2) {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_enum_select2.'));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_enum.'));
                }
            } elseif ($field->is('bool')) {
                if ($this->model->usesSwitches) {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_bool_switch.'));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_bool_radio.'));
                }
            } elseif ($field->is('foreignKey')) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_enum_select_ajax.'));
            } elseif ($field->is(['date', 'datetime'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_date.'));
            } elseif ($field->is(['text', 'mediumtext', 'longtext'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_text.'));
            } elseif ($field->is(['float', 'decimal', 'tinyint', 'int', 'smallint', 'double'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_number.'));
            } else {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_string.'));
            }

            $fieldText[] = str_replace(['LABEL', 'FIELD', 'TYPE', 'NAME', 'COLUMN', 'STEP'],
                [$field->getLabel(), $field->getFormattedName(), $field->getType(), $field->getName(), $field->getColumn(),
                    $field->getStep()], $fieldTemplate);
        }

        $text = file_get_contents($this->getTrueTemplatePath($path));
        $text = $this->addModalTitle($text);
        $text = str_replace(['TABLE', 'mODULE', 'FIELDS'], [$this->model->getName(), $this->moduleName, implode(PHP_EOL, $fieldText)], $text);
        return $text;
    }

    /**
     * @param string $path
     * @return false|string|string[]
     */
    public function generateSearchView(string $path)
    {
        $fieldText = [];
        foreach ($this->model->getFields('recherche') as $field) {
            if ($field->is('enum')) {
                if ($this->model->usesSelect2) {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_enum_select2.'));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_enum.'));
                }
            } elseif ($field->is('bool')) {
                if ($this->model->usesSwitches) {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_bool_switch.'));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_bool_radio.'));
                }
            } elseif ($field->is('foreignKey')) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_enum_select2.'));
            } elseif ($field->is(['date', 'datetime'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_date.'));
            } elseif ($field->is(['text', 'mediumtext', 'longtext'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_string.'));
            } elseif ($field->is(['float', 'decimal', 'int', 'smallint', 'tinyint', 'double'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_number.'));
            } else {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_string.'));
            }

            $defautOui = $field->getDefaultValue() === '1' ? ' checked' : '';
            $defautNon = $field->getDefaultValue() === '0' ? ' checked' : '';
            $fieldText[] = str_replace(['LABEL', 'FIELD', 'TYPE', 'DEFAULT', 'NAME', 'COLUMN', 'STEP', 'DEFAUT_OUI', 'DEFAUT_NON'],
                [$field->getLabel(), $field->getFormattedName(), $field->getType(), $field->getDefaultValue(), $field->getName(), $field->getColumn(),
                    $field->getStep(), $defautOui, $defautNon], $fieldTemplate);
        }

        $text = file_get_contents($this->getTrueTemplatePath($path));
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
     * @param string $path
     * @return string
     * @throws \Exception
     */
    private function generateListActionText(string $path): string
    {
        $actionText = [];
        if (array_contains('consultation', $this->model->getActions())) {
            $consultationTemplatePath = $this->getTrueTemplatePath($path, '_consultation.');
            $actionText[] = file_get_contents($consultationTemplatePath);
        } else {
            if (array_contains('edition', $this->model->getActions())) {
                $editionTemplatePath = $this->getTrueTemplatePath($path, '_edition.');
                $actionText[] = file_get_contents($editionTemplatePath);
            }

            if (array_contains('suppression', $this->model->getActions())) {
                $suppressionTemplatePath = $this->getTrueTemplatePath($path, '_suppression.');
                $actionText[] = file_get_contents($suppressionTemplatePath);
            }
        }

        return str_replace('ACTION', implode(PHP_EOL, $actionText), file_get_contents($this->getTrueTemplatePath($path,  '_actionblock.')));
    }

    /**
     * @param string $path
     * @return false|string
     * @throws \Exception
     */
    private function generateListActionBarText(string $path)
    {
        $actionBarText = '';
        if (array_contains_array(['edition', 'consultation'], $this->model->getActions(), true)) {
            $actionBarTemplatePath = $this->getTrueTemplatePath($path, '_actionbar.');
            $actionBarText = file_get_contents($actionBarTemplatePath);
        }
        return $actionBarText;
    }

    /**
     * @param string $path
     * @return false|string|string[]
     * @throws \Exception
     */
    private function generateListTableTag(string $path)
    {
        $callbackLigne = '';
        if (array_contains_array(['consultation', 'edition', 'suppression'], $this->model->getActions(), ARRAY_ANY)) {
            $callbackLigne = " ligne_callback_cONTROLLER_vCallbackLigneListe";
        }

        $tabletagSubTemplate = ($this->model->getConfig()->get('noCallbackListeElenent') ?? true) ?
            '_tabletag_nocallback.' : '_tabletag.';
        $tabletagText = str_replace('CALLBACKLIGNE', $callbackLigne, file_get_contents($this->getTrueTemplatePath($path, $tabletagSubTemplate)));
        return $tabletagText;
    }

}    