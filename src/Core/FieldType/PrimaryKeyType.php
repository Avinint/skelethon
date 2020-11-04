<?php


namespace Core\FieldType;


use Core\Field;

class PrimaryKeyType extends IntegerType
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
     * @param array $criteresRecherche
     * @return array
     */
    public function getSearchCriterion(string $indent, Field $field,  array $template, array $criteresRecherche) : array
    {
        $texteCritere = $indent.implode('', array_map(function($line) use ($indent) {return $line.$indent;},
                [$template[7], $template[0], $template[11], $template[1], $template[2]]));
        $criteresRecherche[] = str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD'],
            [$field->getAlias(), $field->getColumn(), '=', $field->getName() . ''], $texteCritere);

        return $criteresRecherche;
    }
}