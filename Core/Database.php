<?php

namespace Core;

use \PDO;

trait Database
{
    protected $hostname;
    protected $username;
    protected $password;
    protected $dBName =  '';
    protected $pdo;

    protected function getPDO()
    {
        if ($this->pdo === null) {
            try
            {
                $this->pdo = new PDO("mysql:host=$this->hostname;dbname=$this->dBName", $this->username, $this->password);
                // set the PDO error mode to exception
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            catch(PDOException $e) {
                echo "Echec de connection: " . $e->getMessage();
            }
        }

        return $this->pdo;
    }

    public function query($statement, $class = null, $one = false)
    {
        $data = null;

        $req = $this->getPDO()->query($statement);
        if (is_null($class)){
            $req->setFetchMode(PDO::FETCH_OBJ);
        } else {
            $req->setFetchMode(PDO::FETCH_CLASS, $class);
        }

        if ($one === true){
            $data = $req->fetch();
        } elseif ($one === false){
            $data = $req->fetchAll();
        }

        return $data;

    }

    public function prepare($statement, $attr, $class = null, $one = false, $ctor = null)
    {
        $data = null;
        $req = $this->getPDO()->prepare($statement);
        $res = $req->execute($attr);

        if (
            strpos($statement, 'UPDATE') === 0 ||
            strpos($statement, 'INSERT') === 0 ||
            strpos($statement, 'DELETE') === 0

        ) {
            return $res;
        }

        if (is_null($class)) {
            $req->setFetchMode(PDO::FETCH_OBJ);
        } else {
            if (!is_null($ctor)) {
                $req->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, $class, $ctor);
            }

            else{
                $req->setFetchMode(PDO::FETCH_CLASS, $class);
            }
        }

        if($one){
            $data = $req->fetch();
        } else if($one === false){
            $data = $req->fetchAll();
        }

        return $data;
    }

    public function lastInsertId()
    {

        return $this->getPDO()->lastInsertId();

    }

    public function aListeTables()
    {
        $aTables = array();

        $sRequete = 'SHOW tables FROM ' . $this->dBName;

        $aResultats = $this->query($sRequete);

        $sCle = 'Tables_in_'.$this->dBName;
        foreach ($aResultats as $oTable) {
            $aTables[$oTable->$sCle] = array();

            $sRequete = "SHOW columns FROM ".$oTable->$sCle;
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
                        }
                        break;
                }

                $aTables[$oTable->$sCle][$oChamp->Field] = $oChamp;
            }

        }
        // echo "<pre>".print_r($aTables, true)."</pre>";

        return $aTables;
    }

    public function getMaxLength($maxLength)
    {
        $maxLength = str_replace([')', ' unsigned'], '',$maxLength);
        if (preg_match('/,/', $maxLength)) {
            $aLength = explode(',', $maxLength);
            $maxLength = 1;
            $maxLength += (int)$aLength[0];
            $maxLength += (int)$aLength[1];
        }

        return (int)$maxLength;
    }
}