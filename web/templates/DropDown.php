<?php

namespace gcf\web\templates;

class DropDown
{
    public static function selectTree(string $title, string $fieldName, string $changeEvent, array $list)
    {
        $strChgEvent = "";
        if (!empty($changeEvent))
            $strChgEvent = "onChange='$changeEvent'";

        $out = "<label for=\"{$fieldName}\">{$title}</label><select name=\"{$fieldName}\" id=\"{$fieldName}\" $strChgEvent>";
        $out.= "<option value=\"\"></option>";
        foreach ($list as $id => $items)
        {
                $out.="<optgroup label=\"{$items["titol"]}\">";
                foreach($items["fills"] as $subId => $subItem) {
                    $out.="<option value=\"$subId\">$subItem</option>";
                }
                $out.="</optgroup>";
        }
        $out.="</select>";
        return $out;
    }

    public static function select(string $title, string $fieldName, string $changeEvent, array $list)
    {
        $strChgEvent = "";
        if (!empty($changeEvent))
            $strChgEvent = "onChange='$changeEvent'";

        $out = "<label for=\"{$fieldName}\">{$title}</label><select name=\"{$fieldName}\" id=\"{$fieldName}\" $strChgEvent>";
        $out.= "<option value=\"\"></option>";
        foreach ($list as $id => $item)
        {
             $out.="<option value=\"$id\">$item</option>";
        }
        $out.="</select>";
        return $out;
    }


    /**
     * Mètode pensat per a ser utilitzat amb arrays indexats [0,1,..,n] on feim que l'atribut value del dropdown
     * sigui el mateix que el camp mostrat a l'usuari. Per defecte selecciona el valor indicat al paràmetre selected
     * @param string $title
     * @param string $fieldName
     * @param string $changeEvent
     * @param array $list Array indexat, [0,1...n],
     * @param string|null $selected
     * @return string
     */
    public static function selectFromIndexedArray(string $title, string $fieldName, string $changeEvent, array $list, ?string $selected)
    {
        $strChgEvent = "";
        if (!empty($changeEvent))
            $strChgEvent = "onChange='$changeEvent'";

        $out = "<label for=\"{$fieldName}\">{$title}</label><select name=\"{$fieldName}\" id=\"{$fieldName}\" $strChgEvent>";
        $out.= "<option value=\"\"></option>";

        foreach ($list as $item)
        {
            if ($selected === $item)
            {
                $out.="<option value=\"$item\" selected>$item</option>";
            } else{
                $out.="<option value=\"$item\">$item</option>";
            }

        }
        $out.="</select>";
        return $out;
    }
}