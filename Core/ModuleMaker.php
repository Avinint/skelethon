<?php

namespace Core;

use \Spyc;

class ModuleMaker extends BaseMaker
{
    protected $name;
    protected $model;
    protected $namespaceName;
    protected $config;

    protected function __construct($name, $modelName, $creationMode = 'generate')
    {
        if (!is_dir('modules')) {
            $this->msg('Répertoire \'modules\' inexistant, veuillez vérifier que vous travaillez dans le répertoire racine de votre projet', 'error', false, true, true);
            throw new Exception();
        }

        $this->config = Config::get();

        if (empty($name)) {
            $name = $this->askName();
        }

        $this->name = $name;
        $this->namespaceName = $this->conversionPascalCase($this->name);
        $this->creationMode = $creationMode;
        $this->generate($modelName);
    }

    public function generate($modelName)
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

        $this->initializeModule($modelName);

        $moduleStructure = Spyc::YAMLLoad(dirname(__DIR__) . DS. 'module.yml');
        $success = $this->addSubDirectories('modules'.DS.$this->name, $moduleStructure);

        $configFields = array_map(function($field) {return $field['column'];}, array_values($this->model->getViewFields(true)));
        Config::write($this->name, [
                'template' => $this->template,
                'models' => [ $this->model->getName() => ['fields' => $configFields]]
            ]);

        $this->displayCompletionMessage($success);

