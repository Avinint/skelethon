<?php

namespace E2D\FieldType;

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
    private function getCritereDeRechercheDate(string $indent, Field $field, array $template, array $criteresRecherche): array
    {
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
     * @return string
     */
    public function getCritereDeRecherche(string $indent, Field $field, array $template): string
    {
        $texteCritere = $this->getCritereDeRechercheDateTemplate($indent, $template);

        foreach ([['>=', 'Debut', static::SUFFIXE_DEBUT], ['<=', 'Fin', static::SUFFIXE_FIN]] as [$operator, $suffixeChamp, $suffixeValeur]) {
            $criteresRecherche[] = str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD', 'SUFFIXE', 'FORMAT'],
                [$field->getAlias(), $field->getColumn(), $operator, $field->getName() . $suffixeChamp, $suffixeValeur, static::FORMAT], $texteCritere);
        }

        return implode(PHP_EOL, $criteresRecherche);
    }

    protected function getCritereDeRechercheDateTemplate(string $indent, array $template)
    {
        return $indent . implode('', array_map(function ($line) use ($indent) { return $line . $indent;},
                [$template[6], $template[0], $template[13], $template[1], $template[2]]));
    }

    public function getTemplateChampObligatoire($templatePath)
    {
        $template = file($templatePath, FILE_IGNORE_NEW_LINES);
        return [$template[0], $template[2].$template[3]];
    }

    /**
     * Permet le récupérer le chemin du template du champ pour la vue recherche
     * @param $path
     * @return mixed
     */
    protected function getCheminTemplateVueRecherche($path)
    {
        return $this->app->getTrueTemplatePath($path->add('date'));
    }
}