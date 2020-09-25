<?php

namespace Eto;

use Core\FileManager;
use E2D\E2DModuleMaker;

class EtoModuleMaker extends E2DModuleMaker
{
    /**
     * @param mixed $fileManager
     */
    public function setFileManager(?FileManager $fileManager, $template = 'standard'): void
    {
        $this->fileManager = $fileManager ?? $this->config->getFileManager($template);

    }

    protected function initializeFileGenerators($params)
    {
        $modelFileGenerator       = $params['modelFileGenerator']      ?? EtoModelFileGenerator::class;
        $controllerFileGenerator  = $params['controllerFileGenerator'] ?? EtoControllerFileGenerator::class;
        $viewFileGenerator        = $params['viewFileGenerator']       ?? EtoViewFileGenerator::class;
        $jSFileGenerator          = $params['jSFileGenerator']         ?? EtoJSFileGenerator::class;
        $configFileGenerator      = $params['ConfigFileGenerator']     ?? EtoConfigFileGenerator::class;

        $this->modelFileGenerator      = new $modelFileGenerator($this->name, $this->model, $this->config);
        $this->controllerFileGenerator = new $controllerFileGenerator($this->name, $this->namespaceName,$this->model, $this->getControllerName(), $this->config);
        $this->jsFileGenerator         = new $jSFileGenerator($this->name, $this->namespaceName, $this->model, $this->getControllerName(), $this->config);
        $this->configFileGenerator     = new $configFileGenerator($this->name, $this->namespaceName,$this->model, $this->getControllerName(), $this->config);
        $this->viewFileGenerator       = new $viewFileGenerator($this->name, $this->model, $this->getControllerName(), $this->config);
    }
}