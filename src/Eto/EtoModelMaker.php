<?php

namespace Eto;

use Core\Field;
use E2D\E2DModelMaker;

class EtoModelMaker extends E2DModelMaker
{
//    public function getTableHeaders($templatePath)
//    {
//        $actionHeader = empty($this->actions) ? '' : file_get_contents($this->getTrueTemplatePath($templatePath->add('actionheader')));
//        return implode(PHP_EOL, array_map(function (Field $field) use ($templatePath) {
//                return $field->getTableHeader($templatePath);
//            }, $this->getFields('liste'))).PHP_EOL. $actionHeader;
//    }

    /**
     * @param $colonne
     * @param $nomParametre
     * @return array
     */
    protected function getChampsParametresPotentiels($nomParametre) : array
    {
        $select = "SELECT module, code, valeur FROM parametre WHERE archive =  0 AND module  = '" . $nomParametre . "'";

        return $this->databaseAccess->query($select, null, false, true);
    }
}