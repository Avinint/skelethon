<?php

namespace Eto;

use Core\FileManager;
use E2D\E2DModuleMaker;

class EtoModuleMaker extends E2DModuleMaker
{
    /**
     * Ajout menu sous la forme de texte affiché dans la console à copier coller dans le fichier menu
     */
    protected function addMenu(): void
    {
        if ($this->app->has('updateMenu') && !$this->app->get('updateMenu')) {
            $this->msg('Génération de menu désactivée', 'important');
            return;
        }

        $template = $this->app->getFileManager()->getTemplate();
        $menuText = file_get_contents($this->getTrueTemplatePath($this->app->getFileManager()->getTemplatePath()->getChild($template)->addChild('vues')->addFile('menuitem', 'html')));

        echo PHP_EOL.$this->msg('Menu à recopier dans "/templates/admin-fullscreen/modele.html"') . PHP_EOL .
            str_replace(['mODULE', 'cONTROLLER', 'LABEL'],  [$this->name, $this->getControllerName('lower_case'),
                $this->labelize($this->model->getName().'s')], $menuText) . PHP_EOL.PHP_EOL;
    }
}