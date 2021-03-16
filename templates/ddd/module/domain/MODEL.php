<?php


namespace APP\Modules\MODULE\Domain;


class MODEL
{
    //PROPRIETES

    public function __construct()
    {
        //INITIALISATIONS
        $this->nIdAgent = 0;
        $this->sCode                  = '';
        $this->oLieuTravail           = new \StdClass();
        $this->oNumeroSecuriteSociale = new \StdClass();
        $this->oCollectivite          = new \StdClass();
        $this->oAdresse               = new \StdClass();
        $this->oRib                   = new \StdClass();
        $this->nMoisAdhesion          = 0;
        $this->nAnneeAdhesion         = 0000;
        $this->sNumero                = '';
        $this->sMatricule             = '';
        $this->sRegime                = '';
        $this->sCivilite              = '';
        $this->sNomNaissance          = '';
        $this->sNomUsage              = '';
        $this->sPrenom                = '';
        $this->sEmail                 = '';
        $this->sSituationFamiliale    = '';
        $this->sLienAvecAutreAgent    = '';
        $this->sLieuNaissance         = '';
        $this->dDateNaissance         = '0000-00-00';
        $this->sStatut                = '';
        $this->sCategorieEmploi       = '';
        $this->sPositionStatutaire    = '';
        $this->sPositionStatutaireFormate    = '';
        $this->dDateRetraite          = '0000-00-00';
        $this->sCaisseRetraite        = '';
    }
}