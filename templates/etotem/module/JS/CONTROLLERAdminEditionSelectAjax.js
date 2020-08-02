this.vTransformeSelectMODEL(elCalque.find('.IDFIELD'));
if (oReponseJSON.oElement.IDFIELD !== null) {
    var newOption = new Option(oReponseJSON.oElement.LABELFIELD, oReponseJSON.oElement.IDFIELD, true, true);
    elCalque.eModal.find('.IDFIELD').append(newOption).trigger('change');
}