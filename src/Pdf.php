<?php

namespace MediaTech;

class Pdf
{
    const DEFAULT_COMMAND_PATH = '/usr/local/bin/wkhtmltopdf';
    const TMP_FILE_PREFIX = 'tmp_wkhtmltopdf_';

    /** @var string */
    protected $command;
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

    private $availableCommandOptions = [
        'grayscale', 'orientation', 'page-size',
        'lowquality', 'dpi', 'image-dpi', 'image-quality',
        'margin-bottom', 'margin-left', 'margin-right', 'margin-top',
        'page-height', 'page-width', 'no-background', 'encoding', 'enable-forms',
        'no-images', 'disable-internal-links', 'disable-javascript',
        'password', 'username', 'footer-center', 'footer-font-name',
        'footer-font-size', 'footer-html', 'footer-left', 'footer-line',
        'footer-right', 'footer-spacing', 'header-center', 'header-font-name',
        'header-font-size', 'header-html', 'header-left', 'header-line', 'header-right',
        'header-spacing', 'print-media-type', 'zoom', 'javascript-delay', 'no-stop-slow-scripts',
    ];

    public function __construct($path = null, $tmpDir = null)
    {
        $this->command = $path ?: self::DEFAULT_COMMAND_PATH;
        $this->tmpDir = $tmpDir ?: sys_get_temp_dir();

        $this->fileName = uniqid(self::TMP_FILE_PREFIX);
    }

    public function setCommandPath($path)
    {
        $this->command = $path;
        return $this;
    }

    public function loadHtml($content)
    {
        $htmlPath = $this->getHtmlPath();
        if (file_put_contents($htmlPath, $content) === false) {
            throw new \RuntimeException('Unable to create html file from content');
        }
        return $this->setInputPath($htmlPath);
    }

    public function loadHtmlFromUrl($url)
    {
        return $this->setInputPath($url);
    }

    public function loadHtmlFromFile($path)
    {
        return $this->setInputPath($path);
    }

    /**
     * @return Pdf
     */
    public function create()
    {
        $this->getInputPath();

        $processTerminationStatus = $this->executeCommand($output);
        if ($processTerminationStatus !== 0) {
            throw new \RuntimeException(sprintf('Error on pdf file creation: %s', $output));
        }
        $tmpPdfPath = $this->getPdfPath();
        if (!file_exists($tmpPdfPath) || filesize($tmpPdfPath) === 0) {
            throw new \RuntimeException('Error on pdf file creation');
        }
        $this->content = file_get_contents($tmpPdfPath);
        $this->removeTmpFiles();

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
            throw new \InvalidArgumentException('Invalid command option name');
        }

        if (isset($args[0]) && !empty($args[0])) {
            $this->commandOptions[$option] = $args[0];
        } else {
            $this->commandOptions[] = $option;
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
        $descriptors = [
            ['pipe', 'r'],
            ['pipe', 'w'],
            ['pipe', 'w'],
        ];

        $command = sprintf('%s %s %s %s', $this->command, $this->getCommandOptions(), $this->getInputPath(), $this->getPdfPath());

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
            throw new \RuntimeException('Input source path is not set');
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
        return sizeof($options) > 0 ? implode(' ', $options) : '';
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