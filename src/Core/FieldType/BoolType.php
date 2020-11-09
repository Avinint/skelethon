<?php

namespace Core\FieldType;

use Core\Field;
use Core\FilePath;

class BoolType extends FieldType
{
    public function getEditionView(FilePath $path)
    {
        $suffix = $this->app->usesSwitches ? 'bool_switch' : 'bool_radio';
        $templatePath = $this->app->getTrueTemplatePath($path->add($suffix));

        return file_get_contents($templatePath);
    }

    /**
     * Ajoute les lignes d champs formatés dans les selects pour récupérer des entités
     * @param string $indent
     * @param Field $field
     * @param array $template
     * @return string
     */
    public function addSelectFieldFormattedLines(string $indent, Field $field, array $template) : string
    {
        return $indent . str_replace(['ALIAS', 'COLUMN', 'mODULE', 'MODEL', 'NAME'],
                [$field->getAlias(), $field->getColumn(), $this->module, $this->model ,$field->getFormattedName()], $template[6]);
    }

    /**
     * @param string $indent
     * @param Field $field
     * @param array $template
     * @return string
     */
    public function getCritereDeRecherche(string $indent, Field $field,  array $template) : string
    {
        $criteresRecherche = [];
        $criteresRecherche[] = str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD'],
            [$field->getAlias(), $field->getColumn(), '=', $field->getName() . ''],
            $indent . implode('', array_map(function($line) use ($indent) {return $line.$indent;},
                [$template[7].$template[0].$template[11].$template[1].$template[2]])));

        return implode(PHP_EOL, $criteresRecherche);
    }

    public function getValeurParDefautPourChampController(Field $field, array &$defaults, FilePath $fieldTemplatePath) : void
    {
        $defaultValue = $field->getDefaultValue() ?? 'nc';
        $defaultLines = file($this->app->getTrueTemplatePath($fieldTemplatePath->add('defaut')));
        $defaults[] = str_replace(['FIELD', 'VALUE'], [$field->getName(), $defaultValue], $defaultLines[0]);
    }

    public function getTemplateChampObligatoire($templatePath)
    {
        $template = file($templatePath, FILE_IGNORE_NEW_LINES);

        return [$template[0], $template[1]];
    }

}