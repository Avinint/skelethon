<?php


namespace E2D\FieldType;

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
     * @return string
     */
    public function getCritereDeRecherche(string $indent, Field $field,  array $template) : string
    {
        $texteCritere = $indent.implode('', array_map(function($line) use ($indent) {return $line.$indent;},
                [$template[7], $template[0], $template[$this->templateIndex], $template[1], $template[2]]));

        foreach ([['>=', 'Min'], ['<=', 'Max'], ['=', '']] as [$operator, $suffix]) {
            $criteresRecherche[] = str_replace(['ALIAS', 'COLUMN', 'OPERATOR', 'FIELD'],
                [$field->getAlias(), $field->getColumn(), $operator, $field->getName().$suffix], $texteCritere);
        }

        return implode(PHP_EOL, $criteresRecherche);
    }

    /**
     * Permet le récupérer le chemin du template du champ pour la vue recherche
     * @param $path
     * @return mixed
     */
    protected function getCheminTemplateVueRecherche($path)
    {
        return $this->app->getTrueTemplatePath($path->add('number'));
    }

    //////// LEGACY !!!!!!!!!!!!!!!!!!!!!!!

    /**
     * @param FilePath $templatePath
     * @return array
     */
    public function getTemplateChampNullableLegacy(FilePath $templatePath)
    {
        $template = file($templatePath, FILE_IGNORE_NEW_LINES);
        return $template[5];
    }

    public function getClasseMapping() : string
    {
        return "Nombre";
    }


}