<?php

namespace ESM;

use E2D\E2DModelMaker;

class ESMModelMaker extends E2DModelMaker
{
    public function __construct($fieldClass, string $module, $name, $creationMode = 'generate', $app)
    {
        $app->getConfig()->askLegacy($name);
        parent::__construct($fieldClass, $module, $name, $creationMode, $app);

    }
}