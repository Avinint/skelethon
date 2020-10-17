<?php

namespace E2D;

use Core\FileGenerator;
use Core\FilePath;
use Core\Path;
use Core\PathNode;
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
     * @param $modelName
     * @throws \Exception
     */
    protected function initializeModule(): void
    {
        $this->applyChoicesForAllModules = $this->config->get('memorizeChoices') ?? $this->askApplyChoiceForAllModules();
        $this->initializeFileGenerators();
        $this->initializePaths();
        $this->addMenu();
        $this->addSecurity();
    }

    protected function initializeFileGenerators()
    {

        $this->modelFileGenerator      = new $this->app->modelFileGeneratorClass($this->app);
        $this->controllerFileGenerator = new $this->app->controllerFileGeneratorClass($this->app);
        $this->jsFileGenerator         = new $this->app->jSFileGeneratorClass($this->app);
        $this->configFileGenerator     = new $this->app->configFileGeneratorClass($this->app);
        $this->viewFileGenerator       = new $this->app->viewFileGeneratorClass($this->app);
    }

    protected function executeSpecificModes()
    {
        //if ('addManyToOne' === $this->creationMode)
        return $this->addManyToOne();
    }

    /**
     * @param FilePath $file
     * @return FilePath
     */
    protected function getTrueFilePath(FilePath $file) : FilePath
    {
        if($file->getType() !== 'yml') {
            $file->setName(str_replace(['CONTROLLER', 'MODEL', 'mODEL'], [$this->getControllerName(), $this->model->getClassName(), $this->model->getName()], $file->getName()));
        }
        return $file;
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
        } elseif (strpos($templatePath, 'accueil_mODEL.html')) {
            $text = file_get_contents($this->getTrueTemplatePath($templatePath));
        } elseif (strpos($templatePath, 'liste_mODEL.html')) {
            $text = $this->viewFileGenerator->generate($templatePath);
        } elseif (strpos($templatePath, 'consultation_mODEL.html')) {
            $text = $this->viewFileGenerator->generateConsultationView($templatePath);
        } elseif (strpos($templatePath, 'edition_mODEL.html')) {
            $text = $this->viewFileGenerator->generateEditionView($templatePath);
        } elseif (strpos($templatePath, 'recherche_mODEL.html')) {
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
            $this->fileManager->createFile($this->menuPath, $menu, true);
        }
    }

    /**
     * Retourne le sous-menu intégrant le module au menu principal
     *
     * @return array
     */
    protected function getSubMenu(): array
    {
        $templatePath = $this->getTrueTemplatePath(dirname(dirname(__DIR__)) . DS . 'templates' . DS . $this->app->getFileManager()->getTemplate() . DS . 'menu.yml');
        $label = isset($this->config['titreMenu']) && !empty($this->config['titreMenu']) ? $this->config['titreMenu'] :
            $this->model->getTitre();

        return Spyc::YAMLLoadString(str_replace(['mODULE', 'TABLE', 'LABEL'],
            [$this->name, $this->model->getName(), $label], file_get_contents($templatePath)));
    }

    public function getControllerName($case = 'pascal_case'): string
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

    /**
     * Génère le fichier sécurite ou imprime un snippet à copier coller dans le fichier securite
     */
    private function addSecurity()
    {
        if ($this->config->has('updateSecurity') && !$this->config->get('updateSecurity')) {
            $this->msg('Génération de fichier ' . $this->highlight('securite.yml', 'neutral') . ' désactivée', 'important');
            return;
        }


        if (!file_exists($this->securityPath)) {
            // TODO comportement configurable
            return;
        }

        $security = new E2DSecurityFileGenerator($this->app);
        if ($this->config->get('updateSecurity') === 'generate') {
            $security->generate($this->securityPath);
        } elseif ($this->config->get('updateSecurity') === 'print') {
            $security->print($this->securityPath);
        }

        $this->config->set('updateSecurity', false, $this->model->getName());
    }

    protected function initializePaths($otherModuleName = null)
    {
        $this->setProjectPath();
        $this->setModulePath();
        $this->setMenuPath();
        $this->setSecurityPath();

        $this->templatePath = $this->app->getFileManager()->getTemplatePath()->getChild($this->app->getFileManager()->getTemplate());
        //$this->modulePath->setTwinPath($this->templatePath);
    }

    /**
     * initialise le chemin du menu
     */
    public function setMenuPath(): void
    {
        $this->projectPath->addChild('config')->addFile('menu', 'yml');
        $this->menuPath = $this->projectPath->getChild('config')->getFile('menu');
    }

    /**
     * Initialise le chemin du fichier sécurité
     */
    public function setSecurityPath(): void
    {
        $this->projectPath->getChild('config')->addFile('securite','yml');
        $this->securityPath = $this->projectPath->getChild('config')->getFile('securite');
    }

    /**
     *  initialise le chemin du module
     */
    public function setModulePath()
    {
        $this->projectPath->addChild('modules/' . $this->name, $this->name);
        $this->modulePath = $this->projectPath->getChild($this->name);
    }

    public function setProjectPath()
    {
        $this->projectPath = new Path(getcwd(), 'projectPath');
        $this->app->getFileManager()->setProjectPath($this->projectPath);
    }

}