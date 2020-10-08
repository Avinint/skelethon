<?php

namespace APP\Modules\Parametre\Models;
use APP\Modules\Parametre\Models\Parametre as Parametre;

class ParametreSurcharge extends Parametre
{

	/**
	 * Récupération des éléments pour les mettre dans les select de manière uniformisée
	 *
	 * @param string $sType
	 *
	 * @return array données formatées pour une utilisation directe
	 */
	public function aGetSelectMenu($sType = "", $aNomChamp = array("valeur", "libelle"))
	{
		$aRetour = array();
		if ($sType) {
			$sRequete = "SELECT PAR.`code` AS ".$aNomChamp[0].", IF(IFNULL(PAR.`valeur`, '') != '', PAR.`valeur`, IF(IFNULL(PAR.`valeur_texte`, '') != '', PAR.`valeur_texte`,  PAR.`valeur_num`)) AS ".$aNomChamp[1].""
				." FROM parametre PAR "
				." WHERE PAR.`type`='".addslashes($sType)."' AND archive=0 "
				." ORDER BY PAR.`valeur_num`, PAR.`valeur`";
			$aRetour = $this->aSelectBDD($sRequete);
			// echo $sRequete."\r\n";
		}
		return $aRetour;
	}
}