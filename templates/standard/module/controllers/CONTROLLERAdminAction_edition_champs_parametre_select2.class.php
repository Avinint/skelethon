        $aRetour['aSelects']['NAME'] = $oParametre->aGetSelectMenu('TYPE', ['id', 'text']);
        $aRetour['oSelectDefauts'] = new \StdClass();
        $aRetour['oSelectDefauts']->NAME = 'DEFAULT';
        $aRetour['oElement']->NAME = $aRetour['oElement']->NAME ?? 'DEFAULT';
        $oParametre = $this->oNew('Parametre');
        $oMODEL->NAME = $oParametre->mGetParamValue('TYPE', 'CODE');