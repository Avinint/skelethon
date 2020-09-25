<?php


namespace Core;

class FileManager
{
    private array $templates;

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
                return $this->getTemplatePath($templatePath, 'standard', $search);
            else
                return $this->getTemplatePath($templatePath, $replace, $search);
        }

        return $templatePath;
    }

    /**
     * FileManager constructor.
     * @param $templates
     */
    public function __construct($templates)
    {
        $template = explode("_", trim($templates, "_"));

        $this->templates = $template;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {

        return $this->templates[0];
    }

}