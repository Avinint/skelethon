        $aRetour['aSelects']['NAME'] = $oParametre->aGetSelectMenu('TYPE');
        $aRetour['oElement'] = new \stdClass();
        $aRetour['oElement']->NAME = 'DEFAULT';
        $aRetour['oElement']->NAME = $aRetour['oElement']->NAME ?? 'DEFAULT';
        $oParametre = $this->oNew('Parametre');
        $oMODEL->NAME = $oParametre->mGetParamValue('TYPE', 'CODE');