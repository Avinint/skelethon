    /**
     * Permet de peupler un objet PHPExcel_Worksheet.
     *
     * @param  object  $oPHPExcel_Worksheet   Objet PHPExcel_Worksheet a peupler
     * @param  array   $aRecherche  Critères de recherche
     * @param  integer $nStart      Numéro de début.
     * @param  integer $nNbElements Nombre de résultats.
     * @param  string  $szOrderBy   Ordre de tri.
     * @param  string  $szGroupBy   Groupé par tel champ.
     *
     * @return boolean
     */
    public function bPopulatePHPExcel_Worksheet($oPHPExcel_Worksheet, $aRecherche = array(), $nStart = 0, $nNbElements = '', $szOrderBy = '')
    {
        if(!is_object($oPHPExcel_Worksheet) || (get_class($oPHPExcel_Worksheet) != 'PHPExcel_Worksheet')) {
            return false;
        }

        $szRequete = $this->szGetSelect($aRecherche, $szOrderBy, false, 0, 0, '', '');

        if ($nNbElements && $nNbElements != 0) {
            $szRequete .= ' LIMIT '.$nStart.', '.$nNbElements;
        }

        $aChamps = $this->szGetParametreModule('mODULE', 'aChampExport-MODEL');
        //error_log('requete = '.$szRequete);

        $nLigne = 1;
        $nColonne = 0;

        foreach ($aChamps as $szKey => $szValue) {
            $oPHPExcel_Worksheet->setCellValue(\PHPExcel_Cell::stringFromColumnIndex($nColonne).$nLigne, $szValue);
            $nColonne++;
        }

        try {
            $rLien = $this->rConnexion->query($szRequete);
            // error_log(var_export($rLien, true));

            if($rLien) {
                foreach($rLien as $aLigne) {
                    $nColonne = 0;
                    $nLigne++;
                    foreach ($aChamps as $szKey => $szValue) {
                        $oPHPExcel_Worksheet->setCellValue(\PHPExcel_Cell::stringFromColumnIndex($nColonne++).$nLigne, $aLigne[$szKey] ?? $aLigne[$this->sGetColonne($szKey)]);
                    }
                }
            }

        }
        catch(PDOException $e) {
            error_log('Erreur BDD : ' . $e->getMessage());
            return false;
        }

        return true;
    }