<?php


namespace Eto;


use Core\App;
use Core\ModuleMakerFactory;
use Core\ProjectType;

class EtoModuleMakerFactory extends ModuleMakerFactory
{
    public function __construct(ProjectType $type, $arguments, $appDir)
    {
        $this->databaseAccess = E2DDatabaseAccess::class;
        $this->modelMaker = E2DModelMaker::class;
        $this->moduleMaker = E2DModuleMaker::class;
        $this->fieldClass = E2DField::class;

        parent::__construct($type, $arguments, $appDir);
    }

    public function initializeFileGenerators(App $app)
    {
        $app->modelFileGeneratorClass       = EtoModelFileGenerator::class;
        $app->controllerFileGeneratorClass  = EtoControllerFileGenerator::class;
        $app->viewFileGeneratorClass        = EtoViewFileGenerator::class;
        $app->jSFileGeneratorClass          = EtoJSFileGenerator::class;
        $app->configFileGeneratorClass      = EtoConfigFileGenerator::class;
    }
}