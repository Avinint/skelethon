SKELETHON rework :

questions :

- Renseigner le prefix du projet ou laisser vide (prefix)

- Si le nom de la table en base est différent de session_formation entrer le nom de la table : (nom_table)

- Voulez vous sélectionner toutes les actions disponibles? (recherche, edition, suppression, consultation, export) [O/N] (actions)

- Voulez vous sélectionner l'action "recherche" ? [O/N] (action_recherche)

- Voulez vous sélectionner l'action "edition" ? [O/N] (action_edition)

- Voulez vous sélectionner l'action "suppression" ? [O/N] (action_suppression)

- Voulez vous sélectionner l'action "consultation" ? [O/N] (action_consultation)

- Voulez vous sélectionner l'action "export" ? [O/N] (action_export)

- Voulez-vous pouvoir ouvrir plusieurs calques en même temps ? (multi/concurrent) [O/N] (multi_calque)

- Voulez-vous utiliser les Select2 pour générer les champs Enum ? [O/N] (select2)

- Voulez-vous pouvoir générer des champs switch plutôt que radio pour les booléens ? (switch/radio) [O/N] (switch)

- Voulez-vous un template qui utilise callbackListeElement) [O/N] callback_liste

- Voulez-vous un template qui utilise un callback pour personnaliser les lignes de liste (callbackListeLigne) ? [O/N] (callback_ligne)

- Souhaitez vous utiliser la pagination ? [O/N] (pagination)

- Voulez vous sélectionner quels champs seront utilisés dans chaque vue ou action ? [O/N] [champs]

- Voulez vous sélectionner 'xxxx' pour toutes les vues, pour certaines vues, ou jamais? [1/2/3] champ_vues_toutes

- Voulez vous sélectionner 'xxxx' pour la vue 'yyyy'? champ_vues_yyyy

- Voulez vous transformer le champ "xxxx" en champs tinyMCE ? [O/N] champ_xxxx_wysiwyg

- Voulez-vous transformer des champs en selects Ajax ? [O/N] (selects_ajax)

Voulez-vous convertir tous les champs suivants :
xxxx
yyyy
en Select Ajax ? [O/N] (select_ajax_tous)

Voulez-vous convertir le champ xxxx en Select Ajax ? [O/N] select_ajax_xxxx

Voulez vous transformer certains champs en paramètre? [O/N] (champs_parametre)


# Liste avec emplacements:

ModelMaker ::
askTableName (construct / setDbTable)
askPrefix :
- Renseigner le prefix du projet ou laisser vide (prefix)

        - Si le nom de la table en base est différent de session_formation entrer le nom de la table : (nom_table)


    askActions (construct)
        - Voulez vous sélectionner toutes les actions disponibles? (recherche, edition, suppression, consultation, export) [O/N] (actions)
        - Voulez vous sélectionner l'action "recherche" ? [O/N] (action_recherche)
        - Voulez vous sélectionner l'action "edition" ? [O/N] (action_edition)
        - Voulez vous sélectionner l'action "suppression" ? [O/N] (action_suppression)
        - Voulez vous sélectionner l'action "consultation" ? [O/N] (action_consultation)
        - Voulez vous sélectionner l'action "export" ? [O/N] (action_export)


E2DModelMaker ::
askMulti  (construct / askSpecificsPreData)
- Voulez-vous pouvoir ouvrir plusieurs calques en même temps ? (multi/concurrent) [O/N] (multi_calque)

    askSelect2
        - Voulez-vous utiliser les Select2 pour générer les champs Enum ? [O/N] (select2)

    askSwitches
        - Voulez-vous pouvoir générer des champs switch plutôt que radio pour les booléens ? (switch/radio) [O/N] (switch)

    askCallbackListe
        - Voulez-vous un template qui utilise callbackListeElement) [O/N] callback_liste

    askCallbackListeLigne
        - Voulez-vous un template qui utilise un callback pour personnaliser les lignes de liste (callbackListeLigne) ? [O/N] (callback_ligne)

    askPagination
        - Souhaitez vous utiliser la pagination ? [O/N] (pagination)

# generate : (ModelMaker), : récupère les champs de la table et lance le hook pour lancer le code qui a besoin de ces données

    askFieldsPerView (recupereDonnees)
        - Voulez vous sélectionner quels champs seront utilisés dans chaque vue ou action ? [O/N] [champs]

Field ::
handleQuestionsAboutViews (recupereDonnees / askFieldsPerView / askViews)
- Voulez vous sélectionner 'xxxx' pour toutes les vues, pour certaines vues, ou jamais? [1/2/3] champ_vues_toutes


    askEachView
        - Voulez vous sélectionner 'xxxx' pour la vue 'yyyy'? champ_vues_yyyy


# spécifiques post-data : Code spécifique au projet et exxecutable après que les données aient été récupérées (recupereDonnees)

E2DModelMaker ::
askChampsTinyMCE (recupereDonnees / askSpecificsPostData)
- Voulez vous transformer le champ "xxxx" en champs tinyMCE ? [O/N] champ_xxxx_wysiwyg

E2DManyToOneMaker ::
askAddManyToOneField (recupereDonnees / askSpecificsPostData)
- Voulez-vous transformer des champs en selects Ajax ? [O/N] (selects_ajax)


    askConvertToManyToOneFields (recupereDonnees / askSpecificsPostData / askAddManyToOneField)
        - Voulez-vous convertir tous les champs suivants :
            xxxx
            yyyy
            en Select Ajax ? [O/N] (select_ajax_tous)

        - Voulez-vous convertir le champ xxxx en Select Ajax ? [O/N] select_ajax_xxxx

E2DModelMaker ::
askChampsParametres (recupereDonnees / askSpecificsPostData)
- Voulez vous transformer certains champs en paramètre? [O/N] (champs_parametre)
