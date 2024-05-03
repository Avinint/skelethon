    /**
     * Suppression d'un élément
     *
     * @param integer $nIdElement Id de l'élément
     *
     * @return array Retour JSON
     */
    private function aSuppression($nIdElement = 0)
    {
        $aRetour = array(
            'bSucces' => false,
            'szErreur' => '',
        );

        $oElement = $this->oNew('MODEL', array($nIdElement));
        $aRetour['bSucces'] = $oElement->bDelete();

        if ($aRetour['bSucces'] === false) {
            $aRetour['szErreur'] = $oElement->sMessagePDO;
        }

        return $aRetour;
    }
