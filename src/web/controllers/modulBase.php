<?php
/**
 * Abstract class that defines base controller of any web module
 *
 * @author Tomeu Capó
 */

namespace gcf\web\controllers;

use gcf\ConfiguratorBase;
use gcf\web\templates\modulTemplate;
use gcf\web\templates\templateEngine;

/**
 * Class modulBase
 * This class defines basic module of application. This is a application controller that used for combines model and view to present
 * data to user as different responses HTML, JSON, ...
 */
abstract class modulBase extends controllerBase implements modul
{
    use modulConfig;

    /**
     * Stores local module configuration if exists into cfg directory
     * @var array
     */
    protected array $config = [];

    /**
     * @var templateEngine
     */
    protected templateEngine $tmpl;

    /**
     * List of templates for different module views
     * @var array
     */
    protected array $templates;

    /**
     * List of years that exists in module database table
     * @var array
     */
    public array $anys_disponibles = [];

    public function __construct(ConfiguratorBase $cfg, $name = "", $withTemplate = true, $tmplEngineName = "")
    {
        parent::__construct($cfg);

        $this->authenticated = true;
        $this->configurador = $cfg;
        $this->db = $cfg->db;

        if (empty($name))
            $name = self::classBaseName();

        // Try to get module specific log
        $this->logger = $cfg->getLoggerObject($name);
        if ($this->logger === null)
            $this->logger = $cfg->getLoggerObject();

        if ($withTemplate)
        {
            $tmplFileName = $name;
            if (!empty($tmplEngineName))
                $this->tmpl = $cfg->getTmplEngine($tmplEngineName);
            else
                $this->tmpl = $cfg->tmpl;

            $this->tmpl->readTemplatesFromFile($tmplFileName);
        }

        $this->config = $this->LoadConfig($name);
    }

    /**
     * Add template object into controller to use it
     * @param modulTemplate $myTemplate
     * @return void
     */
    public function addTemplate(modulTemplate $myTemplate) : void
    {
           $this->templates[$myTemplate->name] = $myTemplate;
    }

    /**
     * Add template block by name to use it
     * @param string $templateName
     * @return void
     */
    public function addTemplateByName(string $templateName) : void
    {
        $this->templates[$templateName] = new modulTemplate($this->tmpl, $templateName);
    }

    /**
     * Render all bind variables into specified template name
     * @param string $templateName
     * @param array $bindVars
     * @return void
     */
    public function renderVars(string $templateName, array $bindVars) : void
    {
           if (!array_key_exists($templateName, $this->templates))
               return;

           $this->templates[$templateName]->renderVars($bindVars);
    }

    /**
     * Render single variable into selected template
     * @param string $templateName
     * @param string $varName
     * @param $value
     * @return void
     */
    public function renderVar(string $templateName, string $varName, $value) : void
    {
        if (!array_key_exists($templateName, $this->templates))
            return;

        $this->templates[$templateName]->renderVar($varName, $value);
    }

    /**
     * @param string $templateName
     * @return string
     */
    public function ParsedResult(string $templateName="principal"): string
    {
        if (!array_key_exists($templateName, $this->templates))
            return "";

        return "".$this->templates[$templateName];
    }

    /**
     * Replace old x_Executor SAJAX call
     * @return string
     */
    public function Execute() : string
    {
        $this->Run();

        $any_comp = "[";
        if (!empty($this->anys_disponibles))
        {
            if (!in_array(date("Y"), $this->anys_disponibles))
                $this->anys_disponibles[] = date("Y");

            foreach ($this->anys_disponibles as $any)
                $any_comp .= "['$any','$any'],";
        } else
            $any_comp .= "['" . date("Y") . "','" . date("Y") . "']";
        $any_comp .= "]";

        return '<input type="hidden" id="l_anys_' . self::classBaseName() . '" value="' . $any_comp . '">' . $this->ParsedResult();
    }
}
