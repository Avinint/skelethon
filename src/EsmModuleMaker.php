<?php


class EsmModuleMaker extends E2DModuleMaker
{

    protected function askTemplate()
    {
        return  'esm';
    }


    /**
     * Vérifie qu'un sous menu correspondant au module existe dans menu.yml et soit conforme
     * Sinon on ajoute le sous-menu idoine
     */
    protected function addMenu(): void
    {
        if (isset($this->config['updateMenu']) && !$this->config['updateMenu']) {
            return;
        }

        if (!file_exists($this->menuPath)) {
            return;
        }

        $menu = Spyc::YAMLLoad($this->menuPath);

        $subMenu = $this->getSubMenu();

        if (!empty($menu)) {
            if (isset($menu[$this->name]['html_accueil_'.$this->model->getName()]) && !array_contains($menu[$this->name]['html_accueil_'.$this->model->getName()], $subMenu[$this->name]['html_accueil_'.$this->model->getName()], false, true)) {
                unset($menu[$this->name]['html_accueil_'.$this->model->getName()]);
            }

            if (!isset($menu[$this->name]['html_accueil_'.$this->model->getName()])) {
                $menu = Spyc::YAMLDump(array_merge_recursive($menu, $subMenu), false, 0, true);
                $this->createFile($this->menuPath, $menu, true);
            }
        } else {
            $menu = Spyc::YAMLDump($subMenu, false, 0, true);
            $this->createFile($$this->menuPath, $menu, true);
        }
    }
}