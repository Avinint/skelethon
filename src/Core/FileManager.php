<?php


namespace Core;

class FileManager
{
    private array $templates;
    private App $app;
    private Path $templatePath;
    private Path $projectPath;

    /**
     * FileManager constructor.
     * @param $templates
     */
    public function __construct($app, $templates)
    {
        $this->app = $app;
        $this->templates = explode("_", trim($templates, "_"));

        $this->setTemplate($templates);
//        $this->templates = $templates;
    }

    public function createFile($path, $text = '', $write = false)
    {
        $errorMessage = [];
        $mode = $write ? 'w' : 'a';
        $file = fopen($path, $mode);
        if (fwrite($file, $text) === false) {
            $errorMessage[] = 'Erreur lors de l\'éxriture du fichier '.$path;
        }
        if (fclose($file) === false) {
            $errorMessage[] = 'Erreur lors de la fermeture du fichier '.$path;
        }

        return implode(PHP_EOL, $errorMessage);
    }

    /**
     * Remplace le chemin du template choisi par le chemin du template standard ou le template de fallback  s'il n'y a pas de template personnalisé
     *
     * @param $templatePath
     * @return string|string[]
     */
    public function getTrueTemplatePath($templatePath, $replace = '', $search = '.')
    {
        $templatePath = $this->findRightTemplatePath($templatePath, $replace, $search);

        reset($this->templates);

        return $templatePath;
    }

    /**
     * Recherche récursif de template pour rechercher la template de fallback si le fichier n'existe pas sur ce template
     * @param string $templatePath
     * @param $search
     * @param $replace
     * @return string
     */
    function findRightTemplatePath(string $templatePath, $replace = '', $search = '') : string
    {
        if ($replace !== '')
            $templatePath = str_replace_last($search, $replace, $templatePath);


        if (!file_exists($templatePath) && $replace !== 'standard') {
            $search = current($this->templates);
            $replace = next($this->templates);
            if ($replace === false)
                return $this->findRightTemplatePath($templatePath, 'standard', $search);
            else
                return $this->findRightTemplatePath($templatePath, $replace, $search);
        }

        return $templatePath;
    }

    /**
     * Transmet les templates à la configuration et crée un path pour chaque template
     * @param string $templates
     */
    public function setTemplate(string $templates)
    {
        if (!$this->app->getConfig()->has('template'))
            $this->app->getConfig()->setTemplate($templates);


    }


    /**
     * @return string
     */
    public function getTemplate(): string
    {

        return $this->templates[0];
    }

    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * @param string|null $template
     */
    public function ensureConfigFileExists() : void
    {
        $this->ensureDirectoryExists(dirname($this->app->getConfig()->getPath('for_project')));

        if (!file_exists($this->app->getConfig()->getPath('for_project'))) {
            $this->fileManager->createFile($this->app->getConfig()->getPath('for_project', Spyc::YAMLDump([], 4, 40, true), true));
        }

    }

    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            if (!mkdir($path) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
        }
    }

    /**
     * @return Path
     */
    public function getTemplatePath() : Path
    {
        return $this->templatePath;
    }

    /**
     * Chemin des templates dans l'application
     * @param Path $path
     */
    public function setTemplatePath(Path $path) : void
    {
        $this->templatePath = $path;

        $prevTemplate = null;

        foreach ($this->templates as $template) {
            $this->templatePath->addChild($template.DS.'module', $template);
            $templatePathObject = $this->templatePath->getChild('templates')->getChild($template);

            if (isset($prevTemplate)) {
                $prevTemplate->setTwinPath(
                    $templatePathObject
                );
            }

            $prevTemplate = $templatePathObject;
        }
    }

    public function setProjectPath(Path $path) : void
    {
        $this->projectPath = $path;
    }

    /**
     * @return PathNode
     */
    public function getProjectPath() : PathNode
    {
        return $this->projectPath;
    }

}