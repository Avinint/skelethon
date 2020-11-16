        $aRetour['aSelects']['NAME'] = $this->aGetValeursListeConf('mODULE', 'MODEL', 'COLUMN');
        $aRetour['oElement'] = new \StdClass();
        $aRetour['oElement']->NAME = 'DEFAULT';
        $aRetour['oElement']->NAME = $aRetour['oElement']->NAME ?? 'DEFAULT';