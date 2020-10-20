
    /**
     * Création de Select2
     * permettant de rechercher via un appel Ajax.
     *
     * @param {element} eSelect Element du DOM à dynamiser
     * @param {boolean} bAllowClear
     * @return {void}
     */
    this.vTransformeSelectMODEL = function(eSelect, bAllowClear = true)
    {
        var oParamsSelect = {
            eSelect2: eSelect,
            nMinimumLength: 0,
            aChamps: ['PK', 'LABEL'],
            sTable: 'TABLE',
            sOrderBy: 'LABEL',
            bAllowClear: bAllowClear,
            // sRestriction: ''
            // sModuleRoute: 'base',
            // sRoute: 'json_load_select_research',
            // sSousMode: '',
        };

        // On instancie le select pour qu'il devienne Select2
        // et accepte la recherche via appel Ajax.
        this.aGetSelect2JSONResearch(oParamsSelect);
    };