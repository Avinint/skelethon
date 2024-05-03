<?php

namespace APP\Modules\MODULE\Models;

use APP\Modules\Base\Lib\Champ\Oracle\{CLASSES};
use APP\Modules\Base\Lib\Mapping;

class MODELMapping extends Mapping
{
    protected $sNomTable = 'TABLE';
    protected $sNomSequence = 'SEQUENCE';
    protected $sAlias = 'ALIAS';

    public $sNomChampId = 'IDFIELD';
    public $sNomCle = 'PK';
    public $sNomChampLibelle = 'LIBELLE';

    public function __construct()
    {
        $aMapping = [
CHAMPS
        ];

        parent::__construct($aMapping);
    }
}