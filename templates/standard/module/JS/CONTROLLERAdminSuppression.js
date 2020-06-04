
    /**
     * Rafraichissement de la liste.
     *
     * @param object oReponseJSON   Infos JSON récupérées lors de l'appel Ajax.
     * @param object oParams        Paramètres passés avant l'appel Ajax.
     *
     * @return void
     */
    this.vFermeConsultationEtRefreshListe = function(oReponseJSON, oParams) {
        this.vChargeListe('', $('.liste_TABLE'));
        vFermeCalque('modal_calque_consultation_TABLE');
    };
