
    /**
     * Création de Select2
     * permettant de rechercher via un appel Ajax.
     *
     * @param  {element} eSelect Elément du DOM représentant le select visé.
     *
     * @return {void}
     */
    this.vTransformeSelectMODEL = function(eSelect, bAllowClear)
    {
        var aChamps = ['PK', 'TITRE'];

        var oParamsSelect = {
            eSelect2: eSelect,
            nMinimumLength: 3,
            aChamps: aChamps,
            sTable: 'TABLE',
            sOrderBy: 'ORDERBY',
            bAllowClear: bAllowClear,
            // sModuleRoute: 'base',
            // sRoute: 'json_load_select_research',
            // sSousMode: '',
            // sRestriction: ''
        }
        this.aGetSelect2JSONResearch(oParamsSelect);
    };