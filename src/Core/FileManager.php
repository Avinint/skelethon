<?php

namespace Core;

use Spyc;

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
    public function __construct($app, $templateNodeClass = null)
    {
        $this->app = $app;
        $this->templateNodeClass = $templateNodeClass ??  TemplateNode::class;
    }

    /**
     * Transmet les templates à la configuration et crée un path pour chaque template
     * @param string $templates
     */
    public function setTemplate(string $templates)
    {
        if (!$this->app->has('template')) {
            $this->app->setTemplate($templates, 'model');
        } elseif (strpos($templates, 'legacy_') !== false && !$this->app->get('legacy')) {
            $templates = str_replace('legacy_', '', $templates);
            $this->app->setTemplate($templates, 'model');
        } elseif ($this->app->get('legacy') && strpos($templates, 'legacy_')  === false ) {
            $this->app->setTemplate('legacy_'.$templates, 'model');
        }

        $this->templates = explode("_", trim($templates, '_'));
        if ($this->templates !== ['standard']) {
            $this->templates[] = 'standard';
        }
    }

    public function createFile($path, $text = '', $write = false)
    {
        if ($this->app->get('printOnly')) {
            echo $text;
            return;
        }
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
        return clone $templatePath->getRightPath();
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
     * @return string
     */
    public function getTemplate(): string
    {
        return reset($this->templates);
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
            $this->createFile($this->app->getConfig()->getPath('for_project', Spyc::YAMLDump([], 4, 40, true), true));
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
    public function setTemplatePath(TemplatePath $path) : void
    {
        $this->templatePath = $path;
//        foreach ($this->templates as $template) {
//            $this->altPaths[$template] = $this->templatePath->addChild($template);
//        }

//        $this->templatePath->addChildTemplateNode(
//            new $this->templateNodeClass($this->templates, $this->templates[0])
//        );
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

    /**
     * @return PathNode
     *
     */
    public function getRessourcePath($ressourceDir = '') : PathNode
    {
        if ($ressourceDir) {
            return $this->projectPath->addChild('ressources')->addChild($ressourceDir);
        } else {
            return $this->projectPath->addChild('ressources');
        }
    }
}


