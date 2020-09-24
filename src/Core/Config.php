<?php

namespace Core;

use ArrayAccess;
use Countable;
use http\Exception\InvalidArgumentException;
use \Spyc ;
use function PHPUnit\Framework\directoryExists;

class Config extends CommandLineToolShelf implements ArrayAccess, Countable
{
    private $type;
    private $module;
    private $path;
    private array $data;
    private $currentModel;
    protected $fileManager;

    /**
     * @return mixed
     */
    public function getFileManager()
    {
        return $this->fileManager;
    }

    /**
     * @param FileManager|null $fileManager
     */
    public function setFileManager(?string $template = null, ?FileManager $fileManager = null): void
    {
        if ($fileManager === null) {
            $this->template = $template ?? $this->get('template', $this->currentModel);
            $this->fileManager = new FileManager($template);

        } else {
            $this->fileManager = $fileManager;
        }

        $this->ensureDirectoryExists(dirname($this->getPath('for_project')));

        if (!file_exists($this->getPath('for_project'))) {
            $this->fileManager->createFile($this->getPath('for_project', Spyc::YAMLDump([], 4, 40, true), true));
        }

        $this->set('template', $template,  'for_project');
    }

    public function setTemplate(string $template)
    {
        $this->set('template', $template,  'for_project');
    }

    public function __construct($module, $model, ProjectType $type)
    {
        $this->module = $module;
        $this->currentModel = $model;
        $this->type = $type;
        $this->subDir = $this->type->getConfigName();
        $this->path = $this->getPath();
    }

    /**
     * @param string $currentModel
     */
    public function setCurrentModel(string $currentModel): void
    {
        $this->currentModel = $currentModel;
    }

//    public function getFields(string $fieldName)
//    {
//        if (isset($this->data['']))
//    }

    public function getFromModel($model, string $field)
    {
        if (isset($this->data['models'][$model][$field])) {
            return $this->data['models'][$model][$field];
        }
        return null ;
    }

    /**
     * @param string $field
     * @param null $value
     */
    public function set($field, $value = null, $model = null, $setForAll = false)
    {
        if (isset($value)) {
            if ($setForAll) {
                $this->data[$field] = $value;
                $this->write();
            } elseif (isset($model) && $model === 'for_project') {
                $this->data[$this->subDir][$field] = $value;
                $this->write('for_project');
            } else {
                if (isset($model)) {
                    $this->data[$this->subDir]['modules'][$this->module]['models'][$model][$field] = $value;
                } else {
                    $this->data[$this->subDir]['modules'][$this->module][$field] = $value;
                }

                $this->write($this->module);
            }
        } else {
            $this->unsetParam($setForAll, $field, $model);
        }
    }

    /**
     * @param $field
     * @param $value
     * @param null $model
     * @param bool $setForAll
     */
    public function addTo($field, $key, $value, $model  = null, $setForAll = false): void
    {

        if ($setForAll && is_array($this->data[$field])) {
            $this->data[$field][] = $value;
            return;
        } else {
            if (isset($model) && is_array($this->data[$this->subDir]['modules'][$this->module]['models'][$model][$field])) {
                $this->data[$this->subDir]['modules'][$this->module]['models'][$model][$field][$key] = $value;
                $this->write($this->module);
                return;
            } elseif (is_array($this->data[$this->subDir]['modules'][$this->module][$field] )) {
                $this->data[$this->subDir]['modules'][$this->module][$field][$key] = $value;
                $this->write($this->module);
                return;
            }
        }
        throw new InvalidArgumentException("Le parametre de configuration selectionné doit être un tableau");
    }


//    public function setForModel($model, $field = '', $value = null)
//    {
//        if (isset($value)) {
//            if (!isset($this->data['models'][$model])) {
//                $this->data['models'][$model] = [];
//            }
//
//            $this->data['models'][$model][$field] = $value;
//        }
//
//        $this->write();
//    }

//    public static function create($module = 'main')
//    {
//        if (!isset(static::$configs[$module])) {
//            static::$configs[$module] = new static($module);
//        }
//
//        return static::$configs[$module];
//    }

    public function write($module = '')
    {
        if ($module === 'for_project') {
            $path = $this->getPath('for_project');
            $data = $this->data[$this->subDir];
            unset($data['modules']);

        } elseif ($module) {
            if (!isset($this->data[$this->subDir]['modules'])) {
                $this->data[$this->subDir]['modules'] = [$module => []];
            }
            $moduleData = $this->data[$this->subDir]['modules'][$module];
            $data = $moduleData;

            $path = $this->getPath($module);
        } else {
            $data = $this->data;
            unset($data[$this->subDir]);
            $path = $this->path;

        }

        $this->fileManager->createFile($path, Spyc::YAMLDump($data, 4, 40, true), true);
    }

