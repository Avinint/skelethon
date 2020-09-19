<?php


namespace E2D;


use Core\FileGenerator;

class E2DViewFileGenerator extends FileGenerator
{
    private string $moduleName;
    private string $controllerName;
    private E2DModelMaker $model;

    public function __construct(string $moduleName, E2DModelMaker $model, string $controllerName)
    {
        parent::__construct($model->fileManager);
        $this->model = $model;
        $this->moduleName = $moduleName;
        $this->controllerName = $controllerName;
    }
    
    public function generate(string $path) : string
    {
        $actionBarText = '';
        $actionText = [];
        if (array_contains_array(['edition', 'consultation'], $this->model->getActions(), true)) {
            $actionBarTemplatePath = $this->getTrueTemplatePath($path, '_actionbar.');
            $actionBarText = file_get_contents($actionBarTemplatePath);
        }

        if (array_contains('consultation', $this->model->getActions())) {
            $consultationTemplatePath = $this->getTrueTemplatePath($path, '_consultation.');
            $actionText[] = file_get_contents($consultationTemplatePath);
        } else {
            if (array_contains('edition', $this->model->getActions())) {
                $editionTemplatePath = $this->getTrueTemplatePath($path, '_edition.');
                $actionText[] .= file_get_contents($editionTemplatePath);
            }

            if (array_contains('suppression', $this->model->getActions())) {
                $suppressionTemplatePath = $this->getTrueTemplatePath($path, '_suppression.');
                $actionText[] .= file_get_contents($suppressionTemplatePath);
            }
        }

        $actionText = str_replace('ACTION', implode(PHP_EOL, $actionText), file_get_contents($this->getTrueTemplatePath($path,  '_actionblock.')));

        $callbackLigne = '';
        if (array_contains_array(['consultation', 'edition', 'suppression'], $this->model->getActions(), ARRAY_ANY)) {
            $callbackLigne = " ligne_callback_cONTROLLER_vCallbackLigneListe";
        }

        $templatePath = $this->getTrueTemplatePath($path);
        $text = file_get_contents($templatePath);
        $tabletagSubTemplate = ($this->model->getConfig()->get('noCallbackListeElenent') ?? true) ?
                '_tabletag_nocallback.' : '_tabletag.';

        $tabletagText = file_get_contents($this->getTrueTemplatePath($path, $tabletagSubTemplate));
        //$t    emplatePath = str_replace( '.', '_actionheader.', $path);
//     <table id="liste_salle_creneau_reservation" class="material-table tableau_donnees liste_salle_creneau_reservation align_middle route_parametrageesm_json_recherche_salle_creneau_reservation variable_1_0 callback_salleCreneauReservation_vCallbackListeElement ligne_callback_salleCreneauReservation_vCallbackLigneListe">
        $text = str_replace(['TABLETAG','ACTION_BAR', 'CALLBACKLIGNE', 'cONTROLLER', 'MODEL', 'HEADERS', 'ACTION', 'COLUMNS', 'mODULE', 'TABLE', 'NUMCOL'],
            [$tabletagText, $actionBarText, $callbackLigne, $this->camelize($this->controllerName), $this->model->getClassname(), $this->model->getTableHeaders($templatePath),
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
        foreach ($this->model->getViewFields() as $field) {
            $fieldText[] = str_replace(['LABEL', 'FIELD'], [$field['label'], $field['field']], $fieldTemplate);
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
        foreach ($this->model->getViewFields() as $field) {
            if (array_contains($field['type'], ['enum'])) {
                if ($this->model->usesSelect2) {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_enum_select2.'));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_enum.'));
                }
            } elseif (array_contains($field['type'], ['bool'])) {
                if ($this->model->usesSwitches) {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_bool_switch.'));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_bool_radio.'));
                }
            } elseif (array_contains($field['type'], ['foreignKey'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_enum_select_ajax.'));
            } elseif (array_contains($field['type'], ['date', 'datetime'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_date.'));
            } elseif (array_contains($field['type'], ['text', 'mediumtext', 'longtext'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_text.'));
            } elseif (array_contains($field['type'], ['float', 'decimal', 'tinyint', 'int', 'smallint', 'double'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_number.'));
            } else {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_string.'));
            }

            $fieldText[] = str_replace(['LABEL', 'FIELD', 'TYPE', 'NAME', 'COLUMN', 'STEP'],
                [$field['label'], $field['field'], $field['type'], $field['name'], $field['column'],
                    (isset($field['step']) ? ' step="'.$field['step'].'"' : '')], $fieldTemplate);
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
        foreach ($this->model->getViewFields() as $field) {
            if (array_contains($field['type'], ['enum'])) {
                if ($this->model->usesSelect2) {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_enum_select2.'));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_enum.'));
                }
            } elseif (array_contains($field['type'], ['bool'])) {
                if ($this->model->usesSwitches) {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_bool_switch.'));
                } else {
                    $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_bool_radio.'));
                }
            } elseif (array_contains($field['type'], ['foreignKey'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_enum_select2.'));
            } elseif (array_contains($field['type'], ['date', 'datetime'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_date.'));
            } elseif (array_contains($field['type'], ['text', 'mediumtext', 'longtext'])) {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_string.'));
            } elseif (array_contains($field['type'], ['float', 'decimal', 'int', 'smallint', 'tinyint', 'double'])) {

                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_number.'));

            } else {
                $fieldTemplate = file_get_contents($this->getTrueTemplatePath($path, '_string.'));
            }

            $defautOui = $field['default'] === '1' ? ' checked' : '';
            $defautNon = $field['default'] === '0' ? ' checked' : '';
            $fieldText[] = str_replace(['LABEL', 'FIELD', 'TYPE', 'DEFAULT', 'NAME', 'COLUMN', 'STEP', 'DEFAUT_OUI', 'DEFAUT_NON'],
                [$field['label'], $field['field'], $field['type'], $field['default'], $field['name'], $field['column'],
                    (isset($field['step']) ? ' step="'.$field['step'].'"' : ''), $defautOui, $defautNon], $fieldTemplate);


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
    
}    