<?php

namespace Core;

use \Spyc;

abstract class ModuleMaker extends BaseMaker
{
    protected $name;
    protected $model;
    protected $namespaceName;
    protected Config $config;
    protected $structure;
    protected $specificField;
    protected $modulePath;
    protected FileManager $fileManager;
    protected $app;

    public function __construct(string $name, App $app, $creationMode = 'generate')
    {
        $this->app = $app;
        $this->fileManager = $app->getFileManager();
        $app->setModuleMaker($this);
//        $this->setConfig($app->getConfig());

        static::$verbose = $this->app->get('verbose') ?? true;

        $this->setName($name ?: $this->askName());

        // Pour les modes qui génèrent un champs précis PAS UTILISE
        if (isset($params['specific_fields'])) {
            $this->specificField = $params['specific_fields'];
        }

        $this->creationMode = $creationMode;
        $this->model = $app->getModelMaker();
        $this->initializeModule();
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
        $this->namespaceName = $this->pascalize($name);
    }

    /**
     * Génére les fichiers du module
     * @return bool
     */
    public function generate()
    {
        if ('addModel' === $this->creationMode) {
            if (!is_dir('modules/'.$this->name)) {
                $this->msg('Création du '.$this->highlight('modèle', 'error').' impossible, il faut d\'abord créer le '.$this->highlight('module'), 'error', false, true, true);
                return false;
            }
        } elseif ($this->addModule() === false) {
            $this->msg('Création de répertoire impossible. Processus interrompu', 'error', false, true, true);
            return false;
        }

        $this->structure = $this->getModuleStructure();

        //$template = $this->app->getFileManager()->getTemplate();

        if (!array_contains($this->creationMode, ['generate', 'addModel'])) {
            return $this->executeSpecificModes();
        }
        $success = $this->addSubDirectories($this->structure);

        $this->displayCompletionMessage($success);

        return $success;
    }

    /**
     * Personnalisation du module (recommandé de surcharger cette méthode en fonction du projet)
     * @param $modelName
     * @throws \Exception
     */
    protected function initializeModule(): void
    {
        $this->model->setDatabaseConnection(new Database());
    }

    protected function getModuleStructure()
    {
        return Spyc::YAMLLoad($this->app->getFileManager()->getTemplatePath() . DS . 'standard' . DS. 'module.yml');
        return Spyc::YAMLLoad($this->app->getFileManager()->getTemplatePath()->addChild($this->app->getTemplate())->addFile('module.yml'));
    }

    function askName() : string
    {
        return $this->prompt('Veuillez renseigner le nom du module :');
    }

