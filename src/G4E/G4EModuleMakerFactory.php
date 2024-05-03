<?php


namespace G4E;

use E2D\E2DModelMaker;
use E2D\E2DModuleMaker;
use E2D\E2DModuleMakerFactory;


class G4EModuleMakerFactory extends E2DModuleMakerFactory
{

    protected function initializeComponents()
    {
        $this->databaseAccess = G4EDatabaseAccess::class;
        $this->modelMaker = G4EModelMaker::class;
        $this->moduleMaker = E2DModuleMaker::class;
        $this->fieldClass = G4EField::class;
    }
}