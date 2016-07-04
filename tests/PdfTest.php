<?php

namespace Converter\Tests;

use Converter\Pdf;

class PdfTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $pdf = new Pdf();
        
        $this->assertAttributeEquals(Pdf::DEFAULT_BIN_PATH, 'binary', $pdf);
        $this->assertAttributeEquals([], 'commandOptions', $pdf);

        $pdf = new Pdf('/bin/wkhtmltopdf');

        $this->assertAttributeEquals('/bin/wkhtmltopdf', 'binary', $pdf);
    }
}