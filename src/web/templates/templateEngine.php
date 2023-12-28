<?php

/**
 * Classe que ens abstreu del motor de templates, això ens anirà bé el dia que volguem canviar
 * de templates sense que sigui un trauma.
 *
 * User: Tomeu
 * Date: 07/03/2017
 * Time: 21:47
 */

namespace gcf\web\templates;

abstract class templateEngine
{
    protected string $dirTemplates;
    protected string $fileExtension;

    /**
     * @var string
     */
    protected string $dirAssets;

    abstract public function addVar($tmplName, $varName, $value);
    abstract public function addVars($tmplName, array $values);
    abstract public function parseTemplate($tmplName, $mode="w");
    abstract public function setAttribute($tmplName, $attrName, $value);

    abstract public function clearTemplate($tmplName);
    abstract public function clearAllTemplates();

    abstract public function getParsedTemplate($tmplName);
    abstract public function displayParsedTemplate(string $tmplName);
    abstract public function readTemplatesFromFile($fileName);
    abstract public function exists($tmplName);
    abstract public function getTemplateContent($tmplName);

    public function setBasedir(string $dirName) : void
    {
        $this->dirTemplates = $dirName;
    }

    public function setAssetsdir(string $dirName) : void
    {
        $this->dirAssets = $dirName;
    }
}
