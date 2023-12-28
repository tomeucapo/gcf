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
use Laminas\Json\Json;

/**
 * Class modulBase
 */
abstract class modulBase extends controllerBase implements modul
{
    use modulConfig;

    protected $config;

    /**
     * @var templateEngine
     */
    protected templateEngine $tmpl;

    /**
     * @var array
     */
    protected array $templates;

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

            if ($this->tmpl !== null)
                $this->tmpl->readTemplatesFromFile($tmplFileName);
        }

        $this->config = $this->LoadConfig($name);
    }



    public function addTemplate(modulTemplate $myTemplate)
    {
           $this->templates[$myTemplate->name] = $myTemplate;
    }

    public function addTemplateByName(string $templateName)
    {
        if ($this->tmpl !== null)
            $this->templates[$templateName] = new modulTemplate($this->tmpl, $templateName);
    }

    public function renderVars($templateName, array $bindVars)
    {
           if (!array_key_exists($templateName, $this->templates))
               return;

           $this->templates[$templateName]->renderVars($bindVars);
    }

    public function renderVar(string $templateName, string $varName, $value)
    {
        if (!array_key_exists($templateName, $this->templates)) {
            return;
        }

        $this->templates[$templateName]->renderVar($varName, $value);
    }

    /**
     * @param string $templateName
     * @return string
     */
    public function ParsedResult(string $templateName="principal"): string
    {
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
            if (!in_array((string)date("Y"), $this->anys_disponibles))
                $this->anys_disponibles[] = (string)date("Y");

            foreach ($this->anys_disponibles as $any)
                $any_comp .= "['$any','$any'],";
        } else
            $any_comp .= "['" . date("Y") . "','" . date("Y") . "']";
        $any_comp .= "]";

        return '<input type="hidden" id="l_anys_' . self::classBaseName() . '" value="' . $any_comp . '">' . $this->ParsedResult();
    }
}
