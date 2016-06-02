<?php

namespace Converter\Tests;

use Converter\Pdf;

class PdfTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $pdf = new Pdf();
        
        $this->assertAttributeEquals(Pdf::DEFAULT_UTILITY_PATH, 'binUtilityPath', $pdf);
    }

    protected function call($object, $method, $args = [])
    {
        $method = new \ReflectionMethod($object, $method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}