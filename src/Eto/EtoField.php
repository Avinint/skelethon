<?php


namespace Eto;


class EtoField extends \E2D\E2DField
{
    public function getTableHeader()
    {
        return str_repeat("\x20", 20).'<th id="th_'.$this->column.'" class="tri">'.$this->label.'</th>';
    }

    public function getTableColumn()
    {
        $alignment = $this->getAlignmentFromType();

        return str_repeat("\x20", 20)."<td class=\"{$this->getFormattedName()}$alignment\"></td>";
    }
}