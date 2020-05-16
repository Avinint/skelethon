    /**
     * Consultation d'un élément
     *
     * @param integer $nIdElement Id de l'élément
     *
     * @return array Retour JSON
     */
    private function aDynamisationConsultation($nIdElement = 0)
    {
        $aRetour = array(
                'oElement' => new \StdClass(),
        );

        $aRetour['oElement'] = $this->oNew('MODEL', array($nIdElement));

        return $aRetour;
    }
