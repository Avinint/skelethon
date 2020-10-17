<?php


namespace ESM;


use Core\App;
use Core\ModuleMakerFactory;
use Core\ProjectType;

class ESMModuleMakerFactory extends ModuleMakerFactory
{
    public function __construct(ProjectType $type, $arguments, $appDir)
    {
        $this->databaseAccess = ESMDatabaseAccess::class;
        $this->modelMaker = ESMModelMaker::class;
        $this->moduleMaker = ESMModuleMaker::class;
        $this->fieldClass = ESMField::class;



        parent::__construct($type, $arguments, $appDir);
    }

    public function initializeFileGenerators(App $app)
    {
        $app->modelFileGeneratorClass       = ESMModelFileGenerator::class;
        $app->controllerFileGeneratorClass  = ESMControllerFileGenerator::class;
        $app->viewFileGeneratorClass        = ESMViewFileGenerator::class;
        $app->jSFileGeneratorClass          = ESMJSFileGenerator::class;
        $app->configFileGeneratorClass      = ESMConfigFileGenerator::class;
    }
}