<?php

namespace gcf\reports\ReportingManager;

use Exception;
use gcf\Environment;
use Monolog\Logger;

class ReportingCloud implements ReportingManagerInterface
{
    private \TextControl\ReportingCloud\ReportingCloud $reportingCloud;

    private array $mergeSettings = [];

    private string $tmpDir;

    private string $reportName;

    private Logger $logger;

    /**
     * @throws Exception
     */
    public function __construct(string $reportName)
    {
        $cfg = Environment::getInstance()->GetAppConfigurator();

        $this->logger = $cfg->getLoggerObject();
        $this->reportName = $reportName;

        $apiKey = $cfg->getConfig()->reporting->apikey;

        if (empty($apiKey))
            throw new Exception("ReportingCloud needs reporting.apiKey to run");

        $this->reportingCloud = new \TextControl\ReportingCloud\ReportingCloud([
            'api_key' => $apiKey,
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

        $this->tmpDir = $cfg->getConfig()->paths->path->temp;
        if (empty($this->tmpDir))
            $this->tmpDir = sys_get_temp_dir();
    }

    /**
     * @param string $titol
     * @return void
     */
    public function SetTitle(string $titol) : void
    {
        $this->mergeSettings['document_subject'] = $titol;
        $this->mergeSettings['document_title'] = $titol;
    }

    /**
     * @throws Exception
     */
    public function Merge(array $data, string $template, string $format) : ?string
    {
        if (!$this->reportingCloud->templateExists($template))
            throw new Exception("$template not found in Reporting Cloud server");

        $fileNames = [];

        $arrayOfBinaryData = $this->reportingCloud->mergeDocument($data, $format, $template, '', false, $this->mergeSettings);
        foreach ($arrayOfBinaryData as $index => $binaryData)
        {
            $destinationFile = sprintf('%s_%s_%d.pdf', $this->reportName, date("YmdHis"), $index);
            $destinationFilename = $this->tmpDir . DIRECTORY_SEPARATOR . $destinationFile;

            $fileNames[] = $destinationFilename;
            file_put_contents($destinationFilename, $binaryData);

            $this->logger->debug("Merged $template was written to $destinationFilename");
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
        } catch (Exception $ex) {
            $this->logger->error("Error while getting template list from server: ".$ex->getMessage());
            return [];
        }

        return $templates;
    }

    public function Thumbnails(string $template) : array
    {
        return $this->reportingCloud->getTemplateThumbnails($template, 1,1,1, "png");
    }
}
