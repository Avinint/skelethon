<?php
require_once  __DIR__."/../lib/Spyc/Spyc.php";

use Core\Config;

class ConfigTest extends \Mockery\Adapter\Phpunit\MockeryTestCase
{
    private $modelName;
    private $moduleName;
    private $className;
    private $config;

    protected function setUp(): void
    {
        $this->modelName = $this->moduleName = 'prof';
        $this->className = 'Prof';
        $this->config = new TestableConfig($this->moduleName);
    }

    public function tearDown(): void
    {
        unset($this->config);
        unset($this->modelName);
        unset($this->moduleName);
        unset($this->className);
    }

    public function testGetConfigValueFromModel()
    {
        $this->config->setCurrentModel('prof');
        $this->assertEquals('enseignant', $this->config->get('tableName'));
    }

    public function testNotGetConfigValueFromModuleIfAppConfigValueExists()
    {
        $this->config->setCurrentModel('prof');
        $this->assertEquals('esm', $this->config->get('template'));
    }

    public function testSetModelValue()
    {
        $this->config->setCurrentModel('prof');

        $this->config->set('actions', ['accueil', 'recherche', 'edition', 'suppression'], 'prof');

        var_dump($this->config->get('actions'));
    }
}

class TestableConfig extends Config
{
    protected function getData(): array
    {
        return [
            'updateMenu' => true,
            'usesSelect' => true,
            'memorizeChoices' => true,
            'template' => 'esm',
            'modules' =>
                [
                    'prof' =>
                        [
                            'models' =>
                                [
                                    'prof' =>
                                        [
                                            'tableName' => 'enseignant',
                                            'actions' =>
                                                [
                                                    0 => 'accueil',
                                                    1 => 'recherche',
                                                    2 => 'edition',
                                                    3 => 'suppression',
                                                    4 => 'consultation',
                                                ],
                                            'usesMulti' => false,
                                            'usesSelect2' => true,
                                            'usesSwitches' => true,
                                        ],
                                ],
                            'template' => 'standard',
                        ],
                ],
        ];
    }
}