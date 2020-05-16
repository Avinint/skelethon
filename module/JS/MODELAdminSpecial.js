
    /**
     * Rafraichissement de la liste.
     *
     * @param object oReponseJSON   Infos JSON récupérées lors de l'appel Ajax.
     * @param object oParams        Paramètres passés avant l'appel Ajax.
     *
     * @return void
     */
    this.vRefreshListe = function(oReponseJSON, oParams) {
        oParams.sClasseListe = 'TABLE';
        this.vRefreshListeEtFermeCalque(oParams);
    };
