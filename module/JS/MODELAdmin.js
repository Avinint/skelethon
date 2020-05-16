function MODELAdmin()
{
    Recherche.apply(this, arguments);

    var oThis = this;

    this.vInit = function() {
        this.vAfficheFilAriane('<h1>Les MODELs</h1>');
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
            aSelects: oReponseJSON.aSelects
        };
        var oCallback = this.oGetFonctionCallback(this, this.vCallbackDynamisationFormulaireRecherche, oParams);
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
    this.vCallbackDynamisationFormulaireRecherche = function(oReponseJSON, oParams)
    {
        oParams.szModuleChargeListe = 'TABLE';
        this.vChargeEvenementsChampsRecherche('TABLE', oParams);
    }

    /**
     * Callback exécutée à la suite de la dynamisation de la liste.
     *
     * @return void
     */
    this.vChargeEvenementsBoutons = function() {
    // $('.btn_supp').off('click');
    // $('.btn_supp').on('click', function(oEvent) {
    // oEvent.preventDefault();
    // alert('supp');
    // });
    };
//ACTION
};