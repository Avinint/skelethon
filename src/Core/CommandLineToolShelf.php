<?php


namespace Core;


abstract class CommandLineToolShelf
{
    const Color = ['Red' => "\e[1;31m", 'Yellow' => "\e[1;33m", 'Green' => "\e[1;32m", 'White' => "\e[1;37m", 'Blue' => "\e[1;36m", 'Orange' => "\e[38;5;208m"];

    protected static $verbose;

    /**
     * @param array $validValues
     * @return string
     */
    private function displayValidNumericAnswers(array $validValues): string
    {
        $display = '';
        if (array_contains_array($validValues, range(1, count($validValues)))) {
            $valueArray = [];
            foreach ($validValues as $key => $value) {
                $colors = ['success', 'error', 'info', 'important', 'warning'];
                $color = $colors[$key] ?? 'warning';
                //$color = ($value == 1 ? 'success' : ($value == 2 ? 'error' : ($value == 3 ? 'warning' : 'info')));
                $valueArray[] = $this->highlight($value, $color);
            }
            $display = '[' . implode('/', $valueArray) . ']';
        }
        return $display;
    }

    /**
     * Affiche un message avrc un code couleur ou une question
     * @param string $text
     * @param string $type
     * @param array $validValues
     * @param bool $verboseCondition
     * @param false $important
     * @return bool
     */
    public function msg(string $text, $type = '', $validValues = [], $colon = false, $verboseCondition = true, $important = false)
    {
        $display = '';
        if ((self::$verbose && $verboseCondition) || empty($type) || $important) {
            if (!empty($validValues)) {
                $display = $validValues === ['o', 'n'] ? ' ['.$this->highlight('O', 'success').'/'.$this->highlight('N', 'error').']' : '';
                if (empty($display)) {
                    $display = $this->displayValidNumericAnswers($validValues);
                }
            }

            echo ($type? $this->frame(strtoupper($type), $type).' ' : '')  . $text . $display  . ($colon && empty($validValues) ? '' : ' :').PHP_EOL;
        }

        return !empty($type);
    }

    /**
     * Affiche une liste de choix
     * @param $list
     * @param string $hl
     * @param string $delim1
     * @param string $delim2
     * @return string
     */
    public function displayList($list, $hl = '', $delim1 = '', $delim2 = PHP_EOL)
    {
        return implode($delim1, array_map(function($el) use ($hl, $delim2) { if ($hl) {$el = $this->highlight($el, $hl);} return "\t$el". $delim2;}, $list));
    }

    private function getColorFromType($type)
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

    /**
     * Permet de poser une question tant que la réponse n'est pas valide (vide ou pas dans la liste des réponses valides)
     * @param $msg
     * @param array $validValues
     * @param false $keepCase
     * @return false|string
     */
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
                $tempResult = readline($this->msg($msg, '',  $validValues));
                $result = $keepCase ? $result : strtolower($tempResult);
            }
        }

        return $result;
    }

    /**
     * Permet de poser une question en affichant une liste de choix possibles
     * @param bool $defaultValue
     * @param string $key
     * @param array $choices
     * @return bool|string
     */
    protected function askMultipleChoices(string $key, array $choices, $defaultValue = false, $reference  = '', $freeChoice = false)
    {
        $msgDefault = $defaultValue !== false ?
            ($defaultValue !== '' ?
            PHP_EOL . 'En cas de chaine vide, Le ' . $key . ' ' . $this->frame($defaultValue, 'success') . ' sera sélectionné par défaut' :
            PHP_EOL . 'Vous pouvez également choisir une chaine vide') :
        '';
        $texteReference  = empty($reference) ? '' : ' ('.$reference.') ';
        $allowedValues = $freeChoice ? [] : array_merge($choices, ['']);

        $selection = $this->prompt('Choisir un/e ' . $key . ' dans la liste suivante'. $texteReference.' :' . PHP_EOL . $this->displayList($choices, 'info') . $msgDefault, $allowedValues);
        if ($defaultValue !== false && $selection === '') {
            $selection = $defaultValue;
        }
        return $selection;
    }

    /**
     * Encadre un texte en couleur avec des crochets
     * @param $text
     * @param $type
     * @return string
     */
    public function frame($text, $type)
    {
        $color = static::getColorFromType($type);

        return self::Color['White'] . '[' . $color . $text . self::Color['White'] . ']' ;
    }

    /**
     * Surligne une chaine de caractère en couleur
     * @param $text
     * @param string $type
     * @return string
     */
    public function highlight($text, $type = 'warning')
    {
        $color = static::getColorFromType($type);

        return $color.$text.self::Color['White'];
    }

    /**
     * Transforme un nom de variable en PascalCase
     * @param string $name
     * @return string
     */
    protected function pascalize($name = '')
    {
        $name = str_replace('-', '_', $name);
        $name = explode('_', $name);
        $name = array_map('ucfirst', $name);
        $name = implode('', $name);

        return $name;
    }

    /*
     * Transforme un nom de variable en chaine de type libellé
     */
    protected function labelize($name = '')
    {
        return ucfirst(strtolower(str_replace(['-', '_'], ' ', $name)));
    }

    /**
     * Convertit un nom de variable en underscore_case ou snake_case
     * @param string $name
     * @return string
     */
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

    /**
     * Transforme un nom de variable en chaine séparée par des tirets
     * @param string $name
     * @param false $noHyphen
     * @return string
     */
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

    /**
     * Transforme une chaine de caractères en camelCase
     * @param $name
     * @return string
     */
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