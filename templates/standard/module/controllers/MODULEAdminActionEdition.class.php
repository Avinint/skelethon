    /**
     * Dynamisation d'un élément
     *
     * @param integer $nIdElement Id de l'élément
     *
     * @return array Retour JSON
     */
    private function aDynamisationEdition($nIdElement = 0)
    {
        $aRetour = array(
            'oElement' => new \StdClass(),
        );

//EDITSELECT
        if ($nIdElement > 0) {
            $aRetour['oElement'] = $this->oNew('MODEL', array($nIdElement));
        }

//DEFAULT

        return $aRetour;
    }

    /**
     * Enregistrement d'un élément
     *
     * @param integer $nIdElement Id de l'élément
     *
     * @return array Retour JSON.".PHP_EOL.
     */
    private function aEnregistreEdition($nIdElement = 0)
    {
        $aRetour = array(
            'bSucces' => false,
            'bModif' => false
        );

        $oElement = $this->oNew('MODEL');

        $oElement->nIdMODEL = $nIdElement;

        $aChamps = $this->aGetDonnees($oElementEXCEPTIONS);
        /*foreach ($_REQUEST as $mCle => $mValeur) {
            if (in_array($mCle, $oElement->aGetChamps())) {
                if (in_array($mCle, [DATE]) {
                    $aChamps[$oElement->aGetColonnes()[$mCle]] = $this->sGetDateFormatUniversel($mValeur], 'Y-m-d'),
                } else {
                    $aChamps[$oElement->aGetColonnes()[$mCle]] = $mValeur;
                }
            }
        }*/

        if ($nIdElement > 0) {
            $aRetour['bSucces'] = $oElement->bUpdate($aChamps);
            $aRetour['bModif'] = true;
        } else {
            $aRetour['bSucces'] = $oElement->bInsert($aChamps);
        }

        if ($aRetour['bSucces'] === false) {
            $aRetour['szErreur'] = $oElement->sMessagePDO;
        }

        $aRetour['oElement'] = new \StdClass();
        $aRetour['oElement']->nIdElement = $nIdElement;

        return $aRetour;
    }

    /**
    * @param $oElement
    * @param array $aChampsDate
    * @return array
    */
    private function aGetDonnees($oElement, $aExceptions = [])
    {
        $aChamps = [];

        foreach ($_REQUEST as $mCle => $mValeur) {
            if (in_array($mCle, $oElement->aGetChamps())) {
                if (isset($aExceptions['aDates']) && in_array($mCle, $aExceptions['aDates'])) {
                    $aChamps[$oElement->aGetColonnes()[$mCle]] = $this->sGetDateFormatUniversel($mValeur, 'Y-m-d');
                } elseif (isset($aExceptions['aBooleens'])  && in_array($mCle, $aExceptions['aBooleens'])) {
                    if ($mValeur !== 'nc') {
                        $aChamps[$oElement->aGetColonnes()[$mCle]] = $mValeur;
                    }
                } else {
                    $aChamps[$oElement->aGetColonnes()[$mCle]] = $mValeur;
                }
            }
        }

        return $aChamps;
    }
