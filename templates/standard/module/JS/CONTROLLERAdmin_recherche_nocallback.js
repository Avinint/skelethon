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
     * Callback exécutée à la suite de la dynamisation du formulaire de recherche.
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

        this.vChargeListe('', $('.liste_TABLE'));
        this.vChargeEvenementsChampsRecherche('TABLE', oParams);
    }
