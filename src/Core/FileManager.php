<?php


namespace Core;

class FileManager
{
    private array $templates;
    private App $app;
    private Path $templatePath;
    private Path $projectPath;
    private string $templateNodeClass;

    /**
     * FileManager constructor.
     * @param $templates
     */
    public function __construct($app, $templates, $templateNodeClass = '')
    {
        $this->app = $app;
        $this->templates = explode("_", trim($templates.'_standard', "_"));
        $this->templateNodeClass = $templateNodeClass ?:  TemplateNode::class;
        $this->setTemplate($templates);
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
     * Remplace le chemin du template choisi par le chemin du template standard ou le template de fallback s'il n'y a pas de template personnalisé
     * @param FilePath $templatePath
     * @param string $fileSuffix
     * @param string $search
     * @return PathNode $templatePath
     */
    public function getTrueTemplatePath(FilePath $templatePath)
    {
        $templatePath = $this->findRightTemplatePath($templatePath);

        reset($this->templates);

        return $templatePath;
    }

    /**
     * Recherche récursif de template pour rechercher la template de fallback si le fichier n'existe pas sur ce template
     * @param string $templatePath
     * @param $search
     * @param $replace
     * @return ?FilePath
     */
    function findRightTemplatePath(FilePath $templatePath) : ?FilePath
    {
        if (!file_exists($templatePath)) {

            $nextTemplate = next($this->templates);
            if ($nextTemplate === false) {
                return null;
            }
            $templatePath->setFallbackTemplate($nextTemplate);

            return $this->findRightTemplatePath($templatePath);
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
        $this->templatePath->addChildTemplateNode(
            new $this->templateNodeClass($this->templates, $this->templates[0])
        );
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