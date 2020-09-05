<?php

namespace APP\Modules\MODULE\Models;

use APP\Modules\Base\Lib\Bdd as Bdd;

class MODEL extends Bdd
{
    protected function sGetNomChampId()
    {
        return 'IDFIELD';
    }
    /**
     *  Constructeur de la classe
     *
     * @param integer $nIdElement Id de l'élément
     *
     * @return void
     */
    public function __construct($nIdElement = 0)
    {
        parent::__construct();

        $this->sNomTable = 'TABLE';
        $this->sNomCle = 'PK';

        $this->aMappingChamps = array(
//MAPPINGCHAMPS
        );
//TITRELIBELLE
        $this->vInitialiseProprietes($nIdElement);
    }

    /**
     * @param $nIdElement
     */
    private function vInitialiseProprietes($nIdElement)
    {
        if ($nIdElement > 0) {
            $aRecherche = array($this->sGetNomChampId() => $nIdElement);
            $aElements = $this->aGetElements($aRecherche);
            if (isset($aElements[0]) === true) {
                foreach ($aElements[0] as $szCle => $szValeur) {
                    $this->$szCle = $szValeur;
                }
            }
        }
    }
    
    /**
     * Requête de sélection.
     *
     * @param array       $aRecherche     Critères de recherche
     * @param string      $szOrderBy      Tri
     * @param boolean     $bModeCount     Juste compter.
     * @param integer     $nStart         Numéro de départ du LIMIT.
     * @param integer     $nNbElements    Nombre d'éléments à récupérer.
     * @param string      $sGroupBy       Grouper les éléments par un certains champ.
     * @param integer     $sContexte      Contexte d'appel de la requête (liste, consultation etc.).
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
     * Méthode permettant de compléter une requête avec des critères
     *
     * @param array $aRecherche Critères de recherche
     *
     * @return string           Retourne le SQL des critères de recherche
     */
    protected function szGetCriteresRecherche($aRecherche = array())
    {
        $sRequete = '';

//RECHERCHE
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

    /**
     * Insertion d'un élément.
     *
     * @param  array  $aChamps Champs concernés par l'édition.
     *
     * @return void
     */
    public function bInsert($aChamps = array(), $aChampsNull = [])
    {
    $bRetour = false;

    $sRequete = '
        INSERT INTO TABLE
        SET '.$this->sFormateChampsRequeteEdition($aChamps);

    // echo \"<pre>$sRequete</pre>\";
    // exit;

    $rLien = $this->rConnexion->query($sRequete);
    $this->IDFIELD = $this->rConnexion->lastInsertId();
    if ($rLien) {
        $bRetour = true;
        //$this->bSetLog('insert_TABLE', $this->IDFIELD);
    } else {
        $this->sMessagePDO = $this->rConnexion->sMessagePDO;
    }
    
        return $bRetour;
    }

    /**
    * Mise à jour d'un élément.
    *
    * @param  array   Champs concernés par l'édition.
    *
    * @return void
    */
    public function bUpdate($aChamps = array(), $aChampsNull = [])
    {
        $bRetour = false;

        $sRequete = '
            UPDATE TABLE SET 
            '.$this->sFormateChampsRequeteEdition($aChamps).' 
            WHERE PK = '.$this->IDFIELD;

        // echo "<pre>$sRequete</pre>";
        // exit;

        $rLien = $this->rConnexion->query($sRequete);
        if ($rLien) {
            $bRetour = true;
            //$this->bSetLog('update_TABLE', $this->IDFIELD);
        } else {
            $this->sMessagePDO = $this->rConnexion->sMessagePDO;
        }

        return $bRetour;
    }

    /**
    * Suppression d'un élément.
    *
    * @return void
    */
    public function bDelete()
    {
        $bRetour = false;

        $sRequete = '
            DELETE 
            FROM TABLE
            WHERE PK = '.$this->IDFIELD;

        // echo "<pre>$sRequete</pre>";
        // exit;

        $rLien = $this->rConnexion->query($sRequete);

        if ($rLien) {
            $bRetour = true;
            //$this->bSetLog('delete_TABLE', $this->IDFIELD);
        } else {
            $this->sMessagePDO = $this->rConnexion->sMessagePDO;
        }

        return $bRetour;
    }
}