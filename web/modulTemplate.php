<?php
/**
 * Class that defines template methods of any web controller
 * User: tomeu
 * Date: 5/30/2018
 * Time: 2:43 PM
 */

use gcf\web\templates\templateEngine;


class modulTemplate
{
    const FORM_TYPE=1;
    const NORMAL_TYPE=2;

    /**
     * @var string Template name
     */
    public $name;

    /**
     * @var templateEngine Template engine object
     */
    private $tmpl;

    /**
     * @var int Module type (form or non-form)
     */
    private $type;

    /**
     * @var string Form name
     */
    private $formName;

    /**
     * @var string
     */
    private $formAction = "main.php";

    public function __construct(templateEngine $tmplObj, $name, $formName="", $type=modulTemplate::NORMAL_TYPE)
    {
        $this->name = $name;
        $this->tmpl = $tmplObj;
        $this->type = $type;
        $this->formName = $formName;
    }

    public function renderVars(array $bindVars)
    {
        $this->tmpl->addVars($this->name, $bindVars);
    }

    public function renderVar($varName, $value)
    {
        $this->tmpl->addVar($this->name, $varName, $value);
    }

    public function __toString()
    {
        $beginForm = $endForm = "";
        if ($this->type == modulTemplate::FORM_TYPE)
        {
            $beginForm = '<form name="'.$this->formName.'" id="'.$this->formName.'" method="post" action="'.$this->formAction.'" enctype="multipart/form-data; charset=utf-8">';
            $endForm = '</form>';
        }

        $this->tmpl->parseTemplate($this->name);
        return($beginForm.$this->tmpl->getParsedTemplate($this->name).$endForm);
    }
}
