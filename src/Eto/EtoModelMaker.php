<?php

namespace Eto;

use Core\Field;
use E2D\E2DModelMaker;

class EtoModelMaker extends E2DModelMaker
{
    public function getTableHeaders($templatePath)
    {
        $actionHeader = empty($this->actions) ? '' : file_get_contents($this->getTrueTemplatePath($templatePath, '_actionheader.'));
        return implode(PHP_EOL, array_map(function (Field $field) use ($templatePath) {
                return $field->getTableHeader($templatePath);
            }, $this->getFields('liste'))).PHP_EOL. $actionHeader;
    }
}