<?php

namespace gcf\web\templates;

/**
 * DropDown element used from templates
 */
class DropDown
{
    /**
     * Create select tree dropdown HTML element
     * @param string $title
     * @param string $fieldName
     * @param string $changeEvent
     * @param array $list
     * @return string
     */
    public static function selectTree(string $title, string $fieldName, string $changeEvent, array $list) : string
    {
        $strChgEvent = "";
        if (!empty($changeEvent))
            $strChgEvent = "onChange='$changeEvent'";

        $out = "<label for=\"{$fieldName}\">{$title}</label><select name=\"{$fieldName}\" id=\"{$fieldName}\" $strChgEvent>";
        $out.= "<option value=\"\"></option>";
        foreach ($list as $id => $items)
        {
                $out.="<option value=\"$id\" style=\"font-weight: bold;\">{$items["titol"]}</option>";
                foreach($items["fills"] as $subId => $subItem) {
                    $out.="<option value=\"$subId\">&nbsp;&nbsp;&nbsp;$subItem</option>";
                }
        }
        $out.="</select>";
        return $out;
    }

    /**
     * Create select dropdown HTML element
     * @param string $title Visible label of field
     * @param string $fieldName HTML Field element ID and NAME
     * @param string $changeEvent Event on change
     * @param array $list Array of data
     * @param string|null $selectedValue Selected value
     * @param bool $showCode Show PK if is needed
     * @param bool $multiple Enable multiple selection
     * @param bool $firstEmpty Add first blank option
     * @param int|null $defaultWidth Default field width
     * @return string
     */
    public static function select(string $title, string $fieldName, string $changeEvent, array $list, string $selectedValue=null,
                                  bool $showCode=false, bool $multiple=false, bool $firstEmpty=true, ?int $defaultWidth=null): string
    {
        $strChgEvent = "";
        if (!empty($changeEvent))
            $strChgEvent = "onChange='$changeEvent'";

        $styleWidth = $defaultWidth !== null ? 'style="width: '.$defaultWidth.'px"' : "";

        $multiStr = $multiple ? "multiple" : "";
        $out = "<label for=\"{$fieldName}\">{$title}</label><select name=\"{$fieldName}\" id=\"{$fieldName}\" $strChgEvent $multiStr $styleWidth>";
        if ($firstEmpty)
            $out.= "<option value=\"\"></option>";
        foreach ($list as $id => $item)
        {
            $selected = "";
             if ($selectedValue == $id)
                 $selected = "selected";

             $out.="<option value=\"$id\" $selected>".($showCode ? $id : "")." $item </option>";
        }
        $out.="</select>";
        return $out;
    }


    public static function selectMultiple(string $title, string $fieldName, string $changeEvent, array $list, array $selectedValues=null,
                                  bool $showCode=false, ?int $defaultWidth=null, int $size=8): string
    {
        $strChgEvent = "";
        if (!empty($changeEvent))
            $strChgEvent = "onChange='$changeEvent'";

        $styleWidth = $defaultWidth !== null ? 'style="width: '.$defaultWidth.'px"' : "";

        $out = "<label for=\"{$fieldName}\">{$title}</label><select size=\"{$size}\" name=\"{$fieldName}\" id=\"{$fieldName}\" $strChgEvent multiple $styleWidth>";

        foreach ($list as $id => $item)
        {
            $selected = in_array($id, $selectedValues) ? "selected" : "";
            $out.="<option value=\"$id\" $selected>".($showCode ? $id : "")." $item </option>";
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
    public static function selectFromIndexedArray(string $title, string $fieldName, string $changeEvent, array $list, ?string $selected) : string
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