<?php

namespace Eto;

use E2D\E2DModuleMaker;

class EtoModuleMaker extends E2DModuleMaker
{
    protected function askTemplate()
    {
        return  'etotem';
    }

    /**
     * @param string $templatePath
     * @return false|string|string[]
     */
    protected function generateListView(string $templatePath)
    {
        $actionBarText = '';
        $actionText = str_repeat("\x20", 16) . '<td class="szActions">' . PHP_EOL;

        if (array_contains('edition', $this->model->getActions())) {
            $actionBarTemplatePath = $this->getTrueTemplatePath($templatePath, '_actionbar.');
            $actionBarText = file_get_contents($actionBarTemplatePath);
        }

        if (array_contains('consultation', $this->model->getActions())) {
            $consultationTemplatePath = $this->getTrueTemplatePath($templatePath, '_consultation.');
            $actionText .= file_get_contents($consultationTemplatePath);
        } else {

            if (array_contains('edition', $this->model->getActions())) {
                $editionTemplatePath = $this->getTrueTemplatePath($templatePath, '_edition.');
                $actionText .= file_get_contents($editionTemplatePath);
            }

            if (array_contains('suppression', $this->model->getActions())) {
                $suppressionTemplatePath = $this->getTrueTemplatePath($templatePath, '_suppression.');
                $actionText .= file_get_contents($suppressionTemplatePath);
            }
        }

        $actionText .= PHP_EOL . str_repeat("\x20", 16) . '</td>';
        $callbackLigne = '';
        if (array_contains_array(['consultation', 'edition', 'suppression'], $this->model->getActions(), ARRAY_ANY)) {
            $callbackLigne = " ligne_callback_cONTROLLER_vCallbackLigneListe";
        }
        $text = file_get_contents($this->getTrueTemplatePath($templatePath));
//     <table id="liste_salle_creneau_reservation" class="material-table tableau_donnees liste_salle_creneau_reservation align_middle route_parametrageesm_json_recherche_salle_creneau_reservation variable_1_0 callback_salleCreneauReservation_vCallbackListeElement ligne_callback_salleCreneauReservation_vCallbackLigneListe">
        $text = str_replace(['ACTION_BAR', 'CALLBACKLIGNE', 'cONTROLLER', 'MODEL', 'HEADERS', 'ACTION', 'COLUMNS', 'mODULE', 'TABLE', 'NUMCOL'],
            [$actionBarText, $callbackLigne, $this->getControllerName('camel_case'), $this->model->getClassname(), $this->model->getTableHeaders(),
                $actionText, $this->model->getTableColumns(), $this->name, $this->model->GetName(), $this->model->getColumnNumber()], $text);
        return $text;
    }
}