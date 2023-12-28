<?php
/**
 * Class to define layer with TWIG template engine
 * User: tomeu
 * Date: 8/1/2018
 * Time: 2:44 PM
 */

namespace gcf\web\templates;

use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;
use Twig\TwigFunction;
use Twig\Extra\Intl\IntlExtension;

class twigEngine extends templateEngine
{
    private Environment $objEngine;
    private FilesystemLoader $loader;
    private TemplateWrapper $template;

    private array $tmplContent;
    private array $parsedContent;

    public function __construct()
    {
        $this->loader = new FilesystemLoader();
        $this->objEngine = new Environment($this->loader);
        $this->objEngine->addExtension(new IntlExtension());
        $this->tmplContent = [];
        $this->parsedContent = [];
        $this->fileExtension =".twig";
    }

    /**
     * @param string $dirName
     * @throws LoaderError
     */
    public function setBasedir(string $dirName) : void
    {
        parent::setBasedir($dirName);
        $this->loader->addPath($dirName);
    }

    /**
     * Create internal function asset for get images of correct path
     * @param $dirName
     */
    public function setAssetsdir(string $dirName) : void
    {
        parent::setAssetsdir($dirName);
        $this->initFunctions();
    }

    /**
     * Create self functions used on templates
     */
    private function initFunctions() : void
    {
        $this->objEngine->addGlobal("dropdown", new DropDown());

        $this->objEngine->addFunction(new TwigFunction("asset", function ($asset) {
            return $this->dirAssets . $asset;
        }));

        $this->objEngine->addFunction(new TwigFunction("button", function ($link, $image, $hint) {
            return "<a href=\"$link\"><img src=\"{$this->dirAssets}"."$image\" title=\"$hint\" alt=\"$hint\" border=\"0\"></a>";
        }));

        $this->objEngine->addFunction(new TwigFunction("button32", function ($link, $image, $hint) {
            return "<a href=\"$link\"><img src=\"{$this->dirAssets}"."$image\" title=\"$hint\" alt=\"$hint\" border=\"0\" height='32px' width='32px'></a>";
        }));

        $this->objEngine->addFunction(new TwigFunction("button16", function ($link, $image, $hint) {
            return "<a href=\"$link\"><img src=\"{$this->dirAssets}"."$image\" title=\"$hint\" alt=\"$hint\" border=\"0\" height='16px' width='16px'></a>";
        }));
    }

    public function addVar($tmplName, $varName, $value) : void
    {
        if (!array_key_exists($tmplName, $this->tmplContent))
            $this->tmplContent[$tmplName] = [$varName => $value];
        else if (array_key_exists($varName, $this->tmplContent[$tmplName]))
            $this->tmplContent[$tmplName][$varName] = $value;
        else $this->tmplContent[$tmplName] = [$varName => $value];
    }

    public function addVars($tmplName, array $values)
    {
        $this->tmplContent[$tmplName] = $values;
    }

    /**
     * @param $tmplName
     * @param $mode
     * @throws Throwable
     */
    public function parseTemplate($tmplName, $mode="w")
    {
        if (!array_key_exists($tmplName, $this->parsedContent))
            $this->parsedContent[$tmplName] = "";

        if (!array_key_exists($tmplName, $this->tmplContent))
            $this->tmplContent[$tmplName] = [];

        if ($mode === 'a')
            $this->parsedContent[$tmplName] .= $this->template->renderBlock($tmplName, $this->tmplContent[$tmplName]);
        else
            $this->parsedContent[$tmplName] = $this->template->renderBlock($tmplName, $this->tmplContent[$tmplName]);
    }

    public function setAttribute($tmplName, $attrName, $value)
    {

    }

    public function clearTemplate($tmplName)
    {
           unset($this->parsedContent[$tmplName]);
    }

    public function clearAllTemplates()
    {
	    $this->parsedContent = [];
    }

    /**
     * @param $tmplName
     * @return string
     * @throws Throwable
     */
    public function getParsedTemplate($tmplName)
    {
           if (!array_key_exists($tmplName, $this->parsedContent) && !empty($this->parsedContent[$tmplName]))
                return $this->parsedContent[$tmplName];

           return $this->template->renderBlock($tmplName, $this->tmplContent[$tmplName]);
    }

    /**
     * @param $fileName
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function readTemplatesFromFile($fileName)
    {
        //if ($this->template !== NULL)
        //    throw new \Exception("This template engine supports one file loaded at time!");

        $this->template = $this->objEngine->load($fileName.$this->fileExtension);
    }

    public function displayParsedTemplate(string $tmplName)
    {
        if (array_key_exists($tmplName, $this->parsedContent) && !empty($this->parsedContent[$tmplName]))
            echo $this->parsedContent[$tmplName];
    }

    public function exists($tmplName)
    {
        return $this->template->hasBlock($tmplName);
    }

    /**
     * @param $tmplName
     * @return string
     * @throws Throwable
     */
    public function getTemplateContent($tmplName)
    {
        return $this->template->renderBlock($tmplName);
    }
}
