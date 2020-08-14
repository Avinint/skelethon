        if (oReponseJSON.bModif === true) {
            var oParamsConsultation = {
                aVariables: [oReponseJSON.oElement.nIdElement]
            };
            this.vExecuteAction('', 'mODULE', 'btn_ouverture_consultation_TABLE', oParamsConsultation);
        }
