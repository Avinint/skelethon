<?php
define('ARRAY_ALL', true);
define('ARRAY_ANY', false);

function array_contains($needle, array $haystack, bool $all = false)
{
    if (is_array($needle)) {
        if ($all) {
            return empty(array_diff($needle, $haystack));
        }
        return !empty(array_intersect($needle, $haystack));
    }

    return isset(array_flip($haystack)[$needle]);
}

class BaseFactory
{
    const Color = ['Red' => "\e[1;31m", 'Yellow' => "\e[1;33m", 'Green' => "\e[1;32m", 'White' => "\e[1;37m", 'Blue' => "\e[1;36m"];

    private static $_instance;

    /**
     * @param string $name
     * @param $arg2
     * @return static
     */
    public static function getInstance(string $name, $arg2)
    {
        if (is_null(self:: $_instance)) {
            self::$_instance = new Static($name, $arg2);
        }

        return self::$_instance;
    }

    public function prompt($msg, $validValues = [], $keepCase = false)
    {
        $result = '';
        if (empty($validValues)) {
            while($result === '' || $result === null) {
                $result = readline($msg);
            }
        } else {
            while (!array_contains($result, $validValues)) {
                $result = $keepCase ? readline($msg) : strtolower(readline($msg));
            }
        }

        return $result;
    }

    public function msg(string $text, $type = '', $hasDisplayYesNo = false)
    {
        $displayYesNo = $hasDisplayYesNo ? ' ['.$this->highlight('O', 'success').'/'.$this->highlight('N', 'error').']' : '';

        echo ($type? $this->frame(strtoupper($type), $type) : '') . ' ' . $text . $displayYesNo . PHP_EOL. ($type ? '' : '==> ');

        return (bool)$type;
    }

    public function getColorFromType($type)
    {
        switch ($type) {
            case 'error':
                $color = self::Color['Red'];
                break;
            case 'warning':
                $color = self::Color['Yellow'];
                break;
            case 'success':
                $color =  self::Color['Green'];
                break;
            case 'info':
                $color =  self::Color['Blue'];
                break;
            default:
                $color = self::Color['White'];
                break;
        }

        return $color;
    }

    public function frame($text, $type)
    {
        $color = $this->getColorFromType($type);

        return self::Color['White'] . '[' . $color . $text . self::Color['White'] . ']' ;
    }

    public function highlight($text, $type = 'warning')
    {
        $color = $this->getColorFromType($type);

        return $color.$text.self::Color['White'];
    }

    // MODEL
    protected function conversionPascalCase($name = '')
    {
        $name = str_replace('-', '_', $name);
        $name = explode('_', $name);
        $name = array_map('ucfirst', $name);
        $name = implode('', $name);

        return $name;
    }

    protected function labelize($name = '')
    {
        $name = strtolower(str_replace('-', '_', $name));
        $name = ucfirst(str_replace('_', ' ', $name));

        return $name;
    }

    protected function camelize($name = '')
    {
        return strtolower(str_replace(['-', ' '], '_', $name));
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