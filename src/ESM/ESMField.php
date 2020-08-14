<?php

namespace ESM;

use E2D\E2DField;

class ESMField extends E2DField
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