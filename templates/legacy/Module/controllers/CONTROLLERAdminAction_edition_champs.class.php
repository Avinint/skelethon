$oElement->NAME = $_REQUEST['NAME'];
$oElement->NAME = str_replace(',', '.', $_REQUEST['FIELD']);
$oElement->NAME = '\'' . addslashes($this->sGetDateFormatUniversel($_REQUEST['NAME'], 'FORMAT')) . '\'';
$oElement->NAME = '\'' . str_replace('h', ':', $_REQUEST['NAME']) . '\'';
$oElement->NAME = $_REQUEST['NAME'] ?: 'NULL';
$oElement->NAME = $_REQUEST['NAME'] === '' ? 'NULL' : $_REQUEST['NAME'];
$oElement->NAME = $_REQUEST['FIELD'] === '' ? 'NULL' : str_replace(',', '.', $_REQUEST['FIELD']);
$oElement->NAME = empty($_REQUEST['NAME']) ? 'NULL' : '\'' . addslashes($this->sGetDateFormatUniversel($_REQUEST['NAME'], 'FORMAT')) . '\'';
$oElement->NAME = empty($_REQUEST['NAME']) ? 'NULL' : '\'' . str_replace('h', ':', $_REQUEST['NAME']) . '\'';
Y-m-d
Y-m-d h:i:s