    function addSubDirectories($structure, $pathToFile = '')
    {
        $result = true;
        foreach ($structure as $key => $value) {

            if (is_array($value)) {
                if ($result = ($result && $this->ensureDirExists($pathToFile.DS.$key, true))) {
                    $this->addSubDirectories($value, $pathToFile.DS.$key);
                }
            } else {
                // crée fichier

                $projectFilePath = $this->modulePath->addChild(trim($pathToFile, DS), basename($pathToFile))->addFile($value);
                $fileTemplatePath = $this->templatePath->getChild($this->app->getTemplate())->addChild(trim($pathToFile, DS), basename($pathToFile))->addFile($value);

                [$message, $type] = $this->ensureFileExists(
                    $projectFilePath,
                    $fileTemplatePath);
                $this->msg($message, $type);
                $result = $result && $type !== 'error';
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    private function addModule() : bool
    {
        return $this->ensureDirExists($this->modulePath, false, false);
    }

    /**
     * Crée répertoire s'il n'existe pas
     *
     * @param PathNode $dir
     * @param bool $recursive
     * @return bool
     */
    protected function ensureDirExists(string $dir, bool $recursive = false, $prepend = true) : bool
    {
        $dir = ($prepend ? $this->modulePath : DS) . trim($dir, DS);

        if(!is_dir($dir)) {
            return mkdir($dir, 0777, $recursive) && is_dir($dir) && $this->msg($this->highlight('Création').' du répertoire: '.$dir, 'success');;
        }

        $this->msg('Le répertoire: '.''.$this->highlight($dir). ' existe déja.', 'warning', false);

        return true;
    }

    /**
     * Crée fichier s'il n'existe pas
     *
     * @param FilePath $path
     * @return bool|string
     */
    function ensureFileExists(FilePath $path, FilePath $templatePath)
    {
        $path = $this->getTrueFilePath($path);

       if (! $this->model->hasAction('consultation') &&  strpos($templatePath, 'consultation_TABLE') !== false) {
           return ['Pas de vue créé pour la consultation', 'important'];
       }

        if ($this->fileShouldNotBeCreated($path)) {
            return ['Le fichier ' . $this->highlight($path, 'info') . ' existe déja', 'warning'];
        }

        $text = $this->generateFileContent($templatePath, $path);

//        var_dump($text);
//        var_dump($path.'');
//        var_dump("=========================================");
//        die();

        return $this->saveFile($path, $text);
    }

    /**
     * Gère la sauvegarde de tout fichier dans l'application
     * @param $path
     * @param false $text
     * @return array|string[]
     */
    protected function saveFile($path, $text = false)
    {
        $modifiable = $this->fileIsUpdateable($path) && file_exists($path);
        $modified = $modifiable && file_get_contents($path) !== $text;

        if ($text === false) {
            $message = 'Fichier invalide';
        } else {
            $message = $modified || !$modifiable ? $this->app->getFileManager()->createFile($path, $text, true) : '';
            if (empty($message)) {
                if ($modified) {
                    $message = [$this->highlight('Mise à jour', 'info') . ' du fichier: ' . $this->highlight($path), 'success'];
                } elseif ($modifiable) {
                    $message = ['Le ' . 'fichier ' . $this->highlight($path, 'info') . ' n\'est pas mis à jour', 'warning'];
                } else {
                    $message = [$this->highlight('Création', 'info') . ' du fichier: ' . $this->highlight($path), 'success'];
                }

                return $message;
            }
        }

        return [$message, 'error'];
    }

    /**
     * Les fichiers ne peuvent pas être recréés s'ils existent mais peuvent être mis à jour
     * @param $path
     * @return bool
     */
    protected function fileShouldNotBeCreated($path)
    {
        return file_exists($path) && !$this->fileIsUpdateable($path);
    }

    /**
     * Identifie quels fichiers sont partagés entre plusieurs models et seront mis a jour quand on rajoute un modèle
     *
     * @param $path
     * @return false|int
     */
    protected function fileIsUpdateable($path)
    {
        return 'generate' !== $this->creationMode;
    }

    private function getModelName()
    {
        $model = readline($this->msg('Veuillez renseigner le nom du modèle :'.
            PHP_EOL.'Si vous envoyez un nom de modèle vide, le nom du modèle sera le nom du module ['.$this->name.']'));
        if (!$model) {
            $model = $this->name;
        }

        return $model;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNamespaceName()
    {
        return $this->namespaceName;
    }

    /**
     * Modifie le chemin du template
     * pour récupérer le chemin
     * d'un fichier existant dans le projet,
     * afin de le modifier
     *
     * @param FilePath $file
     * @return FilePath
     */
    abstract protected function getTrueFilePath(FilePath $file) :FilePath;

    /**
     * @param bool $success
     */
    private function displayCompletionMessage(bool $success): void
    {
        $keyword = 'generate' === $this->creationMode ? 'module' : 'modèle';
        if ($success) {
            $this->msg(ucfirst($keyword) . ' généré avec succès', 'success', false, true, true);
        } else {
            $this->msg('La génération du ' . $keyword . ' n\'a pus aller à son terme', 'error', false, true, true);
        }
    }


    /**
     * @return bool|null
     *
     * Permet de demander si on veut appliquer les réponses au choix à tous les modules
     *
     * TODO (utiliser)
     */
    protected function askApplyChoiceForAllModules()
    {
        $reply = $this->prompt('Voulez-vous sauvegarder les choix sélectionnés pour les appliquer lors de la création de nouveaux modules? '
                .PHP_EOL.'['.$this->highlight('o', 'success').'/'.$this->highlight('n', 'error').'] ou '.$this->highlight('réponse vide').
                ' pour choisir au fur et à mesure', ['o', 'n']) === 'o';

        $this->app->set('memorizeChoices',  $reply);

        return$reply;
    }

    abstract protected function initializePaths();
}
