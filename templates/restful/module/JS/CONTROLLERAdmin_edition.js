
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
        oParams.szIdCalque = 'modal_calque_edition_TABLE';
        var oModal = new Modal(oParams.szIdCalque, nIdElement, oReponseJSON);

        oParams.eFormulaire = oModal.eModal.find('form');
        this.vChargeFormulaireData(oReponseJSON, oParams);
        oModal.eModal.find('.IDFIELD').val(nIdElement);

        if (nIdElement > 0) {
            oModal.eModal.find('.btn_enregistre_TABLE').addClass('action_mODULE_btn_modification_TABLE');
        } else {
            oModal.eModal.find('.btn_enregistre_TABLE').addClass('action_mODULE_btn_creation_TABLE');
        }SELECT2EDIT
        oModal.oOpenModal();
        aInstancesCalques[oParams.szIdCalque] = oModal;

TINYMCE
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
    {CLOSECONSULTATIONMODAL
        this.vChargeListe('', $('.liste_TABLE'));
        vFermeCalque('modal_calque_edition_TABLE');
    };

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

TINYMCEDEF
