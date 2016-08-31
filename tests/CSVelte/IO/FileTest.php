<?php
namespace CSVelteTest\IO;

use CSVelte\IO\File;
use org\bovigo\vfs\vfsStream;
/**
 * CSVelte\IO\File Tests.
 * This tests the new IO\File class that will be replacing CSVelte\Input\File and
 * CSVelte\Output\File.
 *
 * @package   CSVelte Unit Tests
 * @copyright (c) 2016, Luke Visinoni <luke.visinoni@gmail.com>
 * @author    Luke Visinoni <luke.visinoni@gmail.com>
 * @coversDefaultClass CSVelte\IO\File
 */
class FileTest extends IOTest
{
    /**
     * @covers ::__construct()
     */
    public function testInstantiateIOFileCreatesFile()
    {
        $filename = $this->root->url() . '/deleteme.csv';
        $this->assertFileNotExists($filename);
        $file = new File($filename);
        $this->assertFileExists($filename);
    }

    /**
     * @expectedException CSVelte\Exception\FileNotFoundException
     * @expectedExceptionCode 1
     * @covers ::__construct()
     */
    public function testInstantiateIOFileInNonExistantFileThrowsExceptionIfCreateOptionIsFalse()
    {
        $filename = $this->root->url() . '/deletemetoo.csv';
        $file = new File($filename, ['create' => false]);
    }

    /**
     * @expectedException CSVelte\Exception\FileNotFoundException
     * @expectedExceptionCode 2
     * @covers ::__construct()
     */
    public function testInstantiateIOFileInNonExistantDirectoryThrowsException()
    {
        $filename = $this->root->url() . '/makethisdir/deleteme.csv';
        $file = new File($filename);
    }

    /**
     * @covers ::__construct()
     */
    public function testInsantiateIOFileInNonExistantDirectoryCreatesDirectoryAndFileIfParentsOptionIsTrue()
    {
        $filename = $this->root->url() . '/makethisdir/deleteme.csv';
        $dirname = dirname($filename);
        $this->assertFileNotExists($dirname);
        $file = new File($filename, ['parents' => true]);
        $this->assertFileExists($dirname);
    }

    /**
     * @covers ::__construct()
     */
    public function testInstantiateIOFileModeDefaultsTo0644()
    {
        $filename = $this->root->url() . '/deleteme.csv';
        $file = new File($filename);
        $perms = substr(sprintf('%o', fileperms($filename)), -4);
        $this->assertEquals($expected = '0644', $perms);
    }

    /**
     * @covers ::__construct()
     */
    public function testInstantiateIOFileAllowsSettingModeForFile()
    {
        $filename = $this->root->url() . '/deleteme.csv';
        $file = new File($filename, ['mode' => 0777]);
        $perms = substr(sprintf('%o', fileperms($filename)), -4);
        $this->assertEquals($expected = '0777', $perms);
    }

    /**
     * @covers ::__construct()
     */
    public function testInstantiateIOFileModeDefaultsTo0644ForCreatedParentDirs()
    {
        $filename = $this->root->url() . '/makethisdir/deleteme.csv';
        $file = new File($filename, ['parents' => true]);
        $perms = substr(sprintf('%o', fileperms(dirname($filename))), -4);
        $this->assertEquals($expected = '0644', $perms);
    }

    /**
     * @covers ::__construct()
     */
    public function testInstantiateIOFileAllowsSettingModeForCreatedParentDirs()
    {
        $filename = $this->root->url() . '/makethisdir/deleteme.csv';
        $file = new File($filename, ['mode' => 0777, 'parents' => true]);
        $perms = substr(sprintf('%o', fileperms(dirname($filename))), -4);
        $this->assertEquals($expected = '0777', $perms);
    }

    /**
     * @covers ::fread()
     */
    public function testFreadGetsCorrectNumberOfChars()
    {
        $file = new File($this->getFilePathFor('commaNewlineHeader'));
        $this->assertEquals("Bank Name,City,ST,CERT,Ac", $file->fread(25));
    }

