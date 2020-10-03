<?php

namespace E2D;

use Core\FileGenerator;
use PhpOffice\Common\File;
use \Spyc;
use Core\ModuleMaker;

class E2DModuleMaker extends ModuleMaker
{
    protected $menuPath;
    protected FileGenerator $modelFileGenerator;
    protected FileGenerator $controllerFileGenerator;
    protected FileGenerator $jsFileGenerator;
    protected FileGenerator $configFileGenerator;
    protected FileGenerator $viewFileGenerator;

    /**
     * @param mixed $menuPath
     */
    public function setMenuPath($menuPath): void
    {
        $this->menuPath = getcwd() .DS. $menuPath;
    }

    /**
     * @param $modelName
     * @throws \Exception
     */
    protected function initializeModule($params): void
    {
        $this->applyChoicesForAllModules = $this->config->get('memorizeChoices') ?? $this->askApplyChoiceForAllModules();
        $this->initializeFileGenerators($params);
        $this->setMenuPath($params['menuPath']);
        $this->addMenu();
    }

    protected function initializeFileGenerators($params)
    {
        $modelFileGenerator       = $params['modelFileGenerator'] ?? E2DModelFileGenerator::class;
        $controllerFileGenerator  = $params['controllerFileGenerator'] ?? E2DControllerFileGenerator::class;
        $viewFileGenerator        = $params['viewFileGenerator'] ?? E2DViewFileGenerator::class;
        $jSFileGenerator          = $params['jSFileGenerator'] ?? E2DJSFileGenerator::class;
        $configFileGenerator      = $params['ConfigFileGenerator'] ?? E2DConfigFileGenerator::class;

        $this->modelFileGenerator      = new $modelFileGenerator($this->name, $this->model, $this->config);
        $this->controllerFileGenerator = new $controllerFileGenerator($this->name, $this->namespaceName,$this->model, $this->getControllerName(), $this->config);
        $this->jsFileGenerator         = new $jSFileGenerator($this->name, $this->namespaceName, $this->model, $this->getControllerName(), $this->config);
        $this->configFileGenerator     = new $configFileGenerator($this->name, $this->namespaceName,$this->model, $this->getControllerName(), $this->config);
        $this->viewFileGenerator       = new $viewFileGenerator($this->name, $this->model, $this->getControllerName(), $this->config);
    }

    protected function executeSpecificModes()
    {
        //if ('addManyToOne' === $this->creationMode)
        return $this->addManyToOne();
    }

    /**
     * @param string $path
     * @return string
     */
    protected function getTrueFilePath(string $path) : string
    {
        if (strpos($path, '.yml') === false) {
            $path = str_replace(['CONTROLLER', 'MODEL', 'TABLE'], [$this->getControllerName(), $this->model->getClassName(), $this->model->getName()], $path);
        }
        return $path;
    }

    /**
     * Identifie quels fichiers sont partagés entre plusieurs models et seront mis a jour quand on rajoute un modèle
     *
     * @param $path
     * @return false|int
     */
    protected function fileIsUpdateable($path)
    {
        if ('generate' === $this->creationMode) {
            return false;
        }
        $modes = ['addModel' =>['yml', 'js'], 'addManyToOne' => 'js', ''];
        return 'generate' !== $this->creationMode && preg_match('/\.'.(is_array($modes[$this->creationMode]) ?
                    implode('|', $modes[$this->creationMode]) :
                    $modes[$this->creationMode]).'$/', $path);
    }

