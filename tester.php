<?php
declare(strict_types=1);
require('vendor/autoload.php');
require('htmleat.php');

use PHPUnit\Framework\TestCase;


// run using cli command:
//./vendor/bin/phpunit --verbose --colors="always" --testdox --bootstrap vendor/autoload.php tester.php


class htmleatTest extends TestCase
{
    protected $testfiles;

    function setUp()
    {
        $this->testfiles = ['simple.html'];
        $this->path      = "/var/www/html/htmleat/";
    }

    
    public function testCannotStartWithNoFile()
    {
        $this->expectExceptionMessage("No file passed");
        $eater = new HTMLeat();
    }

    public function testCannotStartWithMissingFile()
    {
        $target = $this->path . "nonexistantFolder/noFile.html";
        $this->expectExceptionMessage("File {$target} not found");
        $eater = new HTMLeat($target);
    }
    
    public function testCanOpenFileAndRead()
    {
        //runs on assumption that testfile has <body> tag
        $target = $this->path . "testfiles/" . $this->testfiles[0];

        $eater = new HTMLeat($target);
        $raw   = $eater->getHTMLString();

        $this->assertContains("body", $raw);
    }

    public function testNoHTMLTag()
    {
        //runs on assumption that testfile doesnt have <body> tag
        $target = $this->path . "composer.json";

        $eater = new HTMLeat($target);
        $raw   = $eater->getHTMLString();

        $this->assertNotContains("body", $raw);

        $eater->read();
    }
}