<?php

namespace APP\Modules\MODULE\Controllers;
use APP\Core\Lib\Interne\PHP\UndeadBrain;

class MODELAdminAction extends UndeadBrain
{
    /**
     * Constructeur de la classe
     *
     * @param  string $szAction Action à effectuer
     *
     * @return  void
     */
    public function __construct($szAction = '')
    {
       // On regarde si du contenu est disponible en cache.".PHP_EOL.
        $szContenuEnCache = $this->szGetContenuEnCache();

        if ($szContenuEnCache != '') {

        // Si du contenu est disponible en cache, on le renvoie
            echo $szContenuEnCache;

        } else {

            // Si aucun contenu n'est en cache, on traite l'action demandée.
            $nIdElement = 0;
            if (isset($_REQUEST['nIdElement']) === true) {
                $nIdElement = $_REQUEST['nIdElement'];
            }

            switch ($szAction) {

                case 'recherche':
                    $aRetour = $this->aRechercheElements();
                    break;
//CASE
            }

            $szRetour = json_encode($aRetour);

            echo $szRetour;

            // Sauvegarde du contenu dans le cache.
            $this->vSauvegardeContenuEnCache($szRetour);
        }
    }

    /**
     * Recherche d'éléments
     *
     * @return array Retour JSON
     */
    private function aRechercheElements()
    {
        $aRetour = array(
            'aElements' => array(),
        );

        $aRecherche = array();
        foreach ($_REQUEST as $sCle => $sValeur) {
            if (substr($sCle, -3) == 'Rch') {
                $aRecherche[str_replace('Rch', '', $sCle)] = $sValeur;
            }
        }

        $oElement = $this->oNew('MODEL');

        $nNbElementsParPage = $_REQUEST['nNbElementsParPage'];
        //$oPagination = new \StdClass();
        $oPagination = $this->oGetInfosPagination($oElement, $aRecherche, $nNbElementsParPage);
        $aRetour['aPagination'] = $oPagination;

        if (isset($_REQUEST['szOrderBy']) === true && $_REQUEST['szOrderBy'] != '') {
            $aRetour['aElements'] = $oElement->aGetElements($aRecherche, $oPagination->nStart, $nNbElementsParPage, $_REQUEST['szOrderBy']);
        } else {
            $aRetour['aElements'] = $oElement->aGetElements($aRecherche, $oPagination->nStart, $nNbElementsParPage);
        }

        return $aRetour;
    }

//METHOD
}