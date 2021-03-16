<?php


namespace E2D;


use Core\ModuleMakerFactory;
use Core\ProjectType;

class E2DModuleMakerFactory extends ModuleMakerFactory
{
    protected $templateNodeClass;

    public function __construct(ProjectType $type, $arguments, $appDir)
    {
        $this->initializeComponents();
        $this->templateNodeClass = E2DTemplateNode::class;

        parent::__construct($type, $arguments, $appDir);
    }

//    public function initializeFileGenerators(App $app)
//    {
//        $app->modelFileGeneratorClass       = E2DModelFileGenerator::class;
//        $app->controllerFileGeneratorClass  = E2DControllerFileGenerator::class;
//        $app->viewFileGeneratorClass        = E2DViewFileGenerator::class;
//        $app->jSFileGeneratorClass          = E2DJSFileGenerator::class;
//        $app->configFileGeneratorClass      = E2DConfigFileGenerator::class;
//    }

    protected function initializeComponents()
    {
        $this->databaseAccess = E2DDatabaseAccess::class;
        $this->modelMaker = E2DModelMaker::class;
        $this->moduleMaker = E2DModuleMaker::class;
        $this->fieldClass = E2DField::class;
    }

    protected function display_logo()
    {
        return PHP_EOL.logo_blank_line().logo_border_line().logo_blank_line().

            '            .###*                     /(((                                      ' .PHP_EOL.
            '            .###*                     /(((                                      ' .PHP_EOL.
            '      ,*/////###*      .*/////*.      ****    ///*.*////,.        .*//////////  ' .PHP_EOL.
            '   (############*   .#############    /(((    ##############    (#############  ' .PHP_EOL.
            '  .###(     .###*   (###.     .###(   /(((    ####      *###*   ####      ####  ' .PHP_EOL.
            '  .###*     .###*   ####       ###(   /(((    ###(      .###*   ####      ####  ' .PHP_EOL.
            '  .###*     .###*   ####       ###(   /(((    ###(      .###*   ####      ####  ' .PHP_EOL.
            '   ###*     .###*   ####      .###(   /(((    ###(      .###*   ####      ####  ' .PHP_EOL.
            '   (############*   ,#############.   /(((    ###(      .###*   (#############  ' .PHP_EOL.
            '     ,/(((((((((*      /(((((((/      /(((    ((((       (((*     /(((((((####  ' .PHP_EOL.
            '                                                                   (##########  ' .PHP_EOL.
            '                                                                   (#########   ' .PHP_EOL.
            logo_blank_line().logo_border_line().logo_blank_line();
    }
}