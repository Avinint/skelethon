<?php

define('ARRAY_ALL', false);
define('ARRAY_ANY', true);
const DS = DIRECTORY_SEPARATOR;

function autoloader($class_name)
{
    $class_name = str_replace('\\', DS, 'src'.DS.$class_name);
    if (file_exists(__DIR__.DS.$class_name.'.php')) {
        require $class_name.'.php';
    }
}

function getArguments($arguments): array
{
    $arguments = array_replace(array_fill(0, 5, ''), $arguments);
    array_shift($arguments);
    $type = array_shift($arguments);
    return [$type, $arguments];
}

function array_contains($needle, array $haystack, bool $any = false, $has_nested_arrays = false): bool
{

    if (is_array($needle)) {
        return array_contains_array($needle, $haystack, $any, $has_nested_arrays);
    }

    return isset(array_flip($haystack)[$needle]);
}

function array_contains_array(array $needle, array $haystack, bool $any = false, bool $has_nested_arrays = false): bool
{
    if ($has_nested_arrays) {
        return strpos(serialize($haystack), serialize($needle)) !== false;
    } elseif ($any) {
        return !empty(array_intersect($needle, $haystack));
    }
    return empty(array_diff($needle, $haystack));
}

function str_replace_first(string $search, string $replace, string $subject) : string
{
    if (strpos($subject,  $search) !== false) {
        return substr_replace($subject, $replace, strpos($subject,  $search), strlen($search));
    }
    return $subject;
}

function str_replace_last(string $search, string $replace, string $subject) : string
{
    if (strrpos($subject,  $search) !== false) {
        return substr_replace($subject, $replace, strrpos($subject,  $search), strlen($search));
    }
    return $subject;
}

function logo_blank_line()
{
    return '                                                                                ' .PHP_EOL;
}

function logo_border_line()
{
    return '================================================================================' .PHP_EOL;
}

function alphanum()
{
   return (implode('', array_merge(range('a', 'z'),  range(0, 9), range('A', 'Z'))));
}