function CONTROLLER()
{
    if (szZoneCourante == 'admin') {
        CONTROLLERAdmin.apply(this, arguments);
    } else if (szZoneCourante == 'application') {
        CONTROLLERPrive.apply(this, arguments);
    } else if (szZoneCourante == 'site') {
        CONTROLLERPublic.apply(this, arguments);
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
            this.vChargementPage();
        }
    };
};