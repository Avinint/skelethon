        $nNbElementsParPage = $_REQUEST['nNbElementsParPage'];
        $oPagination = $this->oGetInfosPagination($oElement, $aRecherche, $nNbElementsParPage);
        $aRetour['aPagination'] = $oPagination;

        if (isset($_REQUEST['szOrderBy']) === true && $_REQUEST['szOrderBy'] != '') {
            $aRetour['aElements'] = $oElement->aGetElements($aRecherche, $oPagination->nStart, $nNbElementsParPage, $_REQUEST['szOrderBy']);
        } else {
            $aRetour['aElements'] = $oElement->aGetElements($aRecherche, $oPagination->nStart, $nNbElementsParPage);
        }