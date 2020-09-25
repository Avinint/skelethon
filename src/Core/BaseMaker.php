<?php

namespace Core;

abstract class BaseMaker extends CommandLineToolShelf
{

    protected Config $config;
    protected FileManager $fileManager;

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
        $this->fileManager = $fileManager ??  $this->config->getFileManager($this->config->get('template') ?? 'standard');
    }

    public function __construct( FileManager $fileManager = null)
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

    public function getConfig()
    {
        return $this->config;
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

                $this->config->saveChoice($key, $selection);
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
    public function getTrueTemplatePath($templatePath, $replace = '', $search = '.')
    {
        if (!isset($this->fileManager)) {
            throw new \Exception("File manager non initialisé");
        }
        return $this->fileManager->getTrueTemplatePath($templatePath, $replace, $search);

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

        $this->config->set('memorizeChoices',  $reply);

        return$reply;
    }
}