    /**
     * @covers ::fread()
     */
    public function testFreadGetsAllCharsIfMoreAreRequestedThanAreAvailable()
    {
        $file = new File($this->getFilePathFor('veryShort'));
        $this->assertEquals("foo,bar,baz\nbin,boz,bork\nlib,bil,ilb\n", $file->fread(250));
    }

    /**
     * @covers ::fgets()
     */
    public function testFgetsReadsNextLineWithoutTrailingNewline()
    {
        $file = new File($this->getFilePathFor('veryShort'));
        $this->assertEquals("foo,bar,baz", $file->fgets());
        $this->assertEquals("bin,boz,bork", $file->fgets());
        $this->assertEquals("lib,bil,ilb", $file->fgets());
    }

    /**
     * @covers ::fgets()
     * @expectedException \RuntimeException
     */
    public function testFgetsThrowsRuntimeExceptionIfEofReached()
    {
        $file = new File($this->getFilePathFor('veryShort'));
        $this->assertEquals("foo,bar,baz", $file->fgets());
        $this->assertEquals("bin,boz,bork", $file->fgets());
        $this->assertEquals("lib,bil,ilb", $file->fgets());
        $file->fgets(); // this should trigger an exception
    }

    /**
     * @covers ::eof()
     */
    public function testEofReturnsFalseUntilEofIsReached()
    {
        $file = new File($this->getFilePathFor('veryShort'));
        $this->assertFalse($file->eof());
        $this->assertEquals("foo,bar,baz", $file->fgets());
        $this->assertFalse($file->eof());
        $this->assertEquals("bin,boz,bork", $file->fgets());
        $this->assertFalse($file->eof());
        $this->assertEquals("lib,bil,ilb", $file->fgets());
        $this->assertTrue($file->eof());
    }

    /**
     * @covers ::fgets()
     */
    public function testFgetsReadsLinesWithoutRespectToQuotedNewlines()
    {
        $file = new File($this->getFilePathFor('shortQuotedNewlines'));
        $this->assertEquals("foo,bar,baz", $file->fgets());
        $this->assertEquals("bin,\"boz,bork", $file->fgets());
        $this->assertEquals("lib,bil,ilb\",bon", $file->fgets());
        $this->assertEquals("bib,bob,\"boob", $file->fgets());
        $this->assertEquals("boober\"", $file->fgets());
        $this->assertEquals("cool,pool,wool", $file->fgets());
    }

    /**
     * @covers ::write()
     */
    public function testCreateNewFileAndWriteToIt()
    {
        $data = $this->getFileContentFor('veryShort');
        $file = new File($fn = $this->root->url() ."/tempfile1.csv", ['open_mode' => 'w']);
        $this->assertEquals(strlen($data), $file->write($data));
        $this->assertEquals($data, file_get_contents($fn));
    }

    /**
     * @covers ::write()
     */
    public function testAppendFileWrite()
    {
        $file = new File($fn = $this->getFilePathFor('shortQuotedNewlines'), ['open_mode' => 'a']);
        $data = "\"foo, bar\",boo,far\n";
        $this->assertEquals(strlen($data), $file->write($data));
        $this->assertEquals(
            "foo,bar,baz\nbin,\"boz,bork\nlib,bil,ilb\",bon\nbib,bob,\"boob\nboober\"\ncool,pool,wool\n" . $data,
            file_get_contents($fn)
        );
    }

    /**
     * @covers ::write()
     */
    public function testFileOverWrite()
    {
        $file = new File($fn = $this->getFilePathFor('shortQuotedNewlines'), ['open_mode' => 'w']);
        $data = "\"foo, bar\",boo,far\n";
        $this->assertEquals(strlen($data), $file->write($data));
        $this->assertEquals(
            $data,
            file_get_contents($fn)
        );
    }
}
