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
     * @var Path
     */
    protected Path $projectPath;


    /**
     * @param $modelName
     * @throws \Exception
     */
    protected function initializeModule(): void
    {
        $this->applyChoicesForAllModules = $this->app->get('memorizeChoices') ?? $this->askApplyChoiceForAllModules();
        $this->initializeFileGenerators();
        $this->initializePaths();
        $this->addMenu();
        $this->addSecurity();
        if ($this->app->get('sans_generation') ?? false) {
            $this->msg('Génération des fichiers désactivées, affichage du code seulement', 'error');
            exit();
        }
    }

    protected function initializeFileGenerators()
    {
        $this->modelFileGenerator      = new E2DModelFileGenerator($this->app);
        $this->controllerFileGenerator = new E2DControllerFileGenerator($this->app);
        $this->jsFileGenerator         = new E2DJSFileGenerator($this->app);
        $this->configFileGenerator     = new E2DConfigFileGenerator($this->app);
        $this->viewFileGenerator       = new E2DViewFileGenerator($this->app);
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
     * @param FilePath $templatePath
     * @param FilePath $path
     * @return string
     */
    protected function generateFileContent(FilePath $templatePath, FilePath $path) : string
    {
        $text = '';

//        var_dump($templatePath->getType());
//        var_dump($templatePath->getName());

        if ($templatePath->getType() === 'yml') {
            $text = $this->handleConfigFiles($templatePath, $path);
        } elseif ($templatePath->getType() === 'php') {
            $text = $this->generatePHPClass($templatePath);
        } elseif ($templatePath->getType() === 'js') {
            $text = $this->handleJavaScriptFiles($templatePath, $path);
        } elseif ($templatePath->getType() === 'html') {
            $text = $this->generateView($templatePath);
        }
//        var_dump($text);

        return $text;
    }

    protected function handleConfigFiles($templatePath, $path)
    {
        if ($this->creationMode === 'addModel' && file_exists($path)) {
            $text = $this->configFileGenerator->modify($templatePath, $path);
        } else {
            $text = $this->configFileGenerator->generate($templatePath);
        }

        return $text;
    }

    protected function generatePHPClass(FilePath $templatePath)
    {
        if (strpos($templatePath, 'Action.')) {
            $text = $this->controllerFileGenerator->generate($templatePath);
        } elseif (strpos($templatePath, 'HTML.')) {
            $text = $this->controllerFileGenerator->generateHTMLController($templatePath);
        } elseif ('MODELMapping' === $templatePath->getName()) {
            $text = $this->modelFileGenerator->generateMapping($templatePath, $this->model->getTableName());
        } elseif ('MODEL' === $templatePath->getName()) {
            $text = $this->modelFileGenerator->generate($templatePath);
        }

        return $text;
    }

    protected function generateView(FilePath $templatePath)
    {
        if (strpos($templatePath, 'accueil_mODEL.html')) {
            $text = file_get_contents($this->getTrueTemplatePath($this->getTrueTemplatePath($templatePath)));
        } elseif (strpos($templatePath, 'liste_mODEL.html')) {
            $text = $this->viewFileGenerator->generate($this->getTrueTemplatePath($templatePath));
        } elseif (strpos($templatePath, 'consultation_mODEL.html')) {
            $text = $this->viewFileGenerator->generateConsultationView($this->getTrueTemplatePath($templatePath));
        } elseif (strpos($templatePath, 'edition_mODEL.html')) {
            $text = $this->viewFileGenerator->generateEditionView($this->getTrueTemplatePath($templatePath));
        } elseif (strpos($templatePath, 'recherche_mODEL.html')) {
            $text = $this->viewFileGenerator->generateSearchView($this->getTrueTemplatePath($templatePath));
        }

        return $text;
    }

    protected function handleJavaScriptFiles($templatePath, $path)
    {
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

        return $text;
    }

    /**
     * Vérifie qu'un sous menu correspondant au module existe dans menu.yml et soit conforme
     * Sinon on ajoute le sous-menu idoine
     */
    protected function addMenu(): void
    {
        if ($this->app->has('updateMenu') && !$this->app->get('updateMenu')) {
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
                $this->app->getFileManager()->createFile($this->menuPath, $menu, true);
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
        $templatePath = $this->getTrueTemplatePath($this->subMenuPath);
        $label = $this->app->has('titreMenu') && $this->app->get('titreMenu') ? : $this->model->getTitre();

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
        } elseif ('lower_case' === $case) {
            return $this->creationMode === 'generate' ? strtolower($this->namespaceName) : strtolower($this->model->getClassName());
        } else {
            return $this->creationMode === 'generate' ? $this->name : $this->model->getName();
        }
    }

    /**
     * Génère le fichier sécurite ou imprime un snippet à copier coller dans le fichier securite
     */
    private function addSecurity()
    {
        if ($this->app->has('updateSecurity') && !$this->app->get('updateSecurity')) {
            $this->msg('Génération de fichier ' . $this->highlight('securite.yml', 'neutral') . ' désactivée', 'important');
            return;
        }


        if (!file_exists($this->securityPath)) {
            // TODO comportement configurable
            if ($this->app->get('updateSecurity') === 'generate')
                return;
        }

        $security = new E2DSecurityFileGenerator($this->app);
        if ($this->app->get('updateSecurity') === 'generate' && !$this->app->get('onlyPrint')) {
            $security->generate($this->securityPath);
        } elseif ($this->app->get('updateSecurity') === 'print') {
            $security->print($this->securityPath);
        }

        //$this->app->set('updateSecurity', false, $this->model->getName());
    }

    protected function initializePaths($otherModuleName = null)
    {
        $this->setProjectPath();
        $this->setModulePath();
        $this->setMenuPath();
        $this->setSecurityPath();
        $fileManager = $this->app->getFileManager();

        $this->templatePath = $fileManager->getTemplatePath();
        $this->subMenuPath = $this->templatePath->getChild($this->app->getFileManager()->getTemplate())->addFile('menu.yml');
    }

    /**
     * initialise le chemin du menu
     */
    public function setMenuPath(): void
    {
        $this->menuPath = $this->projectPath->addChild('config')->addFile('menu', 'yml');
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
        $this->projectPath = $this->app->getProjectPath();
    }

    public function getModulePath() : PathNode
    {
        return $this->modulePath;
    }



}