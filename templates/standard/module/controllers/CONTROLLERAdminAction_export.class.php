    /**
     * Récupère les filtres du formulaire de recherche
     * @return array
     */
    private function aRecupererCriteresRecherche() : array
    {
        $aRecherche = [];
        foreach ($_REQUEST as $sCle => $sValeur) {
            if (substr($sCle, -3) == 'Rch') {
                $aRecherche[str_replace('Rch', '', $sCle)] = $sValeur;
            }
        }

        return $aRecherche;
    }

    /**
     * Exporter tableau au format Excel.
     *
     * @return array Informations.
     */
    private function vExporter()
    {
        $aRecherche = $this->aRecupererCriteresRecherche();

        $szOrderBy = $_REQUEST['szOrderBy'] ?? '';

        $this->vGenererExportExcel($aRecherche, $szOrderBy, 'Export LABEL', 'TABLE');
    }

    /**
     * @param array $aRecherche
     * @param string $szOrderBy
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    private function vGenererExportExcel(array $aRecherche, string $szOrderBy, string $szTitre, string $szNomFichier) : void
    {
        require $GLOBALS['oUtiles']->szGetFichierPourInclusion('lib_externe_php', 'PHPExcel.php');

        // Create new PHPExcel object
        $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_to_sqlite3;
        if (\PHPExcel_Settings::setCacheStorageMethod($cacheMethod)) {
            error_log(' Enable Cell Caching using ' . $cacheMethod . ' method');
        } else {
            error_log(' Unable to set Cell Caching using ' . $cacheMethod . ' method, reverting to memory');
        }

        $objPHPExcel = new \PHPExcel();
        // Set document properties
        $objPHPExcel->getProperties()->setCreator('E-TOTEM SPV')
            ->setLastModifiedBy('E-TOTEM SPV')
            ->setTitle('E-TOTEM SPV - ')
            ->setSubject("E-TOTEM SPV - {$szTitre}")
            ->setDescription("E-TOTEM SPV - {$szTitre}")
            ->setKeywords("E-TOTEM SPV - {$szTitre}");

        $this->oNew('Pdc')->bPopulatePHPExcel_Worksheet($objPHPExcel->setActiveSheetIndex(0), $aRecherche, 0, '', $szOrderBy);

        $objPHPExcel->getActiveSheet()->freezePane('A2');

        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename={$szNomFichier}_" . date('Y_m_d_H_i_s') . ".xlsx");
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');              // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate');               // HTTP/1.1
        header('Pragma: public');                                      // HTTP/1.0

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }