
    /**
     * Dynamisation de l'édition lors de l'ouverture du calque.
     *
     * @param object oReponseJSON   Infos JSON récupérées lors de l'appel Ajax.
     * @param object oParams        Paramètres passés avant l'appel Ajax.
     *
     * @return void
     */
    this.vDynamisationEditionJSON = function (oReponseJSON, oParams) {
        var nIdElement = 0;
        if (typeof oReponseJSON.oElement != 'undefined' && typeof oReponseJSON.oElement.nIdElement != 'undefined') {
            nIdElement = oReponseJSON.oElement.nIdElement;
        }
        oParams.szIdCalque = 'modal_calque_edition_TABLE'/*MULTI*/;
        var oModal = new Modal(oParams.szIdCalque, nIdElement, oReponseJSON);

        $('#modal_calque_edition_TABLE .action_module_btn_enregistre_edition_TABLE').addClass('variable_1_'+nIdElement);

        oParams.oModal = oModal;
        oParams.szIdFormulaireCharge = 'formulaire_edition_TABLE'/*MULTI*/;
        var oCallback = this.oGetFonctionCallback(this, this.vOuvreEdition, oParams);
        this.vChargeFormulaireData(oReponseJSON, oParams, oCallback);
    };

    /**
     * Ouverture du calque d'édition.
     *
     * @param object oReponseJSON   Infos JSON récupérées lors de l'appel Ajax.
     * @param object oParams        Paramètres passés avant l'appel Ajax.
     *
     * @return void
     */
    this.vOuvreEdition = function(oReponseJSON, oParams)
    {
        // Ouverture et stockage de l'instance de calque.
        oParams.oModal.oOpenModal();
        aInstancesCalques[oParams.szIdCalque] = oParams.oModal;
    };

    /**
     * Rafraichissement de la liste et fermeture du calque d'édition.
     *
     * @param object oReponseJSON   Infos JSON récupérées lors de l'appel Ajax.
     * @param object oParams        Paramètres passés avant l'appel Ajax.
     *
     * @return void
     */
    this.vFermeEditionEtRefreshListe = function(oReponseJSON, oParams)
    {
        var nIdElement = 0;
        if (typeof oReponseJSON.oElement != 'undefined' && typeof oReponseJSON.oElement.nIdElement != 'undefined') {
            nIdElement = oReponseJSON.oElement.nIdElement;
        }

        $('.btn_form_consultation.variable_1_' + nIdElement).trigger('click');
        this.vRefreshListe({}, {});
        vFermeCalque('modal_calque_edition_TABLE');
    };
