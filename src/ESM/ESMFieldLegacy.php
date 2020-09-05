<?php

namespace ESM;

/**
 * Class ESMFieldLegacy
 * @package ESM
 */
class ESMFieldLegacy extends ESMField
{
    public function getUpdateField()
    {
        if ($this->isDate() || $this->isTime()) {
            return "$this->column ='. \$this->$this->name .'";
        } elseif ($this->isInteger()) {
            if ($this->isNullable()) {
                return "$this->column = '.addslashes(\$this->$this->name ?? 'NULL').'";
            }
            return "$this->column = '.addslashes(\$this->$this->name).'";
        }
        return "$this->column = \''.addslashes(\$this->$this->name).'\'";
    }



    public function getInsertValue()
    {
        if ($this->isDate()) {
            return "'. \$this->$this->name .'";

        } elseif ($this->isInteger()) {
            if ($this->isNullable()) {
                return "'.addslashes(\$this->$this->name ?? 'NULL').'";
            }
            return "'.addslashes(\$this->$this->name).'";
        }

        return  "\''.addslashes(\$this->$this->name).'\'";
    }

}