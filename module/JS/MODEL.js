function MODEL()
{
    // La classe hérite du fichier JS de la zone courante.
    if (szZoneCourante == 'site') {
        MODELPublic.apply(this, arguments);
    } else if (szZoneCourante == 'application') {
        MODELPrive.apply(this, arguments);
    } else if (szZoneCourante == 'admin') {
        MODELAdmin.apply(this, arguments);
    }

    var oThis = this;

    /**
     * Document Ready
     * Tout ce qui est ajouté ici sera automatiquement appelé au chargement.
     *
     * @return {void}
     */
    this.vInit = function()
    {
        if (szZoneCourante == 'admin') {
            // this.vChargeEvenementsBoutons();

            oMODELAdmin = new MODELAdmin();
            oMODELAdmin.vInit();
        }
    };
};