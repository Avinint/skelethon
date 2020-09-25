<?php


namespace E2D;


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
                    //$this->fieldClass::changeToManyToOneField($column, $fieldData); TODO remove
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
        $filterIdSuffixes = $this->config->get('foreign_keys_start_with_id_only') ?? true;
        $potentialFields = array_filter($this->getViewFieldsByType(['int', 'smallint', 'tinyint']), function ($field) use ($filterIdSuffixes, $foreignKeys) {
            return (preg_match('/^id_[a-z]*/', $field['column']) || $filterIdSuffixes === false) && !array_key_exists($field['column'], $foreignKeys);
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
            return $this->highlight($field['name'], 'info') . PHP_EOL;
        }, $potentialFields));
        $askConvertAll = $this->prompt('Voulez-vous convertir tous les champs suivants :' . PHP_EOL . $listNames . 'en Select Ajax ?', ['o', 'n']) === 'o';
        foreach ($potentialFields as &$field) {
            $manyToOneFieldData = $this->getDataForManyToOneField($field);
            if ($manyToOneFieldData === false) {
                $this->msg('Champ invalide comme clé étrangère', 'error');
            } else {
                if ($askConvertAll || $this->prompt('Voulez-vous convertir le champ ' . $this->highlight($field['name']) . ' en Select Ajax ?', ['o', 'n']) === 'o') {
                    $this->convertToManyToOneField($field['column'], $manyToOneFieldData);
                }
            }
        }
    }

    private function getDataForManyToOneField($field)
    {
        $childTable = str_replace('id_', '', $field['column']);
        $tables = $this->databaseAccess->getSimilarTableList($childTable);
        if (count($tables) > 1) {
            $default = $this->config->has('prefix') && array_contains($this->config->get('prefix') . '_' .  $childTable, $tables) ?
                $this->config->get('prefix') . '_' . $childTable :
                (array_contains($childTable, $tables) ? $childTable : false);
            $childTable = $this->askMultipleChoices('table', $tables, $default, $field['column']);
        } else {
            $childTable = $tables[0];
        }

        $tableExists = count($tables) > 0 && !empty($childTable);
        if ($tableExists) {
            $displayFields = [];
            $primaryKeyExists = false;

            $table = $this->databaseAccess->getTableList()[$childTable];

            foreach ($table as $column) {
                $displayFields = $this->getAjaxLabelField($column, $displayFields, $childTable);
                if ($column->Field === $field['column'] && 'PRI' === $column->Key) {
                    $primaryKeyExists = true;
                }
            }

            if (empty($displayFields)) {
                $displayFields[] = $field['column'];
            }

            $concat = count($displayFields) > 1;

            $childTableAlias = $this->createChildTableAlias($childTable);
            if ($concat) {
                //$childTableAlias = 's'. implode("" , array_map([$this, 'pascalize'], $displayFields));
                $displayField = $displayFields;
            } else {
                $displayField = array_shift($displayFields);
            }

            $idField = 'n' . $this->pascalize($field['column']);

            $alias = $this->generateAlias($childTable);

            if ($primaryKeyExists) {

                return ['table' => $childTable, 'pk' => $field['column'], 'label' => $displayField, 'alias' => $alias, 'childTableAlias' => $childTableAlias,  'id' => $idField];
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
        //$this->fieldClass::changeToManyToOneField($fieldColumn, $manyToOneFieldData); TODD remove
        if (!$this->config->has('manyToOne', $this->name)) {
            $this->config->set('manyToOne', [], $this->name);
        }
        //$this->config->set('hasManyToOneRelation', true);

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

}