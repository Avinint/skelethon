<?php


namespace Core;


class CommandLineToolShelf
{
    const Color = ['Red' => "\e[1;31m", 'Yellow' => "\e[1;33m", 'Green' => "\e[1;32m", 'White' => "\e[1;37m", 'Blue' => "\e[1;36m", 'Orange' => "\e[38;5;208m"];

    protected static $verbose;

    /**
     * @param array $validValues
     * @return string
     */
    private static function displayValidNumericAnswers(array $validValues): string
    {
        $display = '';
        if (array_contains_array($validValues, range(1, count($validValues)))) {
            $valueArray = [];
            foreach ($validValues as $key => $value) {
                $colors = ['success', 'error', 'info', 'important', 'warning'];
                $color = $colors[$key] ?? 'warning';
                //$color = ($value == 1 ? 'success' : ($value == 2 ? 'error' : ($value == 3 ? 'warning' : 'info')));
                $valueArray[] = static::highlight($value, $color);
            }
            $display = '[' . implode('/', $valueArray) . ']';
        }
        return $display;
    }

    public static function msg(string $text, $type = '', $validValues = [], $verboseCondition = true, $important = false)
    {
        if ((self::$verbose && $verboseCondition) || empty($type) || $important) {
            if (!empty($validValues)) {
                $display = $validValues === ['o', 'n'] ? ' ['.static::highlight('O', 'success').'/'.static::highlight('N', 'error').']' : '';
                if (empty($display)) {
                    $display = self::displayValidNumericAnswers($validValues);
                }
            }

            echo ($type? static::frame(strtoupper($type), $type).' ' : '')  . $text . $display  . ($type ? '' : ' :').PHP_EOL;
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
            case 'important':
                $color =  self::Color['Orange'];
                break;
            default:
                $color = self::Color['White'];
                break;
        }

        return $color;
    }

    public static function prompt($msg, $validValues = [], $keepCase = false)
    {
        echo PHP_EOL;
        $result = '';
        if (empty($validValues)) {
            while($result === '' || $result === null) {
                $result = readline(self::msg($msg));
            }
        } else {
            $result = false;
            while (!array_contains($result, $validValues)) {
                $tempResult = readline(self::msg($msg, '',  $validValues));
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
    protected function askMultipleChoices(string $key, array $choices, $defaultValue = false, $reference  = '', $freeChoice = false)
    {
        $msgDefault = $defaultValue !== false ? PHP_EOL . 'En cas de chaine vide, Le ' . $key . ' ' . $this->frame($defaultValue, 'success') . ' sera sélectionné par défaut.' : '';
        $texteReference  = empty($reference) ? '' : ' ('.$reference.') ';
        $allowedValues = $freeChoice ? [] : array_merge($choices, ['']);

        $selection = $this->prompt('Choisir un/e ' . $key . ' dans la liste suivante'. $texteReference.' :' . PHP_EOL . $this->displayList($choices, 'info') . $msgDefault, $allowedValues);
        if ($defaultValue !== false && $selection === '') {
            $selection = $defaultValue;
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
        if (strpos($name, '_')) {
            return strtolower(str_replace(['-', ' '], '_', $name));
        } else {
            return strtolower (preg_replace_callback ( '/([a-z])([A-Z])/', function ($match) {
                return $match[1] . "_" . $match[2] ;
            }, $name  ));
        }
    }

    protected function urlize($name = '', $noHyphen = false)
    {
        $replace = $noHyphen ? '' : '-';

        if (strpos($name, '_')) {
            return strtolower(str_replace(['_', ' '], $replace, $name));
        } else {
            return strtolower (preg_replace_callback ( '/([a-z])([A-Z])/', function ($match) {
                return $match[1] . "-" . $match[2] ;
            }, $name  ));
        }
    }

    protected function camelize($name)
    {
        if (strpos($name, '-') || strpos($name, '_')) {
            $name = str_replace('-', '_', $name);
            $name = explode('_', $name);
            $name = array_map('ucfirst', $name);
            $name = implode('', $name);
        }

        return lcfirst($name);
    }
}