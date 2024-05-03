<?php

class CodeWriter
{
    public function generateClass($module, $name, $templatePatb)
    {
        $class = new ClassWriter(
            'Projet',
            'APP\Modules\Assurance\Models',
            'APP\Modules\Base\Lib\Bdd',
        );

        $construct = $class->addMethod('__construct');
        $construct->addBody('parent::__construct();');

    }
}

$codeWriter = new CodeWriter('assurance', 'Projet');

