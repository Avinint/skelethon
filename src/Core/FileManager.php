<?php


namespace Core;

class FileManager
{
    private string $template;

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

    public function __construct($template)
    {
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->template;
    }


}