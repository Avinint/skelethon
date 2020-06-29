<?php

namespace Core;

use \Spyc;

class ModuleMaker extends BaseMaker
{
    protected $name;
    protected $model;
    protected $namespaceName;
    protected $config;
    protected $specificField;

    protected function __construct($name, $modelName, $creationMode = 'generate', $specificField = '')
    {
        parent::__construct($name, $modelName, $creationMode, $specificField);

        if (empty($name)) {
            $name = $this->askName();
        }

        $this->specificField = $specificField;

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

        if (!array_contains($this->creationMode, ['generate', 'addModel'])) {
            return $this->executeSpecificModes();
        }
        $success = $this->addSubDirectories('modules'.DS.$this->name, $moduleStructure);

        $configFields = array_map(function($field) {return $field['column'];}, array_values($this->model->getViewFields(true)));
//        Config::write($this->name, [
//                'template' => $this->template,
//                'models' => [ $this->model->getName() => ['fields' => $configFields]]
//            ]);
        // TODO gestion plus fine des champs comme pouvoir selectionner les champs qu'on souhaite utiliser dans l'appli et sauvegarder ou limiter les champs qui apparraissent dans les vues

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
        $this->model = ModelMaker::create($modelName, $this, $this->creationMode, $this->specificField);
        $this->applyChoicesForAllModules = $this->askApplyChoiceForAllModules();
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
                [$message, $type] = $this->ensureFileExists($path.DS.$value);
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

        return $this->saveFile($path, $text);
    }

    protected function saveFile($path, $text = false)
    {
        $modifiable = $this->fileIsUpdateable($path) && file_exists($path);
        $modified = $modifiable && file_get_contents($path) !== $text;

        if ($text === false) {
            $message = 'Fichier invalide';
        } else {
            $message = $modified || !$modifiable ? $this->createFile($path, $text, true) : '';
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

        return $this->askConfig('template', $templates, 'askMultipleChoices', 'standard');
    }

    protected function askApplyChoiceForAllModules()
    {
        $askChoice =  $this->prompt('Voulez-vous sauvegarder les choix sélectionnés pour les appliquer lors de la création de nouveaux modules? '
            .PHP_EOL.'['.$this->highlight('o', 'success').'/'.$this->highlight('n', 'error').'] ou '.$this->highlight('réponse vide').' pour choisir au fur et à mesure', ['o', 'n', '']);

        $this->config->set('memorizeChoices', !empty($askChoice) && $askChoice === 'o');
        return empty($askChoice)  ?  null :  $askChoice === 'o';
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
