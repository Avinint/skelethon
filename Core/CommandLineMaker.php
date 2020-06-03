<?php

namespace Core;

class CommandLineMaker
{
    use CommandLineToolShelf;

    const Color = ['Red' => "\e[1;31m", 'Yellow' => "\e[1;33m", 'Green' => "\e[1;32m", 'White' => "\e[1;37m", 'Blue' => "\e[1;36m"];

    protected static $_instance;

    /**
     * @param string $name
     * @param $arg2
     * @return static
     */
    public static function create(string $name, $arg2)
    {
        if (is_null(static :: $_instance)) {
            static::$_instance = new static($name, $arg2);
        }

        return self::$_instance;
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
     * Remplace le chemin du template choisi par le chemin du template standard s'il n'y a pas de template personnalisé
     *
     * @param $templatePath
     * @return string|string[]
     */
    protected function getTrueTemplatePath($templatePath, $suffix = '', $marker = '.')
    {
        $search = [];
        $replace = [];

        if (!empty($suffix)) {
            $templatePath = str_replace($marker, $suffix.$marker, $templatePath);
        }

        if (!file_exists($templatePath)) {
            $templatePath = str_replace($this->template, 'standard', $templatePath);
        }


        return $templatePath;
    }

    protected function createFile($path, $text = '', $write = false)
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
}