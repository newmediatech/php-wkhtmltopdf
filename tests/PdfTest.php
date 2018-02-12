<?php

namespace MediaTech\Tests;

use MediaTech\Pdf;

class PdfTest extends \PHPUnit_Framework_TestCase
{
    /** @var Pdf */
    private $pdf;

    protected function setUp()
    {
        $this->pdf = new Pdf();
    }

    public function testDefaultConstructor()
    {        
        $this->assertAttributeEquals(Pdf::DEFAULT_COMMAND_PATH, 'command', $this->pdf);
        $this->assertAttributeEquals(sys_get_temp_dir(), 'tmpDir', $this->pdf);
        $this->assertAttributeEquals([], 'commandOptions', $this->pdf);
    }
    
    public function testConstructor()
    {
        $pdf = new Pdf('/bin/wkhtmltopdf', __DIR__ . '/../tests/');

        $this->assertAttributeEquals('/bin/wkhtmltopdf', 'command', $pdf);
        $this->assertAttributeEquals(__DIR__ . '/../tests/', 'tmpDir', $pdf);
    }

    public function testSetCommandPath()
    {
        $result = $this->pdf->setCommandPath('/bin/wkhtmltopdf');

        $this->assertAttributeEquals('/bin/wkhtmltopdf', 'command', $this->pdf);
        $this->assertInstanceOf(Pdf::class, $result);
    }

    public function testLoadHtml()
    {
        $this->pdf->loadHtml('<div>foo</div>');

        $this->assertAttributeContains('.html', 'path', $this->pdf);
    }

    public function testLoadHtmlFromUrl()
    {
        $this->pdf->loadHtmlFromUrl('http://example.com/');

        $this->assertAttributeEquals('http://example.com/', 'path', $this->pdf);
    }

    public function testLoadHtmlFromFile()
    {
        $this->pdf->loadHtmlFromFile('/file.pdf');

        $this->assertAttributeEquals('/file.pdf', 'path', $this->pdf);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCreateWithEmptyPath()
    {
        $this->pdf->create();
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetInvalidCommandOption()
    {
        $this->pdf->foo();
    }

    public function testSetCommandOption()
    {
        $this->pdf->grayscale()->marginBottom('10px');

        $this->assertAttributeEquals([0 => 'grayscale', 'margin-bottom' => '10px'], 'commandOptions', $this->pdf);
    }
}