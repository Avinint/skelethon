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
    this.vCallbackLigneListe = function (oReponseJSON, eLigne, szClasse) {
        var nIdElement = 0;
        if (typeof oReponseJSON.nIdElement != 'undefined') {
            nIdElement = oReponseJSON.nIdElement;
        }

        /*PERSONALIZEBUTTONS*/

        return eLigne;
    };SELECTAJAX
};