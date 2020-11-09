<?php

namespace Core\FieldType;

use Core\App;
use Core\Field;
use Core\FilePath;

class EnumType extends FieldType
{
    protected string $enumType;

    public function __construct($name, App $app)
    {
        $this->enumType = $app->get('usesSelect2') ? 'select2' : 'selectMenu';
        parent::__construct($name, $app);
    }

    public function getEditionView(FilePath $path)
    {
        $suffix = $this->app->get('usesSelect2') ? 'enum_select2' : 'enum';
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
                [$field->getAlias(), $field->getColumn(), $this->module, $this->app->getModelMaker()->getClassName() ,$field->getFormattedName()], $template[7]);
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

        return str_replace(['NAME', 'mODULE', 'MODEL', 'COLUMN'],
            [$field->getName(), $this->app->getModuleName(), $this->app->getModelMaker()->getClassName(), $field->getColumn()], $template[0]);

    }


    public function getValeurParDefautChampPourDynamisationEditionController(Field $field, $templatePath) : string
    {
        $template = $this->getControllerTemplateChamp($templatePath);
        if ($this->enumType === 'select2') {
            $enumDefault = $template[3];
        } else {

            $enumDefault = $template[2];
        }

        return str_replace(['NAME', 'mODULE', 'MODEL', 'COLUMN', 'DEFAULT'],
                [$field->getName(), $this->app->getModuleName(), $this->app->getModelMaker()->getClassName(), $field->getColumn(), $field->getDefaultValue()],$enumDefault);
    }

    /**
     * Récupère le template pour générer le champ en mode dynamisation édition
     * @param $templatePath
     * @return mixed
     */
    public function getControllerTemplateChamp($templatePath)
    {
        return file($this->app->getTrueTemplatePath($templatePath->add('enum')->add($this->enumType)), FILE_IGNORE_NEW_LINES);
    }

    /**
     * @param Field $field
     * @param $templatePath
     * @return string
     */
    public function  getChampsPourDynamisationRecherche(Field $field, $templatePath)
    {
        $template = $this->getControllerTemplateChamp($templatePath);

        if ($this->enumType === 'select2') {
            $enumTemplate = $field->getDefaultValue() ? array_slice($template, 0, 3) : [$template[0]];
        } else {
            $enumTemplate = $field->getDefaultValue() ? array_slice($template, 0, 3) : [$template[0]];
        }


       return implode(PHP_EOL,  array_map(function($line) use ($field) {
           return str_replace(['NAME', 'mODULE', 'MODEL', 'COLUMN', 'DEFAULT'],
               [$field->getName(), $this->app->getModuleName(), $this->app->getModelMaker()->getClassName(), $field->getColumn(), $field->getDefaultValue()],$line);}, $enumTemplate));
    }


}