<?php

namespace Eto;

use E2D\E2DViewFileGenerator;

class EtoViewFileGenerator extends E2DViewFileGenerator
{
//    public function generate(string $path) : string
//    {
//        $actionBarText = $this->generateListActionBarText($path);
//
//        $actionText = $this->generateListActionTexts($path);
//
//
//        $callbackLigne = '';
//        if (array_contains_array(['consultation', 'edition', 'suppression'], $this->model->getActions(), ARRAY_ANY)) {
//            $callbackLigne = " ligne_callback_cONTROLLER_vCallbackLigneListe";
//        }
//
//        $templatePath = $this->getTrueTemplatePath($path);
//        $text = file_get_contents($templatePath);
//        $tabletagSubTemplate = ($this->model->getConfig()->get('noCallbackListeElenent') ?? true) ?
//                '_tabletag_nocallback.' : '_tabletag.';
//
//        $tabletagText = file_get_contents($this->getTrueTemplatePath($path, $tabletagSubTemplate));
//        //$tmplatePath = str_replace( '.', '_actionheader.', $path);
//        $text = str_replace(['TABLETAG','ACTION_BAR', 'CALLBACKLIGNE', 'cONTROLLER', 'MODEL', 'HEADERS', 'ACTION', 'COLUMNS', 'mODULE', 'TABLE', 'NUMCOL'],
//            [$tabletagText, $actionBarText, $callbackLigne, $this->camelize($this->controllerName), $this->model->getClassname(), $this->model->getTableHeaders($templatePath),
//                $actionText, $this->model->getTableColumns( $templatePath), $this->moduleName, $this->model->GetName(), $this->model->getColumnNumber()], $text);
//        return $text;
//    }
//
//    /**
//     * @param string $path
//     * @return string
//     * @throws \Exception
//     */
//    private function generateListActionTexts(string $path): string
//    {
//        $actionText = [];
//        if (array_contains('consultation', $this->model->getActions())) {
//            $consultationTemplatePath = $this->getTrueTemplatePath($path, 'consultation');
//            $actionText[] = file_get_contents($consultationTemplatePath);
//        } else {
//            if (array_contains('edition', $this->model->getActions())) {
//                $editionTemplatePath = $this->getTrueTemplatePath($path, 'edition');
//                $actionText[] = file_get_contents($editionTemplatePath);
//            }
//
//            if (array_contains('suppression', $this->model->getActions())) {
//                $suppressionTemplatePath = $this->getTrueTemplatePath($path, 'suppression');
//                $actionText[] = file_get_contents($suppressionTemplatePath);
//            }
//        }
//
//        return str_replace('ACTION', implode(PHP_EOL, $actionText), file_get_contents($this->getTrueTemplatePath($path,  'actionbloc')));
//    }
//
//    /**
//     * @param string $path
//     * @return false|string
//     * @throws \Exception
//     */
//    private function generateListActionBarText(string $path)
//    {
//        $actionBarText = '';
//        if (array_contains_array(['edition', 'consultation'], $this->model->getActions(), true)) {
//            $actionBarTemplatePath = $this->getTrueTemplatePath($path, 'actionbar');
//            $actionBarText = file_get_contents($actionBarTemplatePath);
//        }
//        return $actionBarText;
//    }
}    