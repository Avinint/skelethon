<?php

namespace Core\FieldType;

use Core\Field;
use Core\FilePath;

class ForeignKeyType extends PrimaryKeyType
{
    public function getEditionView(FilePath $path)
    {
        return file_get_contents($this->app->getTrueTemplatePath($path->add('enum_select_ajax')));
    }

    /**
     * @param array $template
     * @param string $indent
     * @param array $lines
     * @return string
     */
    public function addSelectFieldFormattedLines(string $indent, Field $field, array $template): string
    {
        $strategy = $field->getManyToOne()['strategy'] ?? 'joins';
        if ($strategy === 'nested') {
            $fieldText = implode($indent, array_slice($template, 9));
        } else {
            $fieldText = $indent . $template[8];
        }

        return str_replace(['FKALIAS', 'LABEL', 'CONCATALIAS', 'FKTABLE', 'PK', 'ALIAS', 'FIELD'],
            [$field->getManyToOne()['alias'], $field->getManyToOne()['label'], $field->getManyToOne()['labelAlias'], $field->getManyToOne()['table'], $field->getManyToOne()['pk'], $field->getAlias(), $this->getFormattedName()],
            $fieldText);
    }


}