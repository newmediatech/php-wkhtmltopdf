<?php

namespace MediaTech\Tests;

use MediaTech\Pdf;

class PdfTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $pdf = $this->createPdf();
        
        $this->assertAttributeEquals(Pdf::DEFAULT_COMMAND_PATH, 'command', $pdf);
        $this->assertAttributeEquals([], 'commandOptions', $pdf);

        $pdf = new Pdf('/bin/wkhtmltopdf');

        $this->assertAttributeEquals('/bin/wkhtmltopdf', 'command', $pdf);
    }

    public function testSetCommandPath()
    {
        $pdf = $this->createPdf();
        $result = $pdf->setCommandPath('/bin/wkhtmltopdf');

        $this->assertAttributeEquals('/bin/wkhtmltopdf', 'command', $pdf);
        $this->assertInstanceOf(Pdf::class, $result);
    }

    private function createPdf()
    {
        return new Pdf;
    }
}