    /**
     * @param string $name app, project ou nom de module spécifique
     * @return string
     */
    private function getPath($name = 'for_app')
    {
        if ($name === 'for_app') {
            return dirname(dirname(__DIR__)) . DS . 'config' . DS . 'config.yml';
        } elseif ($name === 'for_project') {
            return dirname(dirname(__DIR__)) . DS . 'config' . DS . $this->type->getConfigName() . DS . 'config.yml';
        } else {
            return dirname(dirname(__DIR__)) . DS . 'config' . DS . $this->type->getConfigName() . DS . $name . '_config.yml';
        }
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {

        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset = null) {
        if ($offset === null) {
            return $this->data;
        }
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function count()
    {
        return count($this->data);
    }

    /**
     * @param string $field
     * @return array|mixed|null
     */
    public function get(string $field, string $model = null)
    {
        //var_dump($this->data[$field] );
        // Quand on set le model de façon explicite
        // celui ci prévaut sur l'ordre canonique : app / module / model
        if (isset($model)) {
            return $this->getValueFromModelConfig($field, $model);
        }

        $result = $this->data[$this->type->getName()][$field] ?? $this->getValueFromModuleConfig($field) ?? $this->getValueFromModelConfig($field) ?? $this->data[$field] ?? null;
        return $result;
    }

    /**
     * @param string $field
     * @return mixed|null
     */
    private function getValueFromModuleConfig(string $field)
    {
        if (isset($this->data[$this->subDir]['modules'][$this->module][$field])) {
            return $this->data[$this->subDir]['modules'][$this->module][$field];
        }

        return $this->getValueFromModelConfig($field);
    }

    /**
     * @param string $field
     * @return mixed|null
     */
    private function getValueFromModelConfig(string $field, $model = null)
    {
        $currentModel = $model ?? $this->currentModel;
        if (isset($currentModel) && isset($this->data[$this->subDir]['modules'][$this->module]['models'][$currentModel][$field])) {
            return $this->data[$this->subDir]['modules'][$this->module]['models'][$currentModel][$field];
        }

        return null;
    }

    public function has(string $field, $model = null)
    {
        return $this->get($field, $model) !== null;
    }

    /**
     * @param bool $setForAll
     * @param string $field
     * @param $model
     */
    private function unsetParam(bool $setForAll, string $field, $model): void
    {
        if ($setForAll) {
            unset($this->data[$field]);
            $this->write();
        } else {
            if (isset($model)) {
                unset($this->data[$this->subDir]['modules'][$this->module]['models'][$model][$field]);
            } else {
                unset($this->data[$this->subDir]['modules'][$this->module][$field]);
            }
            $this->write($this->module);
        }
    }



    /**
     * L'application donne des choix aux utilisateurs, les réponses sont stockées en config,
     * permet a l'application de ne pas redemander une information déja stockée
     *
     * @param string $key
     * @param array $choices
     * @param $function
     * @param bool $defaultValue
     * @param bool $multiple
     * @return mixed
     * @throws \Exception
     */
    protected function askConfig(string $key, array $choices, $function, $defaultValue = false, $model = '')
    {
        if (count($choices) === 1) {
            return $choices[0];
        } elseif (count($choices) > 1) {
            if ($this->has($key) && array_contains($this->get($key), $choices)) {
                $selection = $this->get($key);
            } else {
                $selection = $this->$function($key, $choices, $defaultValue);

                $this->saveChoice($key, $selection, $model);
            }

            return $selection;
        } else {
            throw new \Exception("Pas de $key disponible");
        }
    }

    /**
     * @param string $key
     * @param $selection
     * @param string $model
     */
   public function saveChoice(string $key, $selection, string $model = ''): void
    {
        if (empty($model)) {
            $applyChoiceToAllModules = $this->get('memorizeChoices') ?? $this->prompt('Voulez vous appliquer ce choix à tous les modules créés à l\'avenir?', ['o', 'n']) === 'o';
            if ($applyChoiceToAllModules) {
                $this->set($key, $selection, '', true);
            }
            $this->set($key, $selection);
        } else {
            $this->set($key, $selection, $model);
        }
    }

    public function askLegacy($model)
    {
        if ($this->has('legacy', $model)) {
            return $this->get('legacy', $model);
        } else {
            $hasLegacyCode = $this->prompt('Voulez vous générer du code legacy ?', ['o', 'n']) === 'o';
            $this->set('legacy', $hasLegacyCode, $model);
            return $this->get('legacy');
        }
    }

    public function askTemplate()
    {
        $templates = array_map(function($tmpl) {$parts = explode(DS, $tmpl); return array_pop($parts); }, glob(dirname(dirname(__DIR__)) . DS . 'templates'.DS.'*', GLOB_ONLYDIR));
        $res = $this->askConfig('template', $templates, 'askMultipleChoices', $this->type->getTemplate());
        return $res;
    }

//    public function initializeData()
//    {
//        $this->getData();
//
//
//    }
//
//

    public function initialize(): void
    {
        $projectPath = $this->getPath('for_project');
        $modulePath = $this->getPath($this->module);

        $data = file_exists($this->path) ? Spyc::YAMLLoad($this->path) : [];
        $projectData = file_exists($projectPath) ? Spyc::YAMLLoad($projectPath) : [];
        $moduleData = file_exists($modulePath) ? Spyc::YAMLLoad($modulePath) : [];
//        if (!isset($this->data[$this->subDir]['modules'])) {
//            $this->data[$this->subDir]['modules'] = [$this->module => []];
//        }
        $data[$this->subDir] = $projectData;
        $data[$this->subDir]['modules'] = [$this->module => $moduleData];

        $this->data = $data;
    }

    private function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            if (!mkdir($path) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
        }
    }
}