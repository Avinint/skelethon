        $oElement->NAME = $_REQUEST['NAME'];
        $oElement->NAME = empty($_REQUEST['NAME']) ? 'NULL' : '\'' . addslashes($this->sGetDateFormatUniversel($_REQUEST['NAME'], 'Y-m-d')) . '\'';
        $oElement->NAME = empty($_REQUEST['NAME']) ? 'NULL' : '\'' . addslashes($this->sGetDateFormatUniversel($_REQUEST['NAME'], 'Y-m-d h:i:s')) . '\'';
        $oElement->NAME = empty($_REQUEST['NAME']) ? 'NULL' : '\'' . str_replace('h', ':', $_REQUEST['NAME']) . '\'';
        $oElement->NAME = str_replace(',', '.', $_REQUEST['FIELD']);
        $oElement->NAME = $_REQUEST['NAME'] === '' ? 'NULL' : $_REQUEST['NAME'];
