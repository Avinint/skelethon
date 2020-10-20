<?php


namespace E2D;


use Core\FilePath;

trait E2DManyToOneMaker
{

    private function askAddManyToOneField()
    {
        //$this->hasManyToOneRelation = $this->config->get('hasManyToOneRelation', $this->name);
        $foreignKeys = $this->config->get('manyToOne', $this->name) ?? [];
        $potentialFields = $this->getPotentialFields($foreignKeys);

        if ($this->config->get('hasManyToOneRelation', $this->name) ?? true) {

            $gotPotential = !empty($potentialFields);
            $needToAsk = !$this->config->has('hasManyToOneRelation', $this->name) && ($gotPotential || !empty($foreignKeys));

            if ($needToAsk) {
                $hasManyToOneRelation = $this->prompt('Voulez-vous transformer des champs en selects Ajax ?', ['o', 'n']) === 'o';
                $this->config->set('hasManyToOneRelation', $hasManyToOneRelation, $this->name);
            }

            if ($this->config->get('hasManyToOneRelation')) {
                if ($gotPotential) {
                    $this->askConvertToManyToOneFields($potentialFields);
                }

                foreach ($foreignKeys as $column => $fieldData ) {
                    $this->getFieldByColumn($column)->changeToManyToOneField($fieldData);
                }
            }

        }
    }

    /**
     * @param bool $filterIdSuffixes
     * @param array $foreignKeys
     * @return array
     */
    private function getPotentialFields(array $foreignKeys): array
    {
        $filterWithIdPrefix = $this->config->get('foreign_keys_start_with_id') ?? true;
        $filterWithIdSuffix = $this->config->get('foreign_keys_ends_with_id') ?? true;
        $potentialFields = array_filter($this->getFieldsByType(['int', 'smallint', 'tinyint']), function ($field) use ($filterWithIdPrefix, $filterWithIdSuffix, $foreignKeys) {
            $startsWithIdPrefix = preg_match('/^id_[a-z]*/', $field->getColumn());
            $endsWithIdSuffix = preg_match('/[a-z]*_id^/', $field->getColumn());
            return (($startsWithIdPrefix  || $filterWithIdPrefix  === false) || ($endsWithIdSuffix || $filterWithIdSuffix  === false)) && !array_key_exists($field->getColumn(), $foreignKeys);
        });

        return $potentialFields;
    }

    /**
     * @param array $potentialFields
     *
     */
    private function askConvertToManyToOneFields(array $potentialFields)
    {
        $listNames = implode('', array_map(function ($field) {
            return $this->highlight($field->getName(), 'info') . PHP_EOL;
        }, $potentialFields));
        $askConvertAll = $this->prompt('Voulez-vous convertir tous les champs suivants :' . PHP_EOL . $listNames . 'en Select Ajax ?', ['o', 'n']) === 'o';
        foreach ($potentialFields as &$field) {
            $manyToOneFieldData = $this->getDataForManyToOneField($field);
            if ($manyToOneFieldData === false) {
                $this->msg('Champ invalide comme clé étrangère', 'error');
            } else {
                if ($askConvertAll || $this->prompt('Voulez-vous convertir le champ ' . $this->highlight($field->getName()) . ' en Select Ajax ?', ['o', 'n']) === 'o') {
                    $this->convertToManyToOneField($field->getColumn(), $manyToOneFieldData);
                }
            }
        }
    }

