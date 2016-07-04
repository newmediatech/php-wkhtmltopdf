<?php

namespace Converter;

class Pdf
{
    const DEFAULT_BIN_PATH = '/usr/local/bin/wkhtmltopdf';
    const TMP_FILE_PREFIX = 'tmp_wkhtmltopdf_';

    /** @var string */
    protected $binary;
    /** @var string */
    protected $fileName;
    /** @var string */
    protected $tmpDir;
    /** @var string */
    protected $path;
    /** @var array */
    protected $commandOptions = [];

    /** @var mixed */
    protected $content;

    protected $availableCommandOptions = [
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

    public function __construct($binary = null)
    {
        $this->binary = is_string($binary) ? $binary : self::DEFAULT_BIN_PATH;
        $this->fileName = uniqid(self::TMP_FILE_PREFIX);
        $this->tmpDir = sys_get_temp_dir();
    }

    public function loadHtml($content)
    {
        $htmlPath = $this->getHtmlPath();
        file_put_contents($htmlPath, $content);
        return $this->setInputPath($htmlPath);
    }

    public function loadFromUrl($url)
    {
        return $this->setInputPath($url);
    }

    public function loadFromHtmlFile($path)
    {
        return $this->setInputPath($path);
    }

    /**
     * @return Pdf
     * @throws PdfException
     */
    public function create()
    {
        $processTerminationStatus = $this->executeCommand($output);
        if ($processTerminationStatus !== 0) {
            throw new PdfException(sprintf('Error on pdf file creation: %s', $output));
        }
        $tmpPdfPath = $this->getPdfPath();
        if (file_exists($tmpPdfPath) && filesize($tmpPdfPath) > 0) {
            $this->content = file_get_contents($tmpPdfPath);
            $this->removeTmpFiles();
        } else {
            throw new PdfException('Error on pdf file creation');
        }
        return $this;
    }

    /**
     * @param string $fileName
     * @return bool
     */
    public function save($fileName)
    {
        $content = $this->getContent();
        $size = file_put_contents($fileName, $content, LOCK_EX);
        if ($size === false) {
            return false;
        }
        return true;
    }

    public function __call($method, $args)
    {
        $option = ltrim(strtolower(preg_replace('/[A-Z]/', '-$0', $method)), '-');
        if (!in_array($option, $this->availableCommandOptions)) {
            throw new PdfException('Invalid command option name');
        }

        if (isset($args[0]) && !empty($args[0])) {
            $this->commandOptions[$option] = $args[0];
        } else {
            $this->commandOptions[] = $args[0];
        }
        return $this;
    }

    protected function getContent()
    {
        if ($this->content === null) {
            $this->create();
        }
        return $this->content;
    }

    protected function executeCommand(&$output)
    {
        if (!file_exists($this->binary)) {
            throw new PdfException('Invalid binary utility path');
        }
        if(!is_executable($this->binary)) {
            throw new PdfException('Binary utility is not executable');
        }

        $descriptors = [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w'],
        ];

        $command = sprintf('%s %s %s %s', $this->binary, $this->getCommandOptions(), $this->getInputPath(), $this->getPdfPath());

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

    protected function setInputPath($path)
    {
        $this->path = $path;
        return $this;
    }

    protected function removeTmpFiles()
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

    /**
     * @return string
     */
    protected function getCommandOptions()
    {
        $options = [];
        foreach ($this->commandOptions as $key => $value) {
            $options[] = is_numeric($key) ? '--' . $value : sprintf('--%s "%s"', $key, $value);
        }
        return implode(' ', $options);
    }

    protected function getHtmlPath()
    {
        return $this->tmpDir . '/' . $this->fileName . '.html';
    }

    protected function getPdfPath()
    {
        return $this->tmpDir . '/' . $this->fileName . '.pdf';
    }
}