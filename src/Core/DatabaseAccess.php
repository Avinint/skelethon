<?php

namespace Core;

use \PDO;

abstract class DatabaseAccess implements DatabaseAccessInterface
{
    protected $hostname;
    protected $username;
    protected $password;
    protected $dBName =  '';
    protected $pdo;

    /**
     * Database constructor.
     * @param $hostname
     * @param $username
     * @param $password
     * @param string $dBName
     */
    public function __construct($hostname, $username, $password, string $dBName)
    {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->dBName = $dBName;
    }


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

    public function getMaxLength($maxLength)
    {

        if (preg_match('/,/', $maxLength)) {
            $aLength = explode(',', $maxLength);
            $maxLength = 1;
            $maxLength += (int)$aLength[0];
            $maxLength += (int)$aLength[1];
            $step = 1 / (10 ** (int)$aLength[1]);
            return [(int)$maxLength, $step];
        }

        return (int)$maxLength;
    }

    public function getStep($maxLength)
    {
        $maxLength = str_replace([')', ' unsigned'], '',$maxLength);
        if (strpos($maxLength, ',') > 0) {
            $aLength = explode(',', $maxLength);
            $step = 1 / (10 ** (int)$aLength[1]);
            return $step;
        }

        return null;
    }

    abstract public function aListeTables();

}