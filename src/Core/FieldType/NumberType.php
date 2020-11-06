<?php


namespace Core\FieldType;

use Core\Field;
use Core\FilePath;

class NumberType extends FieldType
{
    protected $templateIndex = 11;

    public function getEditionView(FilePath $path)
    {
        return file_get_contents($this->app->getTrueTemplatePath($path->add('number')));
    }

    /**
     * @param string $indent
     * @param Field $field
     * @param array $template
     * @param array $criteresRecherche
     * @return array
     */
    public function getCritereDeRecherche(string $indent, Field $field,  array $template, array $criteresRecherche) : array
    {
        $texteCritere = $indent.implode('', array_map(function($line) use ($indent) {return $line.$indent;},
                [$template[7], $template[0], $template[$this->templateIndex], $template[1], $template[2]]));

        foreach ([['>=', 'Min'], ['<=', 'Max'], ['=', '']] as [$operator, $suffix]) {
            $criteresRecherche[] = str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD'],
                [$field->getAlias(), $field->getColumn(), $operator, $field->getName().$suffix], $texteCritere);
        }

        return $criteresRecherche;
    }
}