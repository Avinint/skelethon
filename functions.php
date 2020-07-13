<?php

define('ARRAY_ALL', true);
define('ARRAY_ANY', false);
const DS = DIRECTORY_SEPARATOR;

function array_contains($needle, array $haystack, bool $all = false, $has_nested_arrays = false): bool
{
    if (is_array($needle)) {
        return array_contains_array($needle, $haystack, $all, $has_nested_arrays);
    }

    return isset(array_flip($haystack)[$needle]);
}

function array_contains_array(array $needle, array $haystack, bool $all, bool $has_nested_arrays = false): bool
{
    if ($has_nested_arrays) {;
        return strpos(serialize($haystack), serialize($needle)) !== false;
    } elseif ($all) {
        return empty(array_diff($needle, $haystack));
    }
    return !empty(array_intersect($needle, $haystack));
}

function str_replace_first($search, $replace, $subject)
{
    if (strpos($subject,  $search) !== false) {
        return substr_replace($subject, $replace, strpos($subject,  $search), strlen($search));
    }
    return $subject;
}

function str_replace_last($search, $replace, $subject)
{
    if (strrpos($subject,  $search) !== false) {
        return substr_replace($subject, $replace, strrpos($subject,  $search), strlen($search));
    }
    return $subject;
}