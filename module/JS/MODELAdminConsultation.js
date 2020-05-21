
    /**
     * Dynamisation de la consultation lors de l'ouverture du calque.
     *
     * @param object oReponseJSON   Infos JSON récupérées lors de l'appel Ajax.
     * @param object oParams        Paramètres passés avant l'appel Ajax.
     *
     * @return void
     */
    this.vDynamisationConsultationJSON = function (oReponseJSON, oParams) {
        var nIdElement = 0;
        if (typeof oReponseJSON.oElement != 'undefined') {
            if (typeof oReponseJSON.oElement.nIdElement != 'undefined') {
                nIdElement = oReponseJSON.oElement.nIdElement;
            }
        }

        var szIdCalque = 'modal_calque_consultation_TABLE';

        // Création du clone de calque.
        var oModal = new Modal(szIdCalque, nIdElement, oReponseJSON);

        if (nIdElement > 0) {
            $.each(oReponseJSON.oElement, function(sNomChamp, sValeur) {
                oModal.eModal.find('.'+sNomChamp).html(sValeur);
            });
        }

        oModal.eModal.find('.btn_action').addClass('variable_1_' + nIdElement);
        oModal.eModal.find('.btn_supp').attr('id', 'btn_suppression_' + nIdElement);
        this.vChargeEvenementsBoutonsLigne();

        // Ouverture et stockage de l'instance de calque.
        oModal.oOpenModal();
        aInstancesCalques[szIdCalque] = oModal;
    };
