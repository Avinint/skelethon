    /**
     * Insertion d'un élément.
     *
     * @param  array  $aChamps Champs concernés par l'édition.
     *
     * @return void
     */
    public function bInsert($aChamps = array(), $aChampsNull = [])
    {
        $bRetour = false;

        $sRequete = '
            INSERT INTO TABLE (
INSERTCOLUMNS
            ) VALUES (
INSERTVALUES
        )';

        // echo \"<pre>$sRequete</pre>\";
        // exit;

        $rLien = $this->rConnexion->query($sRequete);
        $this->IDFIELD = $this->rConnexion->lastInsertId();
        if ($rLien) {
            $bRetour = true;
            //$this->bSetLog('insert_mODEL', $this->IDFIELD);
        } else {
            $this->sMessagePDO = $this->rConnexion->sMessagePDO;
        }

        return $bRetour;
    }

    /**
     * Mise à jour d'un élément.
     *
     * @param  array   Champs concernés par l'édition.
     *
     * @return void
     */
    public function bUpdate($aChamps = array(), $aChampsNull = [])
    {
        $bRetour = false;

        $sRequete = '
            UPDATE TABLE SET
EDITCHAMPS
            WHERE PK = \''.$this->IDFIELD.'\'
        ';

        // echo "<pre>$sRequete</pre>";
        // exit;

        $rLien = $this->rConnexion->query($sRequete);
        if ($rLien) {
            $bRetour = true;
            //$this->bSetLog('update_mODEL', $this->IDFIELD);
        } else {
            $this->sMessagePDO = $this->rConnexion->sMessagePDO;
        }

        return $bRetour;
    }

    /**
     * Suppression d'un élément.
     *
     * @return void
     */
    public function bDelete()
    {
        $bRetour = false;

        $sRequete = '
                DELETE
                FROM TABLE
                WHERE PK = '.$this->IDFIELD;

        // echo "<pre>$sRequete</pre>";
        // exit;

        $rLien = $this->rConnexion->query($sRequete);

        if ($rLien) {
            $bRetour = true;
            //$this->bSetLog('delete_mODEL', $this->IDFIELD);
        } else {
            $this->sMessagePDO = $this->rConnexion->sMessagePDO;
        }

        return $bRetour;
    }