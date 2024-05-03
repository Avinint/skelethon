<?php

namespace APP\Modules\MODULE\Controllers;
use APP\Core\Lib\Interne\PHP\UndeadBrain;

class CONTROLLERAdminAction extends UndeadBrain
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

            $IDFIELD = $_REQUEST['IDFIELD'] ?? '';

            switch ($szAction) {
//CASE
            }
//MULTI
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
        $aRetour = INIT;

        $aRecherche = array();
        foreach ($_REQUEST as $sCle => $sValeur) {
            if (substr($sCle, -3) == 'Rch') {
                $aRecherche[str_replace('Rch', '', $sCle)] = $sValeur;
            }
        }

        $oElement = $this->oNew('MODEL');
//PAGINATION

        return $aRetour;
    }

//METHOD
}