    private function getDataForManyToOneField($field)
    {
        $childTable = str_replace('id_', '', $field->getColumn());
        $tables = $this->databaseAccess->getSimilarTableList($childTable);

        if (count($tables) > 1) {
            $default = $this->config->has('prefix') && array_contains($this->config->get('prefix') . '_' .  $childTable, $tables) ?
                $this->config->get('prefix') . '_' . $childTable :
                (array_contains($childTable, $tables) ? $childTable : false);
            $childTable = $this->askMultipleChoices('table', $tables, $default, $field->getColumn());
        } elseif (count($tables) === 1) {
            $childTable = $tables[0];
        } else {
            $tables = $this->databaseAccess->getTableList();
            $childTable = $this->askMultipleChoices('table', array_keys($tables), false, $field->getColumn());
        }

        $tableExists = count($tables) > 0 && !empty($childTable);
        if ($tableExists) {
            $displayFields = [];
            $primaryKeyExists = false;

            $table = $this->databaseAccess->getTableList()[$childTable];

            foreach ($table as $column) {
                $displayFields = $this->getAjaxLabelField($column, $displayFields, $childTable);
                if ('PRI' === $column->Key) {
                    $primaryKeyExists = true;
                    $primaryKey = $column->Field;
                }
            }

            if (empty($displayFields)) {
                $displayFields[] = $field->getColumn();
            }

            $concat = count($displayFields) > 1;

            $childTableAlias = $this->createChildTableAlias($childTable);
            if ($concat) {
                //$childTableAlias = 's'. implode("" , array_map([$this, 'pascalize'], $displayFields));
                $displayField = $displayFields;
            } else {
                $displayField = array_shift($displayFields);
            }

            $idField = 'n' . $this->pascalize($field->getColumn());

            $alias = $this->generateAlias($childTable);

            if ($primaryKeyExists) {
                return ['table' => $childTable, 'pk' => $primaryKey, 'label' => $displayField, 'alias' => $alias, 'childTableAlias' => $childTableAlias,  'id' => $idField];
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * @param $field
     * @param array $manyToOneFieldData
     */
    private function convertToManyToOneField($fieldColumn, array $manyToOneFieldData): void
    {
        $this->getFieldByColumn($fieldColumn)->changeToManyToOneField($manyToOneFieldData);
        if (!$this->config->has('manyToOne', $this->name)) {
            $this->config->set('manyToOne', [], $this->name);
        }

        $this->config->addTo('manyToOne', $fieldColumn, $manyToOneFieldData , $this->name);
    }

    /**
     * Essaie de deviner quel champ utiliser dans table parente de la relation Many2One
     * @param $column
     * @param array $displayFields
     * @param bool $childTable
     * @return array
     */
    private function getAjaxLabelField($column, array $displayFields, bool $childTable): array
    {
        $champNom = $column->Field === 'nom';
        $champPrenom = $column->Field === 'prenom';
        $fieldName = strpos($column->Field, 'name') === 0;
        $fieldFirstName = strpos($column->Field, 'first_name') !== false || strpos($column->Field, 'firstname') !== false;
        if ($champNom || $champPrenom) {
            if ($champNom) {
                $displayFields[] = $column->Field;
            }
            if ($champPrenom) {
                $displayFields[] = $column->Field;
            }
        } elseif ($column->Field === 'libelle') {
            $displayFields[] = $column->Field;
        } elseif ($column->Field === 'titre') {
            $displayFields[] = $column->Field;
        } elseif ($column->Field === 'nom') {
            $displayFields[] = $column->Field;
        } elseif ($fieldName || $fieldFirstName) {
            if ($fieldName) {
                $displayFields[] = $column->Field;
            }
            if ($fieldFirstName) {
                $displayFields[] = $column->Field;
            }
        } elseif ($column->Field === 'label') {
            $displayFields[] = $column->Field;
        } elseif ($column->Field === 'title') {
            $displayFields[] = $column->Field;

        } elseif (strpos($column->Field, 'name') > 0) {
            $displayFields[] = $column->Field;
        } elseif ($column->Field === $childTable) {
            $displayFields[] = $childTable;
        } elseif ($column->Field === 'valeur') {
            $displayFields[] = $column->Field;
        }
        return $displayFields;
    }

    public function createChildTableAlias($tableName)
    {
        $prefix = $this->config->get('prefix') ?? '';
        if ($prefix)
            $tableName = str_replace($prefix.'_' , '', $tableName);

        return 's'. $this->pascalize($tableName);
    }

    /**
     * @param string $templatePath
     * @param FilePath $path
     * @param string $select2SearchText
     * @param string $select2EditText
     * @param array $selectAjaxDefinition
     * @return array
     * @throws \Exception
     */
    public function addSelectAjaxToJavaScript(FilePath $templatePath, string $select2SearchText, string $select2EditText, array $selectAjaxDefinition): array
    {
        $fields = $this->getFieldsByType('foreignKey', ['recherche', 'edition']);

        $selectAjaxCallSearchTextTemp = PHP_EOL . file_get_contents($this->getTrueTemplatePath($templatePath->get('recherche')->add('selectAjaxCall')));
        $selectAjaxCallEditTextTemp = PHP_EOL . file_get_contents($this->getTrueTemplatePath($templatePath->get('edition')->add('selectAjaxCall')));
        $selectAjaxDefinitionTemp = file_get_contents($this->getTrueTemplatePath($templatePath->add('selectAjaxDefinition')));

        foreach ($fields as $field) {
            $foreignClassName = substr($field->getManyToOne()['childTableAlias'], 1);
            if ($field->hasView('recherche')) {
                $select2SearchText .= $this->addSelectAjaxSearch($field, $selectAjaxCallSearchTextTemp, $foreignClassName);

            }
            if ($field->hasView('edition')) {
                $select2EditText .= $this->addSelectAjaxEdition($field, $selectAjaxCallEditTextTemp, $foreignClassName);
            }

            $this->addSelectAjaxMethodDefinition($field, $selectAjaxDefinitionTemp, $selectAjaxDefinition, $foreignClassName);
        }

        $selectAjaxDefinitionText = PHP_EOL . implode(PHP_EOL, $selectAjaxDefinition) . PHP_EOL;
        return array($select2SearchText, $select2EditText, $selectAjaxDefinitionText);
    }

    /**
     * @param E2DField $field
     * @param string $selectAjaxCallTextTemp
     * @param string $foreignClassName
     * @return string
     */
    private function addSelectAjaxSearch(E2DField $field, string $selectAjaxCallTextTemp, string $foreignClassName):  string
    {
        return str_replace(['MODEL', 'FORM', 'NAME', 'ALLOWCLEAR'], [$foreignClassName, 'eFormulaire', $field->getName(), 'true'], $selectAjaxCallTextTemp);

    }

    /**
     * @param E2DField $field
     * @param string $selectAjaxCallEditTextTemp
     * @param string $foreignClassName
     * @return string
     */
    private function addSelectAjaxEdition(E2DField $field,  string $selectAjaxCallEditTextTemp, string $foreignClassName): string
    {
        $allowClear = $field->isNullable() ? 'true' : 'false';

        return str_replace(['MODEL', 'FORM', 'NAME', 'FIELD', 'ALLOWCLEAR'],
            [$foreignClassName, 'oParams.eFormulaire', $field->getName(), $field->getFormattedName(), $allowClear], $selectAjaxCallEditTextTemp);
    }

    /**
     * @param array $selectAjaxDefinition
     * @param E2DField $field
     * @param string $selectAjaxDefinitionTemp
     * @param string $foreignClassName
     * @return array
     */
    private function addSelectAjaxMethodDefinition(E2DField $field, string $selectAjaxDefinitionTemp, array &$selectAjaxDefinition, string $foreignClassName)
    {
        $label = $field->getManyToOne()['label'];
        if (is_array($label)) {
            $label = $this->generateConcatenatedColumn($label);
        }

        if (!isset($selectAjaxDefinition[$field->getColumn()])) {
            $selectAjaxDefinition[$field->getColumn()] = str_replace(['MODEL', 'PK', 'LABEL', 'TABLE', 'ORDERBY'],
                [$foreignClassName, $field->getColumn(), $label, $field->getManyToOne()['table'], $field->getColumn()], $selectAjaxDefinitionTemp);
        }

    }

    /**
     * @param array $column
     * @param string $alias
     * @return string
     */
    public function generateConcatenatedColumn(array $columns, $alias = ''): string
    {
        if ($alias !== '') {
            $alias = $alias. '.';

            $columns = array_map(function($part) use ($alias) {return $alias.$part;}, $columns);
        }
        return "CONCAT_WS(\' \', " . implode(", ",  $columns) . ')';
    }
}