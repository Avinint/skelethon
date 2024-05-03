<?php

namespace G4E;

use \PDO;
use E2D\E2DDatabaseAccess;

class G4EDatabaseAccess extends E2DDatabaseAccess
{
    protected function getPDO()
    {
        if ($this->pdo === null) {
            try
            {
                $this->pdo = new PDO('oci:dbname=//' . $this->hostname . '/' . $this->dBName . ';charset=al32utf8', $this->username, $this->password);

                // set the PDO error mode to exception
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
            }
            catch(PDOException $e) {
                echo "Echec de connection: " . $e->getMessage();
            }
        }

        return $this->pdo;
    }


    public function getTableList($legacyPrefixes = false, $table = null)
    {
        if (empty($this->tables)) {

            if (empty($table)) {
                $sRequete = "SELECT TABLE_NAME sNom FROM SYS.ALL_TABLES WHERE OWNER = '" . $GLOBALS['aParamsBdd']['utilisateur'] . "' ORDER BY TABLE_NAME";
                $aResultats = $this->query($sRequete);

            } else {
                $aResultats = [(object)['sNom' => $table]];
            }

            foreach ($aResultats as $oTable) {

                $this->tables[$oTable->sNom] = array();



                $sRequete = "
                    SELECT COL.COLUMN_ID,
                    (
                        SELECT (CASE WHEN CON.CONSTRAINT_TYPE = 'P'  THEN 'PRI' END)
                        FROM all_constraints CON
                        INNER JOIN ALL_CONS_COLUMNS COLS ON CON.CONSTRAINT_NAME = COLS.CONSTRAINT_NAME
                        WHERE COLS.COLUMN_NAME = COL.COLUMN_NAME AND CON.TABLE_NAME = COL.TABLE_NAME
                        AND CON.CONSTRAINT_TYPE = 'P' AND ROWNUM = 1
                    ) KEY,
                    COL.COLUMN_NAME \"sNom\",
                    COL.DATA_TYPE \"sType\",
                    COL.DATA_LENGTH \"sMaxLength\",
                    COL.DATA_PRECISION \"nPrecision\",
                    COL.DATA_SCALE \"nScale\",
                    COL.NULLABLE \"sNullable\",
                    COL.DATA_DEFAULT
                    FROM SYS.ALL_TAB_COLUMNS COL
                     INNER JOIN SYS.ALL_TABLES T ON COL.OWNER = T.OWNER AND COL.TABLE_NAME = T.TABLE_NAME
                    WHERE COL.OWNER = '" . $GLOBALS['aParamsBdd']['utilisateur'] . "'
                      AND COL.TABLE_NAME = '" . $oTable->sNom . "'
                    ORDER BY COL.COLUMN_ID";


                $aResultats = $this->query($sRequete);

                foreach ($aResultats as $oChamp) {
                    $aNom = explode('_', $oChamp->sNom);
                    $aNom = array_map(function ($sUnNom) {return ucfirst(strtolower($sUnNom));}, $aNom);
                    $sNom = implode('', $aNom);

                    $oChamp->Key = $oChamp->KEY;
                    $oChamp->Default = $oChamp->DATA_DEFAULT;
                    $oChamp->Field = $oChamp->sNom;
                    $oChamp->Type = '';
                    $oChamp->Null = $oChamp->sNullable === 'N' ? 'NO' : 'YES';


                    switch ($oChamp->sType) {
                        case 'NUMBER':
                            if ($oChamp->nScale > 0) {
                                $oChamp->sChamp = 'f' . $sNom;
                                $oChamp->sType = 'double';
                            } elseif ($oChamp->nPrecision == 1) {
                                $oChamp->sChamp = 'b' . $sNom;
                                $oChamp->sType = 'bool';
                            } else {
                                $oChamp->sChamp = 'n' . $sNom;
                                $oChamp->sType = 'int';
                            }

                            $oChamp->sMaxLength = $oChamp->nPrecision + $oChamp->nScale + 1;
                            break;
                        case 'VARCHAR2':
                            $oChamp->sChamp = 's' . $sNom;
                            $oChamp->sType = 'varchar';
                            break;
                            //TEXT
                        case 'CLOB':
                        case 'NCLOB':
                            $oChamp->sChamp = 's' . $sNom;
                            $oChamp->sType = 'text';
                            break;
                        case 'TIMESTAMP(6)':
                            $oChamp->sChamp = 'd' . $sNom;
                            $oChamp->sType = 'timestamp';
                            break;
                        case 'DATE':
                            $oChamp->sChamp = 'd' . $sNom;
                            $oChamp->sType = 'datetime';
                            break;
                        case 'BINARY_DOUBLE':
                            $oChamp->sChamp = 'f' . $sNom;
                            $oChamp->sType = 'double';
                            $oChamp->sMaxLength = $oChamp->nPrecision + $oChamp->nScale + 1;
                            break;
                    }

                    $this->tables[$oTable->sNom][$oChamp->sNom] = $oChamp;
                }
            }
        }

        // echo "<pre>".print_r($aTables, true)."</pre>";

        return $this->tables;
    }

    protected function getTypeMapping($sType)
    {
        $mappedTypes = [
            'NUMBER' => 'int',
            'VARCHAR2' => 'varchar',
            'CLOB' => 'text',
            'NCLOB' => 'text',
            'TIMESTAMP(6)' => 'timetamp',
            'DATE' => 'datetime',
            'BINARY_DOUBLE' => 'double'
        ];
        return $mappedTypes[$sType] ?? $sType;
    }
}