<?php

namespace G4E;

use Core\Field;
use E2D\E2DModelMaker;

class G4EModelMaker extends E2DModelMaker
{
    public function getMappingChamps()
    {

    }

//    public function getAttributes($template, $view = '') :string
//    {
//        return implode(PHP_EOL, array_map(function (Field $field)  use ($template) {return $field->getFieldMapping($template);}, $this->getFields('all')));
//    }

    /**
     * @param $template
     * @param $table
     * @return array
     */
    public function getAttributes($template, $table = '')
    {
        $mapping = [];
        $imports = [];
        foreach ($this->getFields('all', '', true) as $field) {
            $mapping[] = $field->getFieldMapping($template, $table);
            $imports[] = $field->getType()->getClasseMapping();
        }
        return [implode(PHP_EOL, $mapping), implode(', ', array_unique($imports)) ];
    }

    protected function askTableName()
    {
        if ($this->config->has('tableName')) {
            $this->tableName = $this->config->get('tableName');
            return;
        }

        $prefix = $this->config->get('prefix') ?? $this->askPrefix();

        $tempTableName = strtoupper(($prefix ? $prefix . '_' : '') . $this->name);

        $tableName = $this->config->get('tableName') ?? readline($this->msg('Si le nom de la table en base est différent de '. $this->highlight($tempTableName , 'success'). ' entrer le nom de la table :').'');

        if (empty($tableName)) $tableName = $tempTableName;

        $this->config->set('tableName', $tableName, $this->name);

        $this->tableName = $tableName;
    }
}