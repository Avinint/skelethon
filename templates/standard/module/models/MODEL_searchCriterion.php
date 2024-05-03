    $sRequete .= "
    ";
}
    if (!preg_match('/:/', $aRecherche['FIELD']) && !preg_match('/h/', $aRecherche['FIELD'])) {
        $aRecherche['FIELD'] .= 'SUFFIXE';
    }
if (isset($aRecherche['FIELD']) && $aRecherche['FIELD'] !== '') {
if (isset($aRecherche['FIELD']) && $aRecherche['FIELD'] > 0) {
if (isset($aRecherche['FIELD']) && $aRecherche['FIELD'] != 'nc') {
        AND ALIAS.COLUMN = '".addslashes($aRecherche['FIELD'])."'
        AND ALIAS.COLUMN LIKE '%".addslashes($aRecherche['FIELD'])."%'
        AND ALIAS.COLUMN OPERATOR ".addslashes($aRecherche['FIELD'])."
        AND ALIAS.COLUMN OPERATOR ".str_replace(',', '.', addslashes($aRecherche['FIELD']))."
        AND ALIAS.COLUMN OPERATOR '".addslashes($this->sGetDateFormatUniversel($aRecherche['FIELD'], 'FORMAT'))."'
