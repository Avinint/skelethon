        $aRetour['aSelects']['NAME'] = $oParametre->aGetSelectMenu('TYPE');
        $oParametre = $this->oNew('Parametre');
        $oMODEL->NAME = $oParametre->mGetParamValue('TYPE', 'CODE');
