<?php

namespace Eto;

use E2D\E2DDatabaseAccess;

class EtoDatabaseAccess extends E2DDatabaseAccess
{
    public static function getDatabaseParams()
    {
        return new static(
            'localhost',
            'adminsql',
            'doing42',
            'etotem-dev'
        );
    }
}