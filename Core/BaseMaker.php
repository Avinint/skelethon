<?php

namespace Core;

class BaseMaker
{
    use CommandLineToolShelf, FileManager;

    const Color = ['Red' => "\e[1;31m", 'Yellow' => "\e[1;33m", 'Green' => "\e[1;32m", 'White' => "\e[1;37m", 'Blue' => "\e[1;36m"];

    protected static $_instance;

    /**
     * @param string $name
     * @param $arg2
     * @return static
     */
    public static function create(string $module, $model, $creationMode = 'generate', $specificField = '')
    {
        if (is_null(static :: $_instance)) {
            static::$verbose = Config::create() ['verbose'] ?? true ;
            static::$_instance = new static($module, $model, $creationMode, $specificField);
        }

        return self::$_instance;
    }

    protected function __construct(string $module, string $model, string $creationMode = 'generate', $specificField = '')
    {

        if (!is_dir('modules')) {
            $this->msg('Répertoire \'modules\' inexistant, veuillez vérifier que vous travaillez dans le répertoire racine de votre projet', 'error', false, true, true);
            throw new Exception();
        }

        $this->config = Config::create();
        $this->moduleConfig = Config::create($module);
    }

    public function prompt($msg, $validValues = [], $keepCase = false)
    {
        echo PHP_EOL;
        $result = '';
        if (empty($validValues)) {
            while($result === '' || $result === null) {
                $result = readline($this->msg($msg));
            }
        } else {
            $result = false;
            while (!array_contains($result, $validValues)) {
                $tempResult = readline($this->msg($msg, '', $validValues === ['o', 'n']));
                $result = $keepCase ? $result : strtolower($tempResult);
            }
        }

        return $result;
    }

    protected function askConfig(string $key, array $choices, $function, $defaultValue = false, $multiple = false)
    {
        if (count($choices) === 1) {
            return $choices[0];
        } elseif (count($choices) > 1) {
            if (count($this->moduleConfig ) > 0 && isset($this->moduleConfig[$key]) && array_contains($this->moduleConfig[$key], $choices)) {
                $selection = $this->moduleConfig[$key];
            } elseif (count($this->config) > 0 && isset($this->config[$key]) && array_contains($this->config[$key], $choices)) {
                $selection = $this->config[$key];
            } else {
                $selection = $this->$function($key, $choices, $defaultValue);

                $this->saveChoiceInConfig($key, $selection);
            }

            return $selection;
        } else {
            throw new \Exception("Pas de $key disponible");
        }
    }



    /**
     * Remplace le chemin du template choisi par le chemin du template standard s'il n'y a pas de template personnalisé
     *
     * @param $templatePath
     * @return string|string[]
     */
    protected function getTrueTemplatePath($templatePath, $replace = '', $search = '.')
    {
        if (!empty($replace)) {
            $templatePath = str_replace($search, $replace, $templatePath);
        }

        if (!file_exists($templatePath)) {
            $templatePath = str_replace($this->template, 'standard', $templatePath);
        }


        return $templatePath;
    }

    /**
     * @param string $key
     * @param $selection
     * @param $moduleConfig
     */
    protected function saveChoiceInConfig(string $key, $selection): void
    {
        $applyChoiceToAllModules = $this->applyChoicesForAllModules ?? $this->prompt('Voulez vous appliquer ce choix à tous les modules créés à l\'avenir?', ['o', 'n']) === 'o';
        if ($applyChoiceToAllModules) {
            $this->config->set($key, $selection);
        }
        $this->moduleConfig->set($key, $selection);
    }

    /**
     * @param bool $defaultValue
     * @param string $key
     * @param array $choices
     * @return bool|string
     */
    protected function askMultipleChoices(string $key, array $choices, $defaultValue = false)
    {
        $msgDefault = $defaultValue !== false ? PHP_EOL . 'En cas de chaine vide, Le ' . $key . ' ' . $this->frame($defaultValue, 'success') . ' sera sélectionné par défaut.' : '';

        $selection = $this->prompt('Choisir un ' . $key . ' dans la liste suivante:' . PHP_EOL . $this->displayList($choices, 'info') . $msgDefault, array_merge($choices, ['']));
        if ($defaultValue !== false && $selection === '') {
            $selection = $defaultValue;
        }
        return $selection;
    }

}