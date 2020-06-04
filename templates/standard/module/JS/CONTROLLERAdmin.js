function CONTROLLERAdmin()
{
    Recherche.apply(this, arguments);

    var oThis = this;

/*ACTION*/
    /**
     * Callback appelée pour charger les boutons de chaque ligne de la liste.
     *
     * @param  {object} oElement Infos de la ligne.
     * @param  {object} oLigne   Ligne du tableau (DOM).
     *
     * @return {void}
     */
    this.vCallbackLigneListe = function (oReponseJSON, oLigne, szClasse) {
        var nIdElement = 0;
        if (typeof oReponseJSON.id_element != 'undefined') {
            nIdElement = oReponseJSON.id_element;
        }

        if (typeof oReponseJSON.nIdElement != 'undefined') {
            nIdElement = oReponseJSON.nIdElement;
        }

        $('.btn_form_consultation', oLigne).attr('id', 'btn_edition_' + nIdElement);
        $('.btn_form_consultation', oLigne).addClass('variable_1_' + nIdElement);

        return oLigne;
    };
};