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

        $oElement->IDFIELD = $nIdElement;

        //$aChamps = $this->aGetDonnees($oElementEXCEPTIONS);
        $aChamps = [CHAMPS];

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
