<?php

namespace Eto;

use Core\Field;
use E2D\E2DModelMaker;

class EtoModelMaker extends E2DModelMaker
{
    public function getTableHeaders()
    {
        $actionHeader = empty($this->actions) ? '' : str_repeat("\x20", 20).'<th id="th_actions"></th>';
        return  implode(PHP_EOL, array_map(function (Field $field) {return $field->getTableHeader();}, $this->fields)).PHP_EOL.$actionHeader;
    }
}