    /**
     * @param string $templatePath
     * @return string
     */
    protected function generateFileContent(string $templatePath, string $path) : string
    {
        $text = '';
        if (strpos($templatePath, '.yml')) {
            if ($this->creationMode === 'addModel' && file_exists($path)) {
                $text = $this->configFileGenerator->modify($templatePath, $path);
            } else {
                $text = $this->configFileGenerator->generate($templatePath);
            }
        } elseif (strpos($templatePath, 'Action.class.php')) {
            $text = $this->controllerFileGenerator->generate($templatePath);
        } elseif (strpos($templatePath, 'HTML.class.php')) {
            $text = $this->controllerFileGenerator->generateHTMLController($templatePath);
        } elseif (strpos($templatePath, 'MODEL.class')) {
            $text = $this->modelFileGenerator->generate($templatePath);
        } elseif (strpos($templatePath, '.js')) {
            if ($this->creationMode === 'addModel' && strpos($templatePath, 'CONTROLLER.js')) {
               [$filePath, $text] = $this->jsFileGenerator->modify($templatePath, $path);
                if (array_contains($text, ['error', 'warning'])) {
                    [$message, $type] = [$filePath, $text];
               } else {
                   [$message, $type] = $this->saveFile($filePath, $text);
               }
               $this->msg($message, $type);
            }
            $text = $this->jsFileGenerator->generate($templatePath);
        } elseif (strpos($templatePath, 'accueil_TABLE.html')) {
            $text = file_get_contents($this->getTrueTemplatePath($templatePath));
        } elseif (strpos($templatePath, 'liste_TABLE.html')) {
            $text = $this->viewFileGenerator->generate($templatePath);
        } elseif (strpos($templatePath, 'consultation_TABLE.html')) {
            $text = $this->viewFileGenerator->generateConsultationView($templatePath);
        } elseif (strpos($templatePath, 'edition_TABLE.html')) {
            $text = $this->viewFileGenerator->generateEditionView($templatePath);
        } elseif (strpos($templatePath, 'recherche_TABLE.html')) {
            $text = $this->viewFileGenerator->generateSearchView($templatePath);
        }

        return $text;
    }

    /**
     * Vérifie qu'un sous menu correspondant au module existe dans menu.yml et soit conforme
     * Sinon on ajoute le sous-menu idoine
     */
    protected function addMenu(): void
    {
        if ($this->config->has('updateMenu') && !$this->config->get('updateMenu')) {
            $this->msg('Génération de menu désactivée', 'important');
            return;
        }

        if (!file_exists($this->menuPath)) {
            return;
        }

        $this->addMenuItem();
    }

    /**
     *  Ajoute lien d'accueil dans le menu
     */
    protected function addMenuItem() : void
    {
        $menu = Spyc::YAMLLoad($this->menuPath);
        $subMenu = $this->getSubMenu();

        if (!empty($menu)) {
            if (isset($menu['admin'][$this->name]['html_accueil_'.$this->model->getName()]) &&
                !array_contains_array($menu['admin'][$this->name]['html_accueil_'.$this->model->getName()], $subMenu['admin'][$this->name]['html_accueil_'.$this->model->getName()], ARRAY_ALL, true)) {
                unset($menu['admin'][$this->name]['html_accueil_'.$this->model->getName()]);
            }

            if (!isset($menu['admin'][$this->name]['html_accueil_'.$this->model->getName()])) {
                $menu = Spyc::YAMLDump(array_merge_recursive($menu, $subMenu), false, 0, true);
                $this->fileManager->createFile($this->menuPath, $menu, true);
            }
        } else {
            $menu = Spyc::YAMLDump($subMenu, false, 0, true);
            $this->createFile($this->menuPath, $menu, true);
        }
    }

    /**
     * Retourne le sous-menu intégrant le module au menu principal
     *
     * @return array
     */
    protected function getSubMenu(): array
    {
        $templatePath = $this->getTrueTemplatePath(dirname(dirname(__DIR__)) . DS . 'templates' . DS . $this->getFileManager()->getTemplate() . DS . 'menu.yml');
        $label = isset($this->config['titreMenu']) && !empty($this->config['titreMenu']) ? $this->config['titreMenu'] :
            $this->model->getTitre();

        return Spyc::YAMLLoadString(str_replace(['mODULE', 'TABLE', 'LABEL'],
            [$this->name, $this->model->getName(), $label], file_get_contents($templatePath)));
    }

    protected function getControllerName($case = 'pascal_case'): string
    {
        if ('pascal_case' === $case) {
            return $this->creationMode === 'generate' ? $this->namespaceName : $this->model->getClassName();
        } elseif ('camel_case' === $case) {
            return $this->creationMode === 'generate' ? lcfirst($this->namespaceName) : lcfirst($this->model->getClassName());
        } elseif ('url_case' === $case) {
            return $this->creationMode === 'generate' ? $this->urlize($this->namespaceName) : $this->urlize($this->model->getClassName());
        } else {
            return $this->creationMode === 'generate' ? $this->name : $this->model->getName();
        }
    }

    private function addManyToOne()
    {
        // Get fields that can become select ajax
    }

}