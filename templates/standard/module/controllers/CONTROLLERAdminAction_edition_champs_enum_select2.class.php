        $aRetour['aSelects']['NAME'] = $this->aGetValeursListeConf('mODULE', 'MODEL', 'COLUMN', ['id', 'text']);
        $aRetour['oSelectDefauts'] = new \StdClass();
        $aRetour['oSelectDefauts']->NAME = 'DEFAULT';
        $aRetour['oElement']->NAME = $aRetour['oElement']->NAME ?? 'DEFAULT';