<?php


namespace Core;


use APP\Modules\Contact\Controllers\ModeleMailAdminAction;

class App
{
    private FileManager $fileManager;
    private Config $config;
    private DatabaseAccess $databaseAccess;
    private ModuleMaker $moduleMaker;
    private $modeleMaker;

    public function setProjectPath()
    {
        $this->projectPath = new Path(getcwd(), 'projectPath');
        $this->getFileManager()->setProjectPath($this->projectPath);
    }

    /**
     * @return FileManager
     */
    public function getFileManager() : FileManager
    {
        return $this->fileManager;
    }

    /**
     * @param FileManager|null $fileManager
     */
    public function setFileManager(?string $templateNodeClass =  null, ?FileManager $fileManager = null): void
    {
        if ($fileManager === null) {
            $this->fileManager = new FileManager($this, $templateNodeClass);
        } else {
            $this->fileManager = $fileManager;
        }

        //        if (!empty($this->data)) {
        //            $this->write($this->module);
        //        }

        $this->getFileManager()->ensureConfigFileExists();
        $this->config->setFileManager($this->getFileManager());
    }

    public function setTemplates(?string $template = null)
    {
        $this->templates ??= $template;
    }


    /**
     * @return DatabaseAccess
     */
    public function getDatabaseAccess() : DatabaseAccess
    {
        return $this->databaseAccess;
    }

    /**
     * @param DatabaseAccess $databaseAccess
     */
    public function setDatabaseAccess(DatabaseAccess $databaseAccess) : void
    {
        $this->databaseAccess = $databaseAccess;
    }

    /**
     * @return ModuleMaker
     */
    public function getModuleMaker() : ModuleMaker
    {
        return $this->moduleMaker;
    }

    /**
     * @param ModuleMaker $moduleMaker
     */
    public function setModuleMaker(ModuleMaker $moduleMaker) : void
    {
        $this->moduleMaker = $moduleMaker;
    }

    /**
     * @return ModelMaker
     */
    public function getModelMaker() : ModelMaker
    {
        return $this->modeleMaker;
    }

    /**
     * @param $modeleMaker
     */
    public function setModeleMaker(ModelMaker   $modeleMaker) : void
    {
        $this->modeleMaker = $modeleMaker;
    }

    /**
     * @return Config
     */
    public function getConfig() : Config
    {
        return $this->config;
    }

    /**
     * @param Config $config
     */
    public function setConfig(Config $config) : void
    {
        $this->config = $config;
    }

    public function get($property, $model = null)
    {
        return $this->getConfig()->get($property, $model);
    }

    public function has($property) : bool
    {
        return $this->getConfig()->has($property);
    }

    public function set($property, $value = null, $model = null, $setForAll = false) : void
    {
       $this->getConfig()->set($property, $value, $model, $setForAll);
    }

    public function getTemplate()
    {
        return $this->getFileManager()->getTemplate();
    }

    public function getProjectPath()
    {
        return $this->getFileManager()->getProjectPath();
    }

    public function getTrueTemplatePath(FilePath $path)
    {
        return $this->getFileManager()->getTrueTemplatePath($path);
    }

    public function getModuleName()
    {
        return $this->getModelMaker()->getModule();
    }

    public function getModelName()
    {
        return $this->getModelMaker()->getName();
    }


}