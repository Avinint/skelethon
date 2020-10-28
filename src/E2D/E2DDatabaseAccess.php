<?php

namespace E2D;

use Core\DatabaseAccess;

class E2DDatabaseAccess extends DatabaseAccess
{
    private $tables = [];

    public static function getDatabaseParams()
    {
        if (!isset($GLOBALS['aParamsAppli']) || !isset($GLOBALS['aParamsBdd'])) {
            $text = str_replace('<?php', '',file_get_contents('surcharge_conf.php'));

            eval($text);
        }
        return new static(
            'localhost',
            $GLOBALS['aParamsBdd']['utilisateur'],
            $GLOBALS['aParamsBdd']['mot_de_passe'],
            $GLOBALS['aParamsBdd']['base']
        );
    }

    public function getTable(string $tableName)
    {
        return $this->getTableList()[$tableName] ?? null;
    }

    /**
     * @param $tableName
     * @return array
     */
    public function getSimilarTableList($tableName)
    {
        $sRequete = 'SHOW tables FROM `' . $this->getDBName(). '` LIKE \'%'.$tableName.'%\'';
        $tables = array_map(function ($row) use ($tableName) {return $row->{'Tables_in_'.$this->getDBName(). ' (%'.$tableName.'%)'};}, $this->query($sRequete));
        return $tables;
    }

    public function getTableList()
    {
        if (empty($this->tables)) {

            $sRequete = 'SHOW tables FROM `'.$this->dBName.'`';

            $aResultats = $this->query($sRequete);

            $sCle = 'Tables_in_'.$this->dBName;
            foreach ($aResultats as $oTable) {
                $sNomTable = $oTable->$sCle;
                $this->tables[$sNomTable] = array();

                $sRequete = "SHOW columns FROM ".$sNomTable;
                $aResultats = $this->query($sRequete);

                foreach ($aResultats as $oChamp) {
                    $aType = explode('(', $oChamp->Type);
                    $oChamp->sType = array_shift($aType);
                    $sMaxLength = array_shift($aType);

                    $aNom = explode('_', $oChamp->Field);
                    $aNom = array_map('ucfirst', $aNom);
                    $sNom = implode('', $aNom);

                    switch ($oChamp->sType) {
                        case 'tinyint':
                            if ($sMaxLength != '') {
                                $oChamp->maxLength = $this->getMaxLength($sMaxLength);
                            }
                            if (1 === $oChamp->maxLength) {
                                $oChamp->sType = 'bool';
                                $oChamp->sChamp = 'b'.$sNom;
                            } else {
                                $oChamp->sChamp = 'n'.$sNom;
                            }
                            break;
                        case 'int':
                        case 'smallint':
                            $oChamp->sChamp = 'n'.$sNom;
                            if ($sMaxLength != '') {
                                $oChamp->maxLength = $this->getMaxLength($sMaxLength);
                            }
                            break;
                        case 'char':
                        case 'varchar':
                        case 'text':
                        case 'mediumtext':
                        case 'longtext':
                            $oChamp->sChamp = 's'.$sNom;
                            if ($sMaxLength != '') {
                                $oChamp->maxLength = $this->getMaxLength($sMaxLength);
                            }
                            break;

                        case 'enum':
                            $oChamp->sChamp = 's'.$sNom;
                            break;

                        case 'datetime':
                            $oChamp->sChamp = 'dt'.$sNom;
                            break;

                        case 'time':
                            $oChamp->sChamp = 't'.$sNom;
                            break;

                        case 'date':
                            $oChamp->sChamp = 'd'.$sNom;
                            break;

                        case 'decimal':
                        case 'float':
                        case 'double':
                            $oChamp->sChamp = 'f'.$sNom;
                            if ($sMaxLength != '') {
                                $oChamp->maxLength = $this->getMaxLength($sMaxLength);
                                $oChamp->step = $this->getStep($sMaxLength);
                            }
                            break;
                    }

                    $this->tables[$sNomTable][$oChamp->Field] = $oChamp;
                }

            }
        }

        // echo "<pre>".print_r($aTables, true)."</pre>";
        return $this->tables;;
    }
}