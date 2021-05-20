<?php
/**
 * Abstract class that defines base controller of any web module
 *
 * @author Tomeu Capó
 */

use app\configurador;
use gcf\ConfiguratorBase;

include_once "interfaces/modul.php";
include_once "web/modulTemplate.php";

/**
 * Class modulBase
 */
abstract class modulBase implements modul 
{
    /**
     * @var \gcf\web\templates\templateEngine
     */
    protected $tmpl;

    /**
     * @var configurador
     */
    protected $configurador;

    /**
     * @var array
     */
    protected $templates;

    /**
     * @var base_dades
     */
    protected $db;

    /**
     * @var Zend_Log|null
     */
    public $logger;

    /**
     * @var
     */
    public $filtres;

    /**
     * @var
     */
    public $mode;

    public $lastResult = null;
    public $lastResultType = null;
    public $lastResultOutfile = null;

    public function __construct(gcf\ConfiguratorBase $cfg, $name="", $withTemplate=true, $tmplEngineName="")
    {
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
             if (!empty($tmplEngineName)) {
                 $this->tmpl = $cfg->getTmplEngine($tmplEngineName);
             } else {
                 $this->tmpl = $cfg->tmpl;
                 $tmplFileName = $name.".tmp.html";
             }

             $this->tmpl->readTemplatesFromFile($tmplFileName);
        }
    }
    
    public function addTemplate(modulTemplate $myTemplate)
    {
           $this->templates[$myTemplate->name] = $myTemplate;
    }

    public function addTemplateByName(string $templateName)
    {
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
     * @deprecated
     */
    public function resultat_html($templateName="principal")
    {
           return "".$this->templates[$templateName];
    }

    /**
     * @param string $templateName
     * @return string
     */
    public function ParsedResult($templateName="principal")
    {
        return "".$this->templates[$templateName];
    }

    protected static function classBaseName()
    {
        $className = explode("\\", get_called_class());
        return array_pop($className);
    }
}
