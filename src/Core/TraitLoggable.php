<?php

namespace Core;

trait TraitLoggable
{
    /** Debugging
     * @param $msg
     * @return void
     */
    protected function logInTheShell($msg)
    {
        echo "==== " . get_called_class() . " ====" . PHP_EOL;
        echo $msg . PHP_EOL;
    }
}