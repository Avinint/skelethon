
        if (isset($_REQUEST['szOrderBy']) === true && $_REQUEST['szOrderBy'] != '') {
            $szOrderBy = $_REQUEST['szOrderBy'];
        } else {
            $szOrderBy = 'PK DESC';
        }

        $aRetour['aElements'] = $oElement->aGetElements($aRecherche, 0, 0, $szOrderBy);