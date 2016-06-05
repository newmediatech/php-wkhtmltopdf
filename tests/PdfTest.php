<?php

namespace Converter\Tests;

use Converter\Pdf;
use League\Flysystem\Adapter\Local;

class PdfTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $pdf = new Pdf();
        
        $this->assertAttributeEquals(Pdf::DEFAULT_BIN_PATH, 'binary', $pdf);
        $this->assertAttributeEquals(null, 'fileSystem', $pdf);
        $this->assertAttributeEquals([], 'commandOptions', $pdf);

        $pdf = new Pdf('/bin/wkhtmltopdf', new Local(__DIR__));

        $this->assertAttributeEquals('/bin/wkhtmltopdf', 'binary', $pdf);
        $this->assertAttributeInstanceOf('League\\Flysystem\\Filesystem', 'fileSystem', $pdf);
    }

    public function testSetLocalStorageAdapter()
    {
        $pdf = new Pdf();
        $this->assertAttributeEquals(null, 'fileSystem', $pdf);

        $pdf->setFileStorageAdapter(new Local(__DIR__));
        $this->assertAttributeInstanceOf('League\\Flysystem\\Filesystem', 'fileSystem', $pdf);
    }

    protected function call($object, $method, $args = [])
    {
        $method = new \ReflectionMethod($object, $method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}