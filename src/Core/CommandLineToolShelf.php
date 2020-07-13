<?php


namespace Core;


trait CommandLineToolShelf
{
    protected static $verbose;

    public function msg(string $text, $type = '', $hasDisplayYesNo = false, $verboseCondition = true, $important = false)
    {
        if ((self::$verbose && $verboseCondition) || empty($type) || $important) {
            $displayYesNo = $hasDisplayYesNo ? ' ['.$this->highlight('O', 'success').'/'.$this->highlight('N', 'error').']' : '';

            echo ($type? $this->frame(strtoupper($type), $type).' ' : '')  . $text . $displayYesNo . PHP_EOL. ($type ? '' : '==> ');
        }

        return !empty($type);
    }

    public function displayList($list, $hl = '', $delim1 = '', $delim2 = PHP_EOL)
    {
        return implode($delim1, array_map(function($el) use ($hl, $delim2) { if ($hl) {$el = $this->highlight($el, $hl);} return "\t$el". $delim2;}, $list));
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
        return ucfirst(strtolower(str_replace(['-', '_'], ' ', $name)));
    }

    protected function camelize($name = '')
    {
        return strtolower(str_replace(['-', ' '], '_', $name));
    }
}