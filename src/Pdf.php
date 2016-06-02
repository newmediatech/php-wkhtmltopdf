<?php

namespace Converter;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem;

class Pdf
{
    const DEFAULT_UTILITY_PATH = '/usr/local/bin/wkhtmltopdf';

    /** @var string */
    protected $binUtilityPath;
    /** @var string */
    protected $path;

    /** @var array */
    protected $params;
    /** @var mixed */
    protected $pdfContent;
    /** @var string */
    protected $htmlContent;

    /** @var string */
    protected $fileName;
    /** @var string */
    protected $tmpDirectory;

    /** @var Filesystem */
    protected $fileSystem;

    protected $availableParams = [
        'grayscale', 'orientation', 'page-size',
        'lowquality', 'dpi', 'image-dpi', 'image-quality',
        'margin-bottom', 'margin-left', 'margin-right', 'margin-top',
        'page-height', 'page-width', 'no-background', 'encoding', 'enable-forms',
        'no-images', 'disable-internal-links', 'disable-javascript',
        'password', 'username', 'footer-center', 'footer-font-name',
        'footer-font-size', 'footer-html', 'footer-left', 'footer-line',
        'footer-right', 'footer-spacing', 'header-center', 'header-font-name',
        'header-font-size', 'header-html', 'header-left', 'header-line', 'header-right',
        'header-spacing', 'print-media-type', 'zoom'
    ];

    public function __construct($utilityPath = null, $fileStorageAdapter = null)
    {
        $this->binUtilityPath = is_string($utilityPath) ? $utilityPath : self::DEFAULT_UTILITY_PATH;
        $this->fileName = (string)microtime(true);
        $this->tmpDirectory = sys_get_temp_dir();
        $this->params = [];

        if ($fileStorageAdapter instanceof AdapterInterface) {
            $this->setFileStorageAdapter($fileStorageAdapter);
        }
    }

    public function setFileStorageAdapter(AdapterInterface $fileStorageAdapter)
    {
        $this->fileSystem = new Filesystem($fileStorageAdapter);
    }

    public function loadHtml($content)
    {
        $htmlPath = $this->getHtmlPath();
        file_put_contents($htmlPath, $content);
        return $this->setPath($htmlPath);
    }

    public function loadFromUrl($url)
    {
        return $this->setPath($url);
    }

    public function loadFromHtmlFile($path)
    {
        return $this->setPath($path);
    }

    public function generate()
    {
        $processTerminationStatus = $this->executeCommand($output);
        if ($processTerminationStatus !== 0) {
            throw new PdfException('Error on pdf file generation');
        }

        $this->pdfContent = $this->getPdfContents();
        $this->removeTemporaryFiles();
        return $this;
    }

    public function save($fileName, $overwrite = false)
    {
        $method = $overwrite ? 'put' : 'write';
        call_user_func_array([$this->fileSystem, $method], [$fileName, $this->getPdfContent()]);
        return $this;
    }

    public function getPdfContents()
    {
        return file_get_contents($this->getPdfPath());
    }

    public function __call($method, $args)
    {
        $param = $this->methodToParam($method);
        if (!in_array($param, $this->availableParams)) {
            throw new PdfException('Invalid option name');
        }

        if (isset($args[0]) && !empty($args[0])) {
            $this->params[$param] = $args[0];
        } else {
            $this->params[] = $args[0];
        }
        return $this;
    }

    protected function methodToParam($method)
    {
        return ltrim(strtolower(preg_replace('/[A-Z]/', '-$0', $method)), '-');
    }

    protected function getPdfContent()
    {
        if ($this->pdfContent === null) {
            $this->generate();
        }
        return $this->pdfContent;
    }

    protected function executeCommand(&$output)
    {
        if (!file_exists($this->binUtilityPath)) {
            throw new PdfException('Invalid binary utility path');
        }
        if(!is_executable($this->binUtilityPath)) {
            throw new PdfException('Binary utility is not executable');
        }

        $descriptors = [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w'],
        ];

        $command = sprintf('%s %s %s %s', $this->binUtilityPath, $this->getParams(), $this->getInputPath(), $this->getPdfPath());

        $process = proc_open($command, $descriptors, $pipes);
        list($stdIn, $stdOut, $stdError) = $pipes;

        $output = stream_get_contents($stdOut) . stream_get_contents($stdError);

        fclose($stdIn);
        fclose($stdOut);
        fclose($stdError);

        return proc_close($process);
    }

    protected function getInputPath()
    {
        if ($this->path === null) {
            throw new PdfException('Input source path is not set');
        }
        return $this->path;
    }

    protected function setPath($path)
    {
        if (!file_exists($path)) {
            throw new PdfException('Html file does not exists');
        }
        $this->path = $path;
        return $this;
    }

    protected function removeTemporaryFiles()
    {
        $pdfFilePath = $this->getPdfPath();
        if (file_exists($pdfFilePath)) {
            unlink($pdfFilePath);
        }
        $htmlFilePath = $this->getHtmlPath();
        if (file_exists($htmlFilePath)) {
            unlink($htmlFilePath);
        }
    }

    protected function getParams()
    {
        $params = [];
        foreach ($this->params as $key => $value) {
            $params[] = is_numeric($key) ? '--' . $value : sprintf('--%s "%s"', $key, $value);
        }
        return implode(' ', $params);
    }

    protected function getHtmlPath()
    {
        return $this->tmpDirectory . DIRECTORY_SEPARATOR . $this->fileName . '.html';
    }

    protected function getPdfPath()
    {
        return $this->tmpDirectory . DIRECTORY_SEPARATOR . $this->fileName . '.pdf';
    }
}