<?php


namespace Core;

class ProjectType
{
    const FILENAME = 'config_type_template.yml';

    private string $type;
    private string $configName;

    public function __construct(string $type, $configName = null)
    {
        $this->type = $type;
        $this->configName = $configName;

        $configPath = dirname(__DIR__,  2). '/config/' . self::FILENAME;

        $this->templates = yaml_load($configPath)['templates'];
    }

    /**
     * Permet plusieurs fichiers configs pour le même type de projet. Permet de configurer plusieurs projets du même type
     * @return string
     */
    public function getConfigName()
    {
        return $this->configName ?? $this->type;
    }

    public function __toString()
    {
        return $this->classPrefix();
    }

    /**
     *  Génère le préfixe qui transforme un nom de classe abstraite en nom de classe concrète
     */
    public function classPrefix()
    {
        return ('\\'.strtoupper($this->type).'\\'.strtoupper($this->type));
    }

    public function getConcreteClassName($abstractClassName)
    {
        return $this->classPrefix().$abstractClassName;
    }


    public function getName()
    {
        return $this->type;
    }

    public function getTemplate()
    {
        return $this->templates;
    }

    public function setTemplate(string $template)
    {
        $this->template = $template;
    }
}