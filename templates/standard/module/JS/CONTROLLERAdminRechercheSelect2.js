        eFormulaire.find('.NAME').select2({ 'data': oReponseJSON.aSelects.NAME, 'allowClear': true, 'placeholder': ' '});
        if (oParams.bChargementPage === true) {
            for (const sProp in oReponseJSON.oSelectDefauts) {
                eFormulaire.find('.' + sProp).val(oReponseJSON.oSelectDefauts[sProp]).trigger('change');
            }
        }