        $aRetour['aSelects']['NAME'] = $this->aGetValeursListeConf('mODULE', 'TABLE', 'COLUMN', ['id', 'text']);
        $aRetour['oSelectDefauts'] = new \StdClass();
        $aRetour['oSelectDefauts']->NAME = 'DEFAULT';
        $aRetour['oElement']->NAME = isset($aRetour['oElement']->NAME) ? $aRetour['oElement']->NAME : 'DEFAULT';