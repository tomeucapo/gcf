<?php
/**
 * patTemplate engine class wrapper
 * User: tomeu
 * Date: 8/1/2018
 * Time: 2:14 PM
 */

namespace gcf\web\templates;

require_once "patTemplate.php";

class patEngine extends templateEngine
{
    public function __construct()
    {
        $this->objEngine = new \patTemplate("html");
        $this->fileExtension = ".tmp.html";
    }

    public function setBasedir(string $dirName) : void
    {
        parent::setBasedir($dirName);
        $this->objEngine->setBasedir($dirName);
    }

    public function addVar($tmplName, $varName, $value)
    {
        $this->objEngine->addVar($tmplName, $varName, $value);
    }

    public function addVars($tmplName, array $values)
    {
        $this->objEngine->addVars($tmplName, $values);
    }

    public function parseTemplate($tmplName, $mode="w")
    {
        $this->objEngine->parseTemplate($tmplName, $mode);
    }

    public function setAttribute($tmplName, $attrName, $value)
    {
        $this->objEngine->setAttribute($tmplName, $attrName, $value);
    }

    public function clearTemplate($tmplName)
    {
        $this->objEngine->clearTemplate($tmplName);
    }

    public function clearAllTemplates()
    {
    	$this->objEngine->clearAllTemplates();
    }

    public function getParsedTemplate($tmplName)
    {
        return $this->objEngine->getParsedTemplate($tmplName);
    }

    public function readTemplatesFromFile($fileName)
    {
        $this->objEngine->readTemplatesFromFile($fileName . $this->fileExtension);
    }

    public function displayParsedTemplate($tmplName)
    {
        $this->objEngine->displayParsedTemplate($tmplName);
    }

    public function exists($tmplName)
    {
        return $this->objEngine->exists($tmplName);
    }

    public function getTemplateContent($tmplName)
    {
        return $this->objEngine->getTemplateContent($tmplName);
    }

}
