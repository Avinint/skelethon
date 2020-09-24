    /**
     * Permet de créer le tinyMCE du textarea
     * @param sChamp
     * @return {void}
     */
    this.vDynamiseTinyMCE = function(sChamp)
    {
        tinymce.remove(sChamp);
        var oTinyMCE = new TinyMCE({
            aParamSelector: [
                {
                    szSelector: '#' + sChamp
                    ,nHeight: 147
                    ,sToolbar: 'undo redo | styleselect | forecolor | backcolor | bold italic underline | removeformat | bullist numlist'
                }
            ]
        });

        tinymce.editors['sChamp'].settings["language"] =  "fr_FR";
    };
