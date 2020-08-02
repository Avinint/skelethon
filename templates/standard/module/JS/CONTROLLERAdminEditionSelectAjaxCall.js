        var eSelectMODEL = oModal.eModal.find('.NAME');
        this.vTransformeSelectMODEL(eSelectMODEL, ALLOWCLEAR);
        if (nIdElement > 0 && oReponseJSON.oElement.NAME !== null) {
            var newOption = new Option(oReponseJSON.oElement.LABELFIELD, oReponseJSON.oElement.NAME, true, true);
            oModal.eModal.find('.NAME').append(newOption).trigger('change');
        }