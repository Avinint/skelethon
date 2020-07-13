<?php

use Core\Config;
use Core\DatabaseAccessInterface;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use org\bovigo\vfs\vfsStream,
    org\bovigo\vfs\vfsStreamDirectory;

class E2DModuleMakerTest extends MockeryTestCase
{
    private $modelName;
    private $moduleName;
    private $className;

    protected function setUp(): void
    {
        $this->modelName = $this->moduleName = 'animal';
        $this->className = 'Animal';

        $structure = array(
            'config' => array(
                'menu.yml' => '
admin:
    modeconstructif:
        html_accueil_modeconstructif:
            titre: Paramétrage modes constructifs
            icon: view_quilt

application:
    projet:
        html_accueil_projet:
            titre: Mes projets et chantiers
            icon: dashboard
                
                ',
                'conf.yml' => 'rien',
        ),
            'modules' => array(
                'projet' => array()
            )
        );

        $this->root = vfsStream::setup('root', 777, $structure);

    }

    public function testModuleMaker()
    {
        require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'functions.php';
        require_once dirname(__DIR__).DS.'lib'.DS.'Spyc'.DS.'Spyc.php';

        $this->assertTrue($this->root->hasChild('config/menu.yml'));
        $this->assertTrue($this->root->hasChild('config/conf.yml'));
        $this->assertEquals('rien', file_get_contents(vfsStream::url('root/config/conf.yml')));


        $config = new Config('main');
        $moduleConfig = new Config($this->moduleName);

        $dbAccess = Mockery::mock('alias:DatabaseAccessInterface');
//        $dbAccess->shouldReceive('aListeTables')
//        ->once()->andReturn([]);

        $model = Mockery::mock('E2DModelMaker');
//        $model->shouldReceive('setDbParams')
//        ->once();
//
//        $model->shouldReceive('setDbTable')
//            ->once();

//        $model->shouldReceive('generate')
//            ->once();

        $model->shouldReceive('getTitre')
            ->once()->andReturn('Mes ' . $this->modelName . 's');

        $model->shouldReceive('getName')->times(3)->andReturn($this->modelName);

//        $model->shouldReceive('getClassName')->once()->andReturn($this->className);


//        $model->shouldReceive('getViewFieldsByType')->times(3)->with('enum')->andReturn([
//                ['field' => 'sSpeciesFormat',
//                'column' => 'species',
//                'label' => 'Species',
//                'type' => 'enum',
//                'default' => 'cat',
//                'name' => 'Species',
//                'is_nullable' => false,
//                'enum' => ['dog','cat','rabbit','fish','parrot','guinea_pig']]
//        ]);

        $model->actions =['recherche', 'edition', 'suppression', 'consultation'];

//        $model->shouldReceive('getViewFields')->once()->andReturn([]);
//        $model->shouldReceive('getAlias')->once()->andReturn('a');
//        $model->shouldReceive('getPrimaryKey')->once()->andReturn('id_animal');
//        $model->shouldReceive('getIdField')->once()->andReturn('nIdAnimal');
//        $model->shouldReceive('getAttributes')->once()->andReturn('');

//        $model = new E2DModelMaker('animal', 'animal', 'generate', [
//            'config' => $config,
//            'moduleConfig' => $moduleConfig,
//        ]);

        $moduleMaker = new E2DModuleMaker('animal', $model, 'generate', [
            'config' => $config,
            'moduleConfig' => $moduleConfig,
            'menuPath' =>  vfsStream::url('root/config/menu.yml'),
            'modulePath' => vfsStream::url('root/modules/animal')
        ]);
        //$moduleMaker->generate();
    }
}