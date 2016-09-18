<?php
namespace CSVelteTest\IO;

use CSVelte\IO\Stream;
use CSVelte\IO\Resource;

/**
 * CSVelte\IO\Stream Tests.
 * This tests the new IO\Stream class that will be replacing CSVelte\Input\Stream
 * and CSVelte\Output\Stream
 *
 * @package   CSVelte Unit Tests
 * @copyright (c) 2016, Luke Visinoni <luke.visinoni@gmail.com>
 * @author    Luke Visinoni <luke.visinoni@gmail.com>
 * @coversDefaultClass CSVelte\IO\Stream
 */
class ResourceTest extends IOTest
{
    public function testInstantiateStreamResource()
    {
        $sr = new Resource($this->getFilePathFor('veryShort'));
        $this->assertEquals($this->getFilePathFor('veryShort'), $sr->getUri());
        $this->assertEquals("r+b", $sr->getMode());
        $this->assertTrue($sr->isLazy());
        $this->assertTrue($sr->isReadable());
        $this->assertTrue($sr->isWritable());
        $this->assertFalse($sr->isConnected());
        $this->assertTrue(is_resource($sr->getResource()));
        $this->assertTrue($sr->isConnected());
        $this->assertFalse($sr->getUseIncludePath());
        $this->assertEquals([], $sr->getContextOptions());
        $this->assertEquals([], $sr->getContextParams());
    }

    public function testOpenAndCloseResource()
    {
        $sr = new Resource($this->getFilePathFor('veryShort'));
        $this->assertFalse($sr->isConnected());
        $this->assertTrue($sr->connect());
        $this->assertTrue($sr->isConnected());
    }

    /**
     * @expectedException CSVelte\Exception\IOException
     * @expectedExceptionCode CSVelte\Exception\IOException::ERR_STREAM_CONNECTION_FAILED
     */
    public function testInstantiateStreamResourceWithBadUriThrowsException()
    {
        $sr = new Resource("I am not a uri", null, false);
    }

    /**
     * @expectedException CSVelte\Exception\IOException
     * @expectedExceptionCode CSVelte\Exception\IOException::ERR_STREAM_CONNECTION_FAILED
     */
    public function testConnectStreamResourceWithBadUriThrowsException()
    {
        $sr = new Resource("I am not a uri");
        $this->assertFalse($sr->isConnected());
        $sr->connect();
    }

    /**
     * @expectedException CSVelte\Exception\IOException
     * @expectedExceptionCode CSVelte\Exception\IOException::ERR_STREAM_CONNECTION_FAILED
     */
    public function testGetResourceStreamResourceWithBadUriThrowsException()
    {
        $sr = new Resource("I am not a uri");
        $this->assertFalse($sr->isConnected());
        $sr->getResource();
    }

    public function testInstantiateALazyResource()
    {
        $sr = new Resource($this->getFilePathFor('veryShort'), null, true);
        $this->assertFalse($sr->isConnected());
        $this->assertTrue(is_resource($sr->getResource()));
        $this->assertTrue($sr->isConnected());
    }

    public function testModeFlags()
    {
        $sr = new Resource($this->getFilePathFor('veryShort'), null, true);
        $this->assertEquals("r+b", $sr->getMode());
        $this->assertTrue($sr->isBinary());
        $this->assertFalse($sr->isText());
    }

    public function testInstantiateVariousModes()
    {
        $sr = new Resource($this->getFilePathFor('veryShort'), null, true);
        $this->assertEquals("r+b", $sr->getMode());
        $this->assertTrue($sr->isReadable());
        $this->assertTrue($sr->isWritable());
        $sr->setMode('r');
        $this->assertTrue($sr->isReadable());
        $this->assertFalse($sr->isWritable());
    }

    public function testSetContextAfterInstantiation()
    {
        $expContextOptions = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => ['foo' => 'bar', 'baz' => 'bin']
            ]
        ];
        $expContextParams = [
            "notification" => "stream_notification_callback"
        ];
        $res = new Resource('http://www.example.com/');
        $res->setContext($expContextOptions, $expContextParams);
        $res->connect();
        $meta = stream_get_meta_data($res->getResource());
        $wrapper = $meta['wrapper_data'];
        $this->assertEquals($expContextOptions, $res->getContextOptions());
        $this->assertEquals($expContextParams, $res->getContextParams());
        $this->assertEquals($expContextOptions, stream_context_get_options($res->getContext()));
        // stream_context_get_params returns an array of params AND options, not just params...
        // important little detail that tripped me up for a bit...
        $this->assertEquals(array_merge($expContextParams, ['options' => $expContextOptions]), stream_context_get_params($res->getContext()));

    }

    // @todo there should be addContextOptions() to add rather than overwrite
    public function testSetOptsAndParamsOnOpenConnectionAndThenChangeThemLater()
    {
        $res = new Resource(
            $uri = "http://www.example.com/data/foo.csv",
            $mode = 'rb',
            $lazy = false,
            $use_inc_path = false,
            $options = ['http' => ['method' => 'POST']],
            $params = ['notification' => 'some_func_callback']
        );
        $this->assertTrue($res->isConnected());
        $this->assertEquals($options, stream_context_get_options($res->getContext()));
        $this->assertEquals($params + ["options" => $options], stream_context_get_params($res->getContext()));

        // now change them...
        $res->setContextOptions($newopts = ['header' => 'Content-Type: application/x-www-form-urlencoded'], 'http');
        $res->setContextParams($newparams = ['notification' => 'some_other_func']);

        // old options and params overwritten
        $this->assertEquals($newoptions = ['http' => $options['http'] + $newopts], stream_context_get_options($res->getContext()));
        $this->assertEquals(["options" => $newoptions] + $newparams, stream_context_get_params($res->getContext()));
    }
}