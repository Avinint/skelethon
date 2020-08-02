<?php


namespace Core;


class CommandLineToolShelf
{
    const Color = ['Red' => "\e[1;31m", 'Yellow' => "\e[1;33m", 'Green' => "\e[1;32m", 'White' => "\e[1;37m", 'Blue' => "\e[1;36m"];

    protected static $verbose;

    public static function msg(string $text, $type = '', $hasDisplayYesNo = false, $verboseCondition = true, $important = false)
    {;
        if ((self::$verbose && $verboseCondition) || empty($type) || $important) {
            $displayYesNo = $hasDisplayYesNo ? ' ['.static::highlight('O', 'success').'/'.static::highlight('N', 'error').']' : '';
            echo ($type? static::frame(strtoupper($type), $type).' ' : '')  . $text . $displayYesNo . PHP_EOL. ($type ? '' : '==> ');
        }

        return !empty($type);
    }

    public function displayList($list, $hl = '', $delim1 = '', $delim2 = PHP_EOL)
    {
        return implode($delim1, array_map(function($el) use ($hl, $delim2) { if ($hl) {$el = $this->highlight($el, $hl);} return "\t$el". $delim2;}, $list));
    }

    private static function getColorFromType($type)
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
     * @param bool $defaultValue
     * @param string $key
     * @param array $choices
     * @return bool|string
     */
    protected function askMultipleChoices(string $key, array $choices, $defaultValue = false, $reference  = '')
    {
        $msgDefault = $defaultValue !== false ? PHP_EOL . 'En cas de chaine vide, Le ' . $key . ' ' . $this->frame($defaultValue, 'success') . ' sera sélectionné par défaut.' : '';
        $texteReference  = empty($reference) ? '' : ' ('.$reference.') ';
        $selection = $this->prompt('Choisir un/e ' . $key . ' dans la liste suivante'. $texteReference.' :' . PHP_EOL . $this->displayList($choices, 'info') . $msgDefault, array_merge($choices, ['']));
        if ($defaultValue !== false && $selection === '') {
            $selection = $defaultValue ;
        }
        return $selection;
    }

    public static function frame($text, $type)
    {
        $color = static::getColorFromType($type);

        return self::Color['White'] . '[' . $color . $text . self::Color['White'] . ']' ;
    }

    public static function highlight($text, $type = 'warning')
    {
        $color = static::getColorFromType($type);

        return $color.$text.self::Color['White'];
    }

    // MODEL
    protected function pascalize($name = '')
    {
        $name = str_replace('-', '_', $name);
        $name = explode('_', $name);
        $name = array_map('ucfirst', $name);
        $name = implode('', $name);

        return $name;
    }

    protected function labelize($name = '')
    {
        return ucfirst(strtolower(str_replace(['-', '_'], ' ', $name)));
    }

    protected function snakize($name = '')
    {
        return strtolower(str_replace(['-', ' '], '_', $name));
    }

    protected function urlize($name = '', $noHyphen = false)
    {
        $replace = $noHyphen ? '' : '-';
        return strtolower(str_replace(['_', ' '], $replace, $name));
    }
}