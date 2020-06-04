<?php

namespace APP\Modules\MODULE\Controllers;

use APP\Core\Lib\Interne\PHP\AffichageHTML;

class CONTROLLERAdminHTML extends AffichageHTML
{
    /**
     * Récupère le contenu central de la page
     * 
     * @return string $sContenu Contenu HTML
     */
    public function szGetContenuCentralHTML()
    {
        $sContenu = parent::szGetContenuCentralHTML();

        if ($this->bAccueilModule === true) {
            $sFichierContenu = $this->szGetFichierPourInclusion('modules', 'mODULE/vues/liste_TABLE.html');
            $oContenu = $this->oGetVue($sFichierContenu);
            
            $this->objQpModele->find('#zone_navigation_3')->html($oContenu->find('body')->html());

            //RECHERCHE
        } else {
            return $sContenu;
        }
    }
}
