    /**
     * Document Ready
     * Tout ce qui est ajouté ici sera automatiquement appelé au chargement.
     *
     * @return {void}
     */
    this.vChargementPage = function() {
        this.vAfficheFilAriane('<h1>TITRE</h1>');

        var oParams = {
            eFormulaire: $('#zone_navigation_2 form'),
            bChargementPage: true,
        };
        this.vExecuteAction('', 'mODULE', 'btn_dynamisation_recherche_TABLE', oParams);
    }

    /**
     * Callback exécutée à la suite de la dynamisation de la liste.
     *
     * @param object oReponseJSON   Infos JSON récupérées lors de l'appel Ajax.
     * @param object oParams        Paramètres passés avant l'appel Ajax.
     *
     * @return void
     */
    this.vCallbackListeElement = function(oReponseJSON, oParams)
    {
        oParams.bSansVidage = true;
        var oInfos = {
            aSelects: oReponseJSON.aSelects,
            oSelectDefauts: oReponseJSON.oSelectDefauts
        };

        var oCallback = this.oGetFonctionCallback(this, this.vDynamisationFormulaireRecherche, oParams);
        this.vChargeFormulaireData(oInfos, oParams, oCallback);
    };

    /**
     *Callback exécutée à la suite de la dynamisation du formulaire de recherche.
     *
     *@param object oReponseJSON   Infos JSON récupérées lors de l'appel Ajax.
     *@param object oParams        Paramètres passés avant l'appel Ajax.
     *
     *@return void
     */
    this.vDynamisationFormulaireRecherche = function(oReponseJSON, oParams)
    {
        oParams.szModuleChargeListe = 'TABLE';
        var eFormulaire = $('#zone_navigation_2').find('form');
SELECT2

        this.vChargeEvenementsChampsRecherche('TABLE', oParams);
    }
