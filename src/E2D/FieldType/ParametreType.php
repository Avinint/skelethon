<?php


namespace E2D\FieldType;


use Core\Field;
use Core\FilePath;

class ParametreType extends EnumType
{
    /**
     * Récupère le template pour générer le champ en mode dynamisation édition
     * @param $templatePath
     * @return mixed
     */
    public function getControllerTemplateChamp($templatePath)
    {
        return file($this->app->getTrueTemplatePath($templatePath->add('parametre')->add($this->enumType)), FILE_IGNORE_NEW_LINES);
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
         return $indent . str_replace(['ALIAS', 'FIELD', 'NAME', 'TYPE'],
                [strtoupper(substr($field->getColumn(), 0, 3)), $field->getFormattedName(), $field->getFormattedName(), $field->getParametre('type')], $template[8]);
    }


    /**
     * Génère les lignes des controllers générer les selects
     * en peupLant
     * @param $field
     * @param $templatePath
     * @return string
     */
    public function getChampsPourDynamisationEdition($field, $templatePath) : string
    {
        $template = $this->getControllerTemplateChamp($templatePath);

        $res =  str_replace(['NAME', 'TYPE'],
            [$field->getName(), $field->getParametre('type')], $template[0]);

        return $res;

    }

    /**
     * @param Field $field
     * @param $templatePath
     * @return string
     */
    public function  getChampsPourDynamisationRecherche(Field $field, $templatePath)
    {
        $template = $this->getControllerTemplateChamp($templatePath);

        $enumTemplate = $field->getDefaultValue() ? array_slice($template, 0, 3) : [$template[0]];

        return implode(PHP_EOL,  array_map(function($line) use ($field) {
            return str_replace(['NAME', 'TYPE', 'DEFAULT'],
                [$field->getName(), $field->getParametre('type'), $field->getDefaultValue()],$line);}, $enumTemplate));
    }

    public function getValeurParDefautChampPourDynamisationEditionController(Field $field, FilePath $templatePath) : string
    {
        $template = $this->getControllerTemplateChamp($templatePath);
        $enumDefault = $template[3];
//        if ($this->enumType === 'select2') {
//
//        } else {
//
//            $enumDefault = $template[2];
//        }

        return str_replace(['NAME', 'mODULE', 'MODEL', 'COLUMN', 'DEFAULT'],
            [$field->getName(), $this->app->getModuleName(), $this->app->getModelMaker()->getClassName(), $field->getColumn(), $field->getDefaultValue()],$enumDefault);
    }

    public function getClasseMapping() : string
    {
        return "Parametre";
    }
}