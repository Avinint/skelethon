<?php

namespace Core;

abstract class BaseMaker extends CommandLineToolShelf
{
    use TraitLoggable;
    protected Config $config;
    protected FileManager $fileManager;

    /**
     * @return mixed
     */
//    public function getFileManager()
//    {
//        return $this->fileManager;
//    }

    /**
     * @param mixed $fileManager
     */
//    public function setFileManager(?FileManager $fileManager): void
//    {
//        $this->fileManager = $fileManager ??  $this->config->getFileManager($this->config->get('template') ?? 'standard');
//    }


    /**
     * @param Config $config
     */
    protected function setConfig(Config $config): void
    {
        $this->config = $config;
    }

    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Remplace le chemin du template choisi par le chemin du template standard ou le template de fallback  s'il n'y a pas de template personnalisé
     *
     * @param $templatePath
     * @return string|string[]
     */
    public function getTrueTemplatePath($templatePath, $replace = '', $search = '.')
    {
        return $this->app->getFileManager()->getTrueTemplatePath($templatePath, $replace, $search);
    }

    public function __construct()
    {
        $this->logInTheShell("constructeur");
    }

}