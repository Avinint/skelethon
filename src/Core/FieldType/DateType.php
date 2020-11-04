<?php

namespace Core\FieldType;

use Core\Field;
use Core\FilePath;

class DateType extends FieldType
{
    public const SUFFIXE_DEBUT = '';
    public const SUFFIXE_FIN = '';
    public const FORMAT = 'Y-m-d';

    /**
     * @param FilePath $path
     * @return false|string
     */
    public function getEditionView(FilePath $path)
    {
        return file_get_contents($this->app->getTrueTemplatePath($path->add('date')));
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
                [$field->getAlias(), $field->getColumn(), $this->module, $this->model ,$field->getFormattedName()], $template[2]);
    }

    /**
     * @param string $indent
     * @param Field $field
     * @param array $template
     * @param array $criteresRecherche
     * @return array
     */
    private function getSearchCriterionDate(string $indent, Field $field, array $template, array $criteresRecherche): array
    {
//        if ($this->type === 'date') {
//            $sSuffixeDebut = '';
//            $sSuffixeFin = '';
//            $sFormat = 'Y-m-d';
//        } elseif ($this->type === 'datetime') {
//            $sSuffixeDebut = ' 00:00:00';
//            $sSuffixeFin = ' 23:59:59';
//            $sFormat = 'Y-m-d H:i:s';
//        } else {
//            $sSuffixeDebut = '';
//            $sSuffixeFin = '';
//            $sFormat = 'H:i:s';
//        }

        if ($this->type->getName === 'datetime') {
            $texteCritere = $indent . implode('', array_map(function ($line) use ($indent) {
                    return $line . $indent;
                },
                    [$template[6], ...array_slice($template, 3, 3), $template[0], $template[13], $template[1], $template[2]]));
        } else {
            $texteCritere = $indent . implode('', array_map(function ($line) use ($indent) {
                    return $line . $indent;
                },
                    [$template[6], $template[0], $template[13], $template[1], $template[2]]));
        }

        $aCritereRecherche[] = str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD', 'SUFFIXE', 'FORMAT'],
            [$this->alias, $this->column, '>=', $this->name . 'Debut', $this->type::SUFFIXE_DEBUT, $this->type::FORMAT], $texteCritere);
        $aCritereRecherche[] = str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD', 'SUFFIXE', 'FORMAT'],
            [$this->alias, $this->column, '<=', $this->name . 'Fin', $this->type::SUFFIXE_DEBUT, $this->type::FORMAT], $texteCritere);
        return $aCritereRecherche;
    }

    /**
     * @param string $indent
     * @param Field $field
     * @param array $template
     * @param array $criteresRecherche
     * @return array
     */
    public function getSearchCriterion(string $indent, Field $field, array $template, array $criteresRecherche): array
    {
        $texteCritere = $this->getSearchCriterionDateTemplate($indent, $template);

        foreach ([['>=', 'Debut', self::SUFFIXE_DEBUT], ['<=', 'Fin', self::SUFFIXE_FIN]] as [$operator, $suffixeChamp, $suffixeValeur]) {
            $criteresRecherche[] = str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD', 'SUFFIXE', 'FORMAT'],
                [$field->getAlias(), $field->getColumn(), $operator, $field->getName() . $suffixeChamp, $suffixeValeur, self::FORMAT], $texteCritere);
        }

        return $criteresRecherche;
    }

    protected function getSearchCriterionDateTemplate(string $indent, array $template)
    {
        return $indent . implode('', array_map(function ($line) use ($indent) { return $line . $indent;},
                [$template[6], $template[0], $template[13], $template[1], $template[2]]));
    }

    public function getRequiredFieldTemplate($templatePath)
    {
        $template = file($templatePath, FILE_IGNORE_NEW_LINES);
        return [$template[0], $template[2].$template[3]];
    }


//    public function addToExceptions($field, &$exceptions)
//    {
//        $exceptions['aDates'][] = $field->getName();
//    }
}