<?php

namespace Core;

use ArrayAccess;
use Countable;
use http\Exception\InvalidArgumentException;
use \Spyc ;

class Config extends CommandLineToolShelf implements ArrayAccess, Countable
{

    private $path;
    private $data;
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
    public function setFileManager(?FileManager $fileManager): void
    {
        if ($fileManager === null) {
            $template = $this->askTemplate();
            $this->fileManager = new FileManager($template);
        } else {
            $this->fileManager = $fileManager;
        }
    }


    public function __construct($module, $model, FileManager $fileManager = null)
    {
        $this->module = $module;
        $this->currentModel = $model;
        $this->data = $this->getData();

        $this->setFileManager($fileManager);
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
            } else {
                if (isset($model)) {
                    $this->data['modules'][$this->module]['models'][$model][$field] = $value;
                } else {
                    $this->data['modules'][$this->module][$field] = $value;
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
            if (isset($model) && is_array($this->data['modules'][$this->module]['models'][$model][$field])) {
                $this->data['modules'][$this->module]['models'][$model][$field][$key] = $value;
                $this->write($this->module);
                return;
            } elseif (is_array($this->data['modules'][$this->module][$field] )) {
                $this->data['modules'][$this->module][$field][$key] = $value;
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
        $moduleData = $this->data['modules'][$module];

        if ($module) {
            $data = $moduleData;
            $path = str_replace('config', $module.'_config', $this->path);
        } else {
            $data = $this->data;
            unset($data['modules']);
            $path = $this->path;
        }

        $this->fileManager->createFile($path, Spyc::YAMLDump($data, 4, 40, true), true);
    }

    private function getPath($module = 'main')
    {
        return dirname(dirname(__DIR__)) .DS .($module !== 'main' ? $module. '_' : '').'config.yml';
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
        // Quand on set le model de façon explicite
        // celui ci prévaut sur l'ordre canonique : app / module / model
        if (isset($model)) {
            return $this->getValueFromModelConfig($field, $model);
        }

        $result = $this->data[$field] ?? $this->getValueFromModuleConfig($field) ?? $this->getValueFromModelConfig($field);
        if (isset($result)) {
            return $result;
        }

        return null;
    }

    /**
     * @param string $field
     * @return mixed|null
     */
    private function getValueFromModuleConfig(string $field)
    {
        if (isset($this->data['modules'][$this->module][$field])) {
            return $this->data['modules'][$this->module][$field];
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
        if (isset($currentModel) && isset($this->data['modules'][$this->module]['models'][$currentModel][$field])) {
            return $this->data['modules'][$this->module]['models'][$currentModel][$field];
        }

        return null;
    }

    public function has(string $field, $model = null)
    {

        $value = $this->getValueFromModelConfig($field, $model);



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
                unset($this->data['modules'][$this->module]['models'][$model][$field]);
            } else {
                unset($this->data['modules'][$this->module][$field]);
            }
            $this->write($this->module);
        }
    }

    protected function getData(): array
    {
        $this->path = $this->getPath();
        $modulePath = $this->getPath($this->module);

        $data = file_exists($this->path) ? Spyc::YAMLLoad($this->path) : [];
        $moduleData = file_exists($modulePath) ? Spyc::YAMLLoad($modulePath) : [];
        $data['modules'][$this->module] = $moduleData;

        return $data;
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


    protected function askTemplate()
    {
        $templates = array_map(function($tmpl) {$parts = explode(DS, $tmpl); return array_pop($parts); }, glob(dirname(dirname(__DIR__)) . DS . 'templates'.DS.'*', GLOB_ONLYDIR));
        $res =  $this->askConfig('template', $templates, 'askMultipleChoices', 'standard');
        return $res;
    }
}