<?php

namespace APP\Modules\MODULE\Models;

use APP\Modules\Base\Lib\Bdd as Bdd;
use APP\Modules\Manu\Models\ManuMapping;

class MODEL extends Bdd
{
    /**
     *  Constructeur de la classe
     *
     * @param IntegerType $nIdElement Id de l'élément
     *
     * @return void
     */
    public function __construct($nIdElement = 0)
    {
        $this->aMappingChamps = new MODELMapping();

        parent::__construct($nIdElement);
    }

    
    /**
     * Requête de sélection.
     *
     * @param array       $aRecherche      Critères de recherche
     * @param string      $szOrderBy       Tri
     * @param boolean     $bModeCount      Juste compter.
     * @param IntegerType     $nStart      Numéro de départ du LIMIT.
     * @param IntegerType     $nNbElements Nombre d'éléments à récupérer.
     * @param string      $sGroupBy        Grouper les éléments par un certains champ.
     * @param IntegerType     $sContexte   Contexte d'appel de la requête (liste, consultation etc.).
     *
     * @return string                    Retourne la requête
     */
    public function szGetSelect($aRecherche = array(), $szOrderBy = '', $bModeCount = false, $nStart = 0, $nNbElements = 20, $sGroupBy = '', $sContexte = '')
    {
        if ($bModeCount === false) {
            $szChamps = CHAMPS_SELECT;
        } else {
            $szChamps = '
                COUNT(*) AS nNbElements
            ';
        }

        $sRequete = '
            SELECT *
            FROM
            (
                SELECT '.$szChamps.'
                FROM TABLE ALIASLEFTJOINS';
        if ($sContexte !== '' && in_array($sContexte, ['']) === true) {
            // Jointures à effectuer selon le contexte
            // passé en paramètres.
            $sRequete .= '
            ';
        }
        $sRequete .= '
                WHERE 1=1
        ';
        $sRequete .= $this->szGetCriteresRecherche($aRecherche);

        $sRequete .= '
            ) matable 
        ';
        if ($bModeCount === false) {
            if ($sGroupBy === '') {
                $sGroupBy = 'PK';
            }
            $sRequete .= ' GROUP BY '.$sGroupBy.' ';
            if ($szOrderBy === '') {
                $szOrderBy = 'PK DESC';
            }
            $sRequete .= ' ORDER BY '.$szOrderBy.' ';
        }
        //var_dump($sRequete);

        return $sRequete;
    }

    /**
     * Permet de récupérer les critères de validation du formulaire d'édition.
     *
     * @param  string   $szNomChamp Nom du champ.
     * @param  string   $szType     Type de retour (chaine ou tableau).
     *
     * @return string             Critères (chaine ou tableau).
     */
    public function aGetCriteres($szNomChamp = '', $szType = 'tableau')
    {
//VALIDATION
        if ($szType == 'tableau') {
            return $aConfig[$szNomChamp];
        } elseif ($szType == 'chaine') {
            if (isset($aConfig[$szNomChamp])) {
                return $this->szGetCriteresValidation($aConfig[$szNomChamp]);
            }
        }
    }
}