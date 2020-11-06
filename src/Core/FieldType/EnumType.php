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
                [$field->getAlias(), $field->getColumn(), $this->module, $this->model ,$field->getFormattedName()], $template[7]);
    }


    /**
     * Génère les lignes des controllers générer les selects
     * en peupLant
     * @param $field
     * @param $templatePath
     * @return mixed|string|string[]
     */
    public function getChampsPourDynamisationEdition($field, $templatePath)
    {

        $template = $this->getControllerEditionTemplate( $templatePath->add('enum'));


        return str_replace(['NAME', 'mODULE', 'TABLE', 'COLUMN'],
            [$field->getName(), $this->app->getModuleName(), $this->app->getModelName(), $field->getColumn()], $template);
       // $template = $this->enumType === 'select2' ?   :

        $default = $field->getDefaultValue() ?? '';
    }

    /**
     * Récupère le template pour générer le champ en mode dynamisation édition
     * @param $templatePath
     * @return mixed
     */
    public function getControllerEditionTemplate($templatePath)
    {
//        $this->addToExceptions($field, $exceptions);
//        $defaults[] = $this->getValeurParDefautPourChampController($field, $defaults, $fieldTemplatePath);

        $template = file($this->app->getTrueTemplatePath($templatePath->add($this->enumType)), FILE_IGNORE_NEW_LINES);
        return $template[0];

        // $fieldsText = $field->buildFieldForController($field, $template, $this->templateNullableFields);
    }


    public function  getChampsPourDynamisationRecherche(Field $field, $templatePath, array &$enums, array &$defaults)
    {
        //$this->enumType = $this->app->get('usesSelect2') ? 'select2' : 'selectMenu';
        [$enumTemplate, $defaultTemplate] = $this->getControllerSearchTemplate($field, $templatePath->add('enum'));

        var_dump($enumTemplate);
        //return $templates;
       $template =  array_map(function($line) use ($field) {return str_replace(['NAME', 'mODULE', 'TABLE', 'COLUMN'], [$field->getName(), $this->app->getModuleName(), $this->app->getModelName()],$line);}, $enumTemplate);
       var_dump($template); die();
    }

    /**
     *  Récupère le template pour générer le champ en mode dynamisation recherche
     * @param $field
     * @param $templatePath
     */
    protected function getControllerSearchTemplate($field, $templatePath)
    {
        $template = file($this->app->getTrueTemplatePath($templatePath->add($this->enumType)), FILE_IGNORE_NEW_LINES);

        $default = $field->getDefaultValue() ?? '';

        if ($this->enumType === 'select2') {
            if ($default) {
                $enumSearchLines = array_slice($template, 0, 3);
                $enumDefault     = $template[3];
            } else {
                $enumSearchLines = [$template[0]];
            }
        } else {
            if ($default) {
                $enumSearchLines = $template;
                $enumDefault     = $enumSearchLines[2];
            } else {
                $enumSearchLines = [$template[0]];
            }
        }

        return [$enumSearchLines, $enumDefault];
    }


}