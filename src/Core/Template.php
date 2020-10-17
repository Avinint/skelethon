<?php

namespace Core;

class Template
{
    private          $type;
    private PathNode $path;

    public function __construct($pathText)
    {
        $this->path = new PathNode($pathText);
        $this->type = $this->getType($pathText);
    }

    private function getType($pathText)
    {

    }

}

/**
 * GetTrueTemplate($path, $subtemplate)
 *
 *
 * $object template = new Template($path)
 *
 *
 * $templatePath = getTrueTemplatePath($path, subPath)
 *
 * file_get_content = getTemplate(new Path)
 *
 * $path
 * $type FileType
 *
 *
 *
 */