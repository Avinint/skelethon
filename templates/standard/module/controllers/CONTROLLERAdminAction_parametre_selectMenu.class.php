        $oParametre = $this->oNew('Parametre');
        $oMODEL->NAME = $oParametre->mGetParamValue('TYPE', 'CODE');
        $aRetour['aSelects']['NAME'] = $oParametre->aGetSelectMenu('TYPE');