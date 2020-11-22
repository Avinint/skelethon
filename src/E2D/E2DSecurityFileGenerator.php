<?php


namespace E2D;
use Core\App;
use Core\BaseMaker;
use Core\FilePath;
use \Spyc;

class E2DSecurityFileGenerator extends BaseMaker
{
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->module = $app->getModuleMaker()->getName();
        $this->controller = $app->getModuleMaker()->getControllerName('snake_case');
    }

    /**
     * @return array
     */
    private function generateSecurityDefinition($modes, $actions): array
    {
        $moduleDefinition = ['mode' => [], 'action' => []];
        foreach ($modes as $mode) {
            $moduleDefinition['mode'][$mode] = ['cle' => $this->urlize($mode), 'libelle' => $this->labelize($mode)];
        }
        foreach ($actions as $action) {
            $uniqueAction = $this->urlize($action. '-' . $this->controller);
            $moduleDefinition['action'][$action] = ['cle' => $uniqueAction, 'libelle' => $this->labelize($uniqueAction)];
        }

        return $moduleDefinition;
    }

    private function getModesAndActions()
    {
        $routingPath = getcwd() .DS. 'modules' .DS. $this->module .DS.'config'.DS.'routing.yml';
        $routing = Spyc::YAMLLoad($routingPath);
        $res = [[], []];
        foreach ($routing as $route) {
            if (isset($route['route']['mode']) && $route['route']['controller'] === $this->controller) {
                $res[0][] = $route['route']['mode'];
            } elseif (isset($route['route']['action']) && $route['route']['controller'] === $this->controller) {
                $res[1][] = $route['route']['action'];
            }
        }

        return $res;
    }

    /**
     */
    public function generate(FilePath $path): void
    {
        $this->updateSecurityData($path);

        $security = Spyc::YAMLDump($this->security, false, 0, true);

        $this->fileManager->createFile($path, $security, true);
    }


    public function print($path) : void
    {
        [$securityDefinitions, $newRules] = $this->updateSecurityData($path);

        $text = PHP_EOL . '==== SECURITE ====' . PHP_EOL . PHP_EOL .
            Spyc::YAMLDump([$this->controller => $securityDefinitions[$this->controller]], false, 0, true) . PHP_EOL .
            Spyc::YAMLDump(['admin' => $newRules], false, 0, true);
        echo($text);
    }

    /**
     * @return array
     */
    private function updateSecurityData($path): array
    {
        $this->security = Spyc::YAMLLoad($path);
        if (empty($this->security)) {
            $this->security = ['aRessources' => ['zone' => ['admin' => ['module' => []]]], 'aRestrictionsAcces' => ['admin' => []]];
        }

        $securityDefinitions = &$this->security['aRessources']['zone']['admin']['module'];


        if (!isset($securityDefinitions[$this->module])) {
            $securityDefinitions[$this->module] = ['controller' => []];
        }

        if (isset($securityDefinitions[$this->module]['controller'][$this->controller])) {
            unset($securityDefinitions[$this->module]['controller'][$this->controller]);
        }

        [$modes, $actions] = $this->getModesAndActions();

        $securityDefinitions[$this->module]['controller'][$this->controller] = $this->generateSecurityDefinition($modes, $actions);

        $droitAdmin = $this->app->get('droit-admin') ?: 'admin';
        $securityRulesAdmin = &$this->security['aRestrictionsAcces'][$droitAdmin];

        $newRules = array_merge(array_map([$this, 'urlize'], $modes)
            , array_map(function ($action) {
                return $this->urlize($action.  '-' . $this->controller);
            }, $actions));

        $securityRules = array_merge($securityRulesAdmin, $newRules);

        return [$securityDefinitions, $newRules];
    }
}