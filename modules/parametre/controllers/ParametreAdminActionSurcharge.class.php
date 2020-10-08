<?php

namespace APP\Modules\Parametre\Controllers;
use APP\Modules\Parametre\Controllers\ParametreAdminAction as ParametreAdminAction;

class ParametreAdminActionSurcharge extends ParametreAdminAction
{

	/**
	 * Recherche d'éléments.
	 * 
	 * @return array Retour JSON.
	 */
	protected function aRechercheElements()
	{
		$_REQUEST["nNbElementsParPage"] = "9999999999";
		$aRetour = parent::aRechercheElements();
		return $aRetour;
	}
	
	/**
	 * Suppression d'un élément.
	 * 
	 * @param integer $nIdElement Id de l'élément.
	 * 
	 * @return array Retour JSON.
	 */
	protected function aSuppression($nIdElement = 0)
	{
		$aRetour = array(
			'bSucces' => false,
			'szErreur' => '',
		);

		$oElement = $this->oNew('Parametre', array($nIdElement));
		$nArchiveBdd = $oElement->nArchive;
		$oElement->nArchive = 1;
		$aRetour['bSucces'] = $oElement->bUpdate();

		if ($aRetour['bSucces'] === true) {
			$oLog = $this->oNew('Logs');
			$oLog->bLogArchivage('parametre', $nIdElement, $nArchiveBdd, 1);
		}

		return $aRetour;
	}
	
	/**
	 * Enregistrement d'un élément.
	 * 
	 * @param integer $nIdElement Id de l'élément.
	 * 
	 * @return array Retour JSON.
	 */
	protected function aEnregistreEdition($nIdElement = 0)
	{
		$aRetour = array(
			'bSucces' => false
		);

		$oElement = $this->oNew('Parametre', array($nIdElement));

		$oLog = $this->oNew('Logs');
		$oLog->bLogArchivage('parametre', $nIdElement, $oElement->nArchive, $_REQUEST['nArchive']);

		if (!$nIdElement) {
			$oElement->sType = $_REQUEST['sType'];
			$oElement->sCode = $_REQUEST['sCode'];
		}
		$oElement->sValeur = $_REQUEST['sValeur'];
		$oElement->nValeurNum = $_REQUEST['nValeurNum'];
		$oElement->sValeurTexte = $_REQUEST['sValeurTexte'];
		$oElement->sValeurTexteAutre = $_REQUEST['sValeurTexteAutre'];
		$oElement->dValeurDate = $_REQUEST['dValeurDate'];
		$oElement->nValeurBool = $_REQUEST['nValeurBool'];
		$oElement->nArchive = $_REQUEST['nArchive'];
		if ($nIdElement > 0) {
			$aRetour['bSucces'] = $oElement->bUpdate();
		} else {
			$aRetour['bSucces'] = $oElement->bInsert();
		}
		$aRetour['oElement'] = new \StdClass();
		$aRetour['oElement']->nIdElement = $nIdElement;

		return $aRetour;
	}
}