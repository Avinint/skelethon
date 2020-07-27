<?php

namespace Core;

use http\Exception\InvalidArgumentException;

class BaseMaker
{
    use CommandLineToolShelf;

    const Color = ['Red' => "\e[1;31m", 'Yellow' => "\e[1;33m", 'Green' => "\e[1;32m", 'White' => "\e[1;37m", 'Blue' => "\e[1;36m"];

    /** @var Config $this->config */
    protected $config;
    protected $fileManager;

    /**
     * @return mixed
     */
    public function getFileManager()
    {
        return $this->fileManager;
    }

    /**
     * @param mixed $fileManager
     */
    public function setFileManager(?FileManager $fileManager): void
    {
        if ($fileManager === null) {
            $this->fileManager = new FileManager();
        } else {
            $this->fileManager = $fileManager;
        }
    }

    public function __construct(FileManager $fileManager = null)
    {
        $this->setFileManager($fileManager);
    }



    /**
     * @param array $params
     */
    protected function setConfig(array $params): void
    {
        if (!isset($params['config'])) {
            throw new \InvalidArgumentException("Fichiers config manquants");
        }
        $this->config = $params['config'];
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

    /**
     * L'application donne des choix aux utilisateurs, les réponses sont stockées en config, permet a l'application de ne pas redemander une information déja stockée
     *
     * @param string $key
     * @param array $choices
     * @param $function
     * @param bool $defaultValue
     * @param bool $multiple
     * @return mixed
     * @throws \Exception
     */
    protected function askConfig(string $key, array $choices, $function, $defaultValue = false, $multiple = false)
    {
        if (count($choices) === 1) {
            return $choices[0];
        } elseif (count($choices) > 1) {
            if ($this->config->get($key) !== null && array_contains($this->config->get($key), $choices)) {
                $selection = $this->config->get($key);
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
     * Remplace le chemin du template choisi par le chemin du template standard ou le template de fallback  s'il n'y a pas de template personnalisé
     *
     * @param $templatePath
     * @return string|string[]
     */
    protected function getTrueTemplatePath($templatePath, $replace = '', $search = '.')
    {
        if (!empty($replace)) {
            $templatePath = str_replace_last($search, $replace, $templatePath);
        }

        if (!file_exists($templatePath) && isset($this->fallBackTemplate)) {
            // get fallback template ($this->>template)  returns gettrutemplate (next template)
            $templatePath = str_replace($this->template, $this->fallBackTemplate, $templatePath);
        }

        if (!file_exists($templatePath)) {
            $templatePath = str_replace($this->template, 'standard', $templatePath);
        }

//        if (!file_exists($templatePath)) {
//            throw new \Exception("Fichier manquant : $templatePath");
//        }

        return $templatePath;
    }

    /**
     * @param string $key
     * @param $selection
     * @param string $model
     */
    protected function saveChoiceInConfig(string $key, $selection, $model = ''): void
    {
        if (empty($model)) {
            $applyChoiceToAllModules = $this->applyChoicesForAllModules ?? $this->prompt('Voulez vous appliquer ce choix à tous les modules créés à l\'avenir?', ['o', 'n']) === 'o';
            if ($applyChoiceToAllModules) {
                $this->config->set($key, $selection, '', true);
            }
            $this->config->set($key, $selection);
        } else {
            $this->config->set($key, $selection, $model);
        }
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