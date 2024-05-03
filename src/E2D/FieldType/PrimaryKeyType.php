<?php


namespace E2D\FieldType;


use Core\Field;

class PrimaryKeyType extends EnumType
{
    /**
     * Ajoute les lignes d champs formatés dans les selects pour récupérer des entités
     * @param string $indent
     * @param Field $field
     * @param array $template
     * @return string
     */
    public function addSelectFieldFormattedLines(string $indent, Field $field, array $template) : string
    {
        return $indent . str_replace(['ALIAS', 'COLUMN'],
                [$field->getAlias(), $field->getColumn()], $template[1]);
    }

    /**
     * @param string $indent
     * @param Field $field
     * @param array $template
     * @param array $criteresRecherch
     * @return string
     */
    public function getCritereDeRecherche(string $indent, Field $field,  array $template) : string
    {
        $texteCritere = $indent.implode('', array_map(function($line) use ($indent) {return $line.$indent;},
                [$template[7], $template[0], $template[11], $template[1], $template[2]]));

        return str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD'],
            [$field->getAlias(), $field->getColumn(), '=', $field->getName() . ''], $texteCritere);

    }

    public function getClasseMapping() : string
    {
        return "ClePrimaire";
    }
}