        return $success;
    }


    /**
     * Personnalisation du module (recommandé de surcharger cette méthode en fonction du projet)
     * @param $modelName
     * @throws \Exception
     */
    protected function initializeModule($modelName): void
    {
        $this->model = ModelMaker::create($modelName, $this);
        $this->template = $this->askTemplate();
    }

    function askName() : string
    {
        return $this->prompt('Veuillez renseigner le nom du module :');
    }

    function addSubDirectories($path, $structure)
    {
        $result = true;
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                if ($result = ($result && $this->ensureDirExists($path.DS.$key, true))) {
                    $this->addSubDirectories($path.DS.$key, $value);
                }
            } else {
                // crée fichier
                $message = $this->ensureFileExists($path.DS.$value);
                $this->msg($message[0], $message[1]);
                $result = $result && $message[1] !== 'error';
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    private function addModule() : bool
    {
        return $this->ensureDirExists('modules/'.$this->name);
    }

    /**
     * Crée répertoire s'il n'existe pas
     *
     * @param string $dirname
     * @param bool $recursive
     * @return bool
     */
    private function ensureDirExists(string $dirname, bool $recursive = false) : bool
    {
        if(!is_dir($dirname)) {
            return mkdir($dirname, 0777, $recursive) && is_dir($dirname) && $this->msg($this->highlight('Création').' du répertoire: '.$dirname, 'success');;
        }

        $this->msg('Le ' . 'répertoire: '.''.$this->highlight($dirname). ' existe déja.', 'warning', false, $this->creationMode === 'generate');

        return true;
    }

    /**
     * Crée fichier s'il n'existe pas
     *
     * @param string $path
     * @return bool|string
     */
    function ensureFileExists(string $path)
    {
        $commonPath = str_replace('modules'.DS.$this->name, '', $path);
        $templatePath = dirname(__DIR__) . DS. 'templates' .DS.$this->template.DS.'module'.$commonPath;

        $path = $this->getTrueFilePath($path);

        if ($this->fileShouldNotbeCreated($path)) {
            return ['Le fichier ' . $this->highlight($path, 'info') . ' existe déja', 'warning'];
        }

        $text = $this->generateFileContent($templatePath, $path);

        $modifiable = $this->fileIsUpdateable($path) && file_exists($path);
        $modified = $modifiable && file_get_contents($path) !== $text;

        $message = $modified || !$modifiable ? $this->createFile($path, $text, true) : '';
        if (empty($message)) {
            if ($modified) {
                return [$this->highlight('Mise à jour', 'info') . ' du fichier: ' . $this->highlight($path), 'success'];
            } elseif ($modifiable) {
                return ['Le '. 'fichier ' . $this->highlight($path, 'info') . ' n\'est pas mis à jour', 'warning'];
            } else {
                return [$this->highlight('Création', 'info').' du fichier: '.$this->highlight($path), 'success'];
            }
        } else {
            return [$message, 'error'];
        }
    }

    protected function fileShouldNotbeCreated($path)
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
        // TODO regler CAMEL CASE conversions
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

    public function getNamespace()
    {
        return $this->namespaceName;
    }

    protected function askTemplate()
    {
        $templates = array_map(function($tmpl) {$parts = explode(DS, $tmpl); return array_pop($parts); }, glob(dirname(__DIR__) . DS . 'templates'.DS.'*', GLOB_ONLYDIR));
        if (count($templates) === 1) {
            return $templates[0];
        } elseif (count($templates) > 1) {
            $moduleConfig = Config::get($this->name);

            if (count($moduleConfig) > 0 && isset($moduleConfig['template']) && array_contains($moduleConfig['template'], $templates)) {
                $template = Config::get($this->name)['template'];
            } elseif (count($this->config) > 0 && isset($this->config['defaultTemplate']) && array_contains($this->config['defaultTemplate'], $templates)) {
                $template = $this->config['defaultTemplate'];
            } else {
                $template = $this->prompt('Choisir un template dans la liste suivante:'.PHP_EOL.$this->displayList($templates, 'info') .
                    PHP_EOL.'En cas de chaine vide, Le template '. $this->frame('standard', 'success').' sera sélectionné par défaut.', array_merge($templates, ['']));
                if ($template === '') {
                    $template = 'standard';
                }
            }

            return $template;
        } else {
            throw new \Exception("Pas de templates disponibles");
        }
    }

    /**
     * @param $enumPath
     * @param $enum
     * @param array $allEnumEditLines
     * @param array $allEnumSearchLines
     * @param array $enumDefaults
     * @return array
     */
    protected function handleControllerEnumField($enumPath, $enum, array &$allEnumEditLines, array &$allEnumSearchLines, array &$enumDefaults)
    {
        $enumLines = $enumSearchLines = file($enumPath);
        $enumEditionLine = $enumLines[0];

        if ($enum['default']) {
            $enumSearchLines = $enumLines;
            $enumDefault = $enumLines[2];
        } else {
            $enumSearchLines = [$enumLines[0]];
        }

        if ($this->model->usesSelect2) {
            if ($enum['default']) {
                $enumSearchLines = array_slice($enumLines, 0, 3);
                $enumDefault = $enumLines[3];
            } else {
                $enumSearchLines = array_slice($enumLines, 0, 1);
            }
            if ($enum['default'] === null) {
                $enum['default'] = '';
            }
        }


        $searches = ['NAME', 'mODULE', 'TABLE', 'COLUMN', 'DEFAULT'];
        $replacements = [$enum['name'], $this->name, $this->model->getName(), $enum['column'], $enum['default']];

        $allEnumEditLines[] = str_replace($searches, $replacements, $enumEditionLine);
        $allEnumSearchLines[] = str_replace($searches, $replacements, implode('', $enumSearchLines));
        $enumDefaults[] = str_replace($searches, $replacements, $enumDefault);
        //return $enumSearchLines;
    }

    /**
     * @param $field
     * @param array $exceptions
     * @param array $defaults
     * @return array
     */
    protected function handleControllerBooleanField($field, array &$exceptions, array &$defaults)
    {
        $exceptions['aBooleens'][] = $field['field'];
        $defaultValue = isset($field['default']) ? $field['default'] : 'nc';
        $defaults[] = str_repeat("\x20", 8) . "\$aRetour['aRadios']['{$field['name']}'] = '$defaultValue';";
    }

    /**
     * Modifie le nom du template pour generer le fichier,
     * à surcharger en fonction du projet
     *
     * @param string $path
     * @return string
     */
    protected function getTrueFilePath(string $path) : string
    {
        return $path;
    }

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
}
