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

//        $template = $this->app->getFileManager()->getTemplate();
        $template = 'etotem';
        $templatePath = $this->app->getFileManager()->getTemplatePath()->addChild($template)->addChild('module/vues')->addFile('menuitem', 'html');
        $menuText = file_get_contents($this->getTrueTemplatePath($templatePath));

        echo PHP_EOL.$this->msg('Menu à recopier dans "/templates/admin-fullscreen/modele.html"') . PHP_EOL .
            str_replace(['mODULE', 'mODEL', 'LABEL'],  [$this->name, $this->model->getName(),
                $this->labelize($this->model->getName().'s')], $menuText) . PHP_EOL.PHP_EOL;
    }

    protected function initializeFileGenerators()
    {
        $this->modelFileGenerator      = new EtoModelFileGenerator($this->app);
        $this->controllerFileGenerator = new EtoControllerFileGenerator($this->app);
        $this->jsFileGenerator         = new EtoJSFileGenerator($this->app);
        $this->configFileGenerator     = new EtoConfigFileGenerator($this->app);
        $this->viewFileGenerator       = new EtoViewFileGenerator($this->app);
    }
}