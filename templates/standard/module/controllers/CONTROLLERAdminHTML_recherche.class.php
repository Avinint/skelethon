
            $sFichierContenu = $this->szGetFichierPourInclusion('modules', 'mODULE/vues/recherche_TABLE.html');
            $oContenu = $this->oGetVue($sFichierContenu);
            $this->objQpModele->find('#zone_navigation_2')->html($oContenu->find('body')->html());