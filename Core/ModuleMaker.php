<?php

namespace Core;

use \Spyc;

class ModuleMaker extends CommandLineMaker
{
    protected $name;
    protected $model;
    protected $namespaceName;
    protected $config;

    protected function __construct($name, $modelName)
    {
        if (!is_dir('modules')) {
            $this->msg('Répertoire \'modules\' inexistant, veuillez vérifier que vous travaillez dans le répertoire racine de votre projet', 'error');
            throw new Exception();
        }

        if (file_exists(dirname(__DIR__) . DS)) {
            $this->config = file_exists(dirname(__DIR__) . DS . 'config.yml') ? Spyc::YAMLLoad(dirname(__DIR__) . DS . 'config.yml') : [];
        }

        if (empty($name)) {
            $name = $this->askName();
        }

        $this->name = $name;
        $this->namespaceName = $this->conversionPascalCase($this->name);

        $this->generate($modelName);
    }

    public function generate($modelName)
    {
        $verbose = true;

        if ($this->addModule() === false) {
            $this->msg('Création de répertoire impossible. Processus interrompu', 'error');
            return false;
        }

        $this->initializeModule($modelName);
        $moduleStructure = Spyc::YAMLLoad(dirname(__DIR__) . DS. 'module.yml');
        $this->addSubDirectories('modules'.DS.$this->name, $moduleStructure, $verbose);

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

    function addSubDirectories($path, $structure, $verbose = false)
    {
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                if ($this->ensureDirExists($path.DS.$key, true, $verbose) === true) {
                    $this->addSubDirectories($path.DS.$key, $value, $verbose);
                }
            } else {
                // crée fichier
                $error = $this->ensureFileExists($path.DS.$value, $verbose);
                $filename = str_replace(['MODULE', 'MODEL', 'TABLE'], [$this->namespaceName, $this->model->getClassName(), $this->model->getName()], $value);
                if ($error === true) {
                   $this->msg('Le '. $this->highlight('fichier ', 'error') . $path.DS. $filename . ' existe déja', 'warning');
                } elseif ($error  !== '') {
                    $this->msg($error, 'error');
                } else {
                    if ($verbose) {
                        $this->msg('Création du fichier: '.$path.DS. $filename, 'success');
                    }
                }
            }
        }
    }

    private function addModule() : bool
    {
        return $this->ensureDirExists('modules/'.$this->name);
    }

    /**
     * Crée répertoire s'il n'existe pas
     *
     * @param string $dirname
     * @param bool $recursive
     * @param bool $verbose
     * @return bool
     */
    private function ensureDirExists(string $dirname, bool $recursive = false, $verbose = false) : bool
    {
        if(!is_dir($dirname)) {
            return mkdir($dirname, 0777, $recursive) && is_dir($dirname) && $this->msg('Création du répertoire: '.$dirname, 'success');;
        }

        if ($verbose) {
            return $this->msg('Le ' . $this->highlight('répertoire: ', 'info').''.$dirname. ' existe déja.', 'warning');
        }
        return true;
    }

    /**
     * Crée fichier s'il n'existe pas
     *
     * @param string $path
     * @param $verbose
     * @return bool|string
     */
    function ensureFileExists(string $path, $verbose)
    {
        $commonPath = str_replace('modules'.DS.$this->name, '', $path);
        $templatePath = dirname(__DIR__) . DS. 'templates' .DS.$this->template.DS.'module'.$commonPath;

        if (strpos($path, '.yml') === false) {
            $path = str_replace(['MODULE', 'MODEL', 'TABLE'], [$this->namespaceName, $this->model->getClassName(), $this->model->getName()], $path);
        }

        if (file_exists($path)) {
            return true;
        } else {
            $text = $this->generateFileContent($templatePath);
            //$this->msg("Template path: ".$templatePath, self::Color['White']);

            return $this->createFile($path, $text, true, $verbose);
        }
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
        $templates = array_map(function($tmpl) {$parts = explode(DS, $tmpl); return array_pop($parts); }, glob(dirname(__DIR__) . DS .DS.'*', GLOB_ONLYDIR));
        if (count($templates) === 1) {
            return $templates[0];
        } elseif (count($templates) > 1) {
            if (count($this->config) > 0 && isset($this->config['defaultTemplate']) && array_contains($this->config['defaultTemplate'], $templates)) {
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
}
