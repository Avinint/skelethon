<?php


namespace Core;

class ProjectType
{
    const TEMPLATES = ['eto' => 'etotem', 'esm' => 'esm', 'e2d' => 'standard'];

    private string $type;
    private string $configName;

    public function __construct(string $type, $configName = null)
    {
        $this->type = $type;
        $this->configName = $configName;
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
        return ('\\'.strtoupper($this->type).'\\'.strtoupper($this->type));
    }

    public function getName()
    {
        return $this->type;
    }

    public function getTemplate()
    {
        return self::TEMPLATES[$this->type];
    }

    public function setTemplate(string $template)
    {
        $this->template = $template;
    }
}