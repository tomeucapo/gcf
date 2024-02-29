<?php

namespace gcf\reports\ReportingManager;

use app\configurador;
use Laminas\Log\Logger;

class ReportingCloud implements ReportingManagerInterface
{
    private $reportingCloud;

    private array $mergeSettings = [];

    private string $tmpDir;

    private string $reportName;

    private Logger $logger;

    /**
     * @throws \Exception
     */
    public function __construct(string $reportName)
    {
        $this->logger = configurador::getLogger();
        $this->reportName = $reportName;

        $apiKey = configurador::getInstance()->getConfig()->reporting->apikey;

        if (empty($apiKey))
            throw new \Exception("ReportingCloud needs reporting.apiKey to run");

        $this->reportingCloud = new \TextControl\ReportingCloud\ReportingCloud([
            'api_key' => configurador::getInstance()->getConfig()->reporting->apikey,
        ]);

        $this->mergeSettings = [
            'creation_date' => time(),
            'last_modification_date' => time(),

            'remove_empty_blocks' => true,
            'remove_empty_fields' => true,
            'remove_empty_images' => true,
            'remove_trailing_whitespace' => true,

            'author' => 'Phi Consultors',
            'creator_application' => 'Personal web'
        ];

        $this->tmpDir = configurador::getInstance()->getConfig()->paths->path->temp;
        if (empty($this->tmpDir))
            $this->tmpDir = sys_get_temp_dir();
    }

    public function SetTitle(string $titol)
    {
        $this->mergeSettings['document_subject'] = $titol;
        $this->mergeSettings['document_title'] = $titol;
    }

    /**
     * @throws \Exception
     */
    public function Merge($data, $template, $format)
    {
        if (!$this->reportingCloud->templateExists($template))
            throw new \Exception("$template not found in Reporting Cloud server");

        $fileNames = [];

        $arrayOfBinaryData = $this->reportingCloud->mergeDocument($data, $format, $template, '', false, $this->mergeSettings);
        foreach ($arrayOfBinaryData as $index => $binaryData)
        {
            $destinationFile = sprintf('%s_%s_%d.pdf', $this->reportName, date("YmdHis"), $index);
            $destinationFilename = $this->tmpDir . DIRECTORY_SEPARATOR . $destinationFile;

            $fileNames[] = $destinationFilename;
            file_put_contents($destinationFilename, $binaryData);

            $this->logger->debug("Merged {$template} was written to {$destinationFilename}");
        }

        if (count($fileNames) > 0)
            return $fileNames[0];
        return null;
    }

    public function ListTemplates() : array
    {
        $templates = [];

        try {
            foreach ($this->reportingCloud->getTemplateList() as $template) {
                $templates[$template["template_name"]] = $template["template_name"];
            }
        } catch (\Exception $ex) {
            $this->logger->err("Error while getting template list from server: ".$ex->getMessage());
            return [];
        }

        return $templates;
    }

    public function Thumbnails($template) : array
    {
        return $this->reportingCloud->getTemplateThumbnails($template, 1,1,1, "png");
    }
}
