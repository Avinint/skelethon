        $aRetour['aSelects']['NAME'] = $this->aGetValeursListeConf('mODULE', 'TABLE', 'COLUMN');
        $aRetour['oElement'] = new \StdClass();
        $aRetour['oElement']->NAME = 'DEFAULT';
        $oParametre = $this->oNew('Parametre');
        $oMODEL->NAME = $oParametre->mGetParamValue('TYPE', 'CODE');