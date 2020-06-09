        var eSelectMODEL = FORM.find('.NAME');
        this.vTransformeSelectMODEL(eSelectMODEL, ALLOWCLEAR);
        if (nIdElement > 0) {
            var option = new Option(oReponseJSON.oElement.FIELD, oReponseJSON.oElement.NAME, true, true);
            eSelectMODEL.append(option);
        }
