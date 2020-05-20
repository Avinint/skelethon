    /**
     * Document Ready
     * Tout ce qui est ajouté ici sera automatiquement appelé au chargement.
     *
     * @return {void}
     */
    this.vChargementPage = function() {
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

        this.vChargeFormulaireData(oInfos, oParams);
    };

