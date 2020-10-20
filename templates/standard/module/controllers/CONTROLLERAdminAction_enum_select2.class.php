        $aRetour['aSelects']['NAME'] = $oParametre->aGetSelectMenu('TYPE');
        $aRetour['oSelectDefauts'] = new \StdClass();
        $aRetour['oSelectDefauts']->NAME = 'DEFAULT';
        $aRetour['oElement']->NAME = isset($aRetour['oElement']->NAME) ? $aRetour['oElement']->NAME : 'DEFAULT';