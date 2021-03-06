<?php
namespace CSVelteTest;

use CSVelte\Flavor;
use CSVelte\IO\StreamResource;
use CSVelte\IO\Stream;
use \SplFileObject;
use function
    CSVelte\taste_has_header,
    CSVelte\stream,
    CSVelte\stream_resource,
    CSVelte\streamize,
    CSVelte\taste,
    CSVelte\collect,
    CSVelte\invoke;

/**
 * CSVelte functions tests
 *
 * @package   CSVelte Unit Tests
 * @copyright (c) 2016, Luke Visinoni <luke.visinoni@gmail.com>
 * @author    Luke Visinoni <luke.visinoni@gmail.com>
 */
class FunctionsTest extends UnitTestCase
{
    public function testStreamizeReturnsStreamObjectForOpenStreamResource()
    {
        $handle = fopen($this->getFilePathFor('veryShort'), $mode = 'r+b');
        $this->assertInstanceOf(Stream::class, $stream = streamize($handle));
        $this->assertEquals($handle, $stream->getResource()->getHandle());
        $this->assertEquals($mode, $stream->getResource()->getMode());
    }

    public function testStreamizeReturnsStreamObjectForPHPString()
    {
        $string = "All your base are belong to us";
        $this->assertInstanceOf(Stream::class, $stream = streamize($string));
        $res = $stream->getResource();
        $this->assertTrue(is_resource($res->getHandle()));
        $this->assertTrue($res->isConnected());
        $this->assertEquals(strlen($string), $stream->getSize());
        $this->assertEquals($string, (string) $stream);
        $this->assertEquals('r+', $stream->getResource()->getMode());
    }

    public function testStreamizeReturnsStreamObjectForPHPStringableObject()
    {
        // Create a stub for non-existant StringableClass.
        $csv_obj = $this->getMockBuilder('StringableClass')
                        ->setMethods(['__toString'])
                        ->getMock();

        // configure the stub to return test string...
        $string = "All your base are belong to us";
        $csv_obj->method('__toString')
             ->willReturn($string);

        // test it...
        $this->assertInstanceOf(Stream::class, $stream = streamize($csv_obj));
        $res = $stream->getResource();
        $this->assertTrue(is_resource($res->getHandle()));
        $this->assertTrue($res->isConnected());
        $this->assertEquals(strlen($string), $stream->getSize());
        $this->assertEquals($string, (string) $stream);
        $this->assertEquals('r+', $stream->getResource()->getMode());
    }

    // this will work for any Iterator class, not just SplFileObject
    public function testStreamizeReturnsStreamObjectForSplFileObject()
    {
        $file_obj = new SplFileObject($this->getFilePathFor('commaNewlineHeader'));
        $stream = streamize($file_obj);
        $this->assertEquals("Bank Name,City,ST,CERT,Acquiring", $stream->read(32));
        $this->assertEquals(" Institution,Closing Date,Update", $stream->read(32));
        $this->assertEquals("d Date\nFirst CornerStone Bank,\"K", $stream->read(32));
    }

    public function testStreamFunctionReturnsStream()
    {
        $stream = stream($this->getFilePathFor('veryShort'));
        $this->assertInstanceOf(Stream::class, $stream);
    }

    public function testStreamFunctionReturnsLazyStreamByDefault()
    {
        $stream = stream($this->getFilePathFor('veryShort'));
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertTrue($stream->getResource()->isLazy());
        $this->assertFalse($stream->getResource()->isConnected());
        $this->assertEquals("vfs://root/testfiles/veryShort.csv", $stream->getUri());
        $this->assertEquals("foo,b", $stream->read(5));
    }

    public function testStreamFunctionReturnsOpenStreamWhenRequested()
    {
        $stream = stream($this->getFilePathFor('veryShort'), 'r+b', null, false);
        $this->assertInstanceOf(Stream::class, $stream);
        // @todo this is a bug, needs to be fixed
        //$this->assertFalse($stream->getResource()->isLazy());
        $this->assertTrue($stream->getResource()->isConnected());
        $this->assertEquals("vfs://root/testfiles/veryShort.csv", $stream->getUri());
        $this->assertEquals("foo,b", $stream->read(5));
    }

    public function testTasteFunctionIsAliasForTasterInvoke()
    {
        $stream = Stream::open($this->getFilePathFor('headerDoubleQuote'));
        $flavor = taste($stream);
        $this->assertInstanceOf(Flavor::class, $flavor);
        $this->assertEquals(",", $flavor->delimiter);
        $this->assertEquals("\n", $flavor->lineTerminator);
        $this->assertEquals(Flavor::QUOTE_MINIMAL, $flavor->quoteStyle);
        $this->assertTrue($flavor->doubleQuote);
        $this->assertEquals('"', $flavor->quoteChar);
    }

    public function testTasterInvokeWithSplFileObject()
    {
        $fileObj = new SplFileObject($this->getFilePathFor('headerTabSingleQuotes'));
        $flavor = taste(streamize($fileObj));
        $this->assertInstanceOf(Flavor::class, $flavor);
        $this->assertEquals("\t", $flavor->delimiter);
        $this->assertEquals("\n", $flavor->lineTerminator);
        $this->assertEquals(Flavor::QUOTE_MINIMAL, $flavor->quoteStyle);
        $this->assertTrue($flavor->doubleQuote);
        $this->assertEquals("'", $flavor->quoteChar);
    }

    public function testHasHeaderFunction()
    {
        $stream = Stream::open($this->getFilePathFor('veryShort'));
        $this->assertFalse(taste_has_header($stream));

        $stream = Stream::open($this->getFilePathFor('shortQuotedNewlines'));
        $this->assertFalse(taste_has_header($stream));

        $stream = Stream::open($this->getFilePathFor('commaNewlineHeader'));
        $this->assertTrue(taste_has_header($stream));

        $stream = Stream::open($this->getFilePathFor('headerDoubleQuote'));
        $this->assertTrue(taste_has_header($stream));

        $stream = Stream::open($this->getFilePathFor('headerTabSingleQuotes'));
        $this->assertTrue(taste_has_header($stream));

        $stream = Stream::open($this->getFilePathFor('noHeaderCommaNoQuotes'));
        $this->assertFalse(taste_has_header($stream));

        $stream = Stream::open($this->getFilePathFor('noHeaderCommaQuoteAll'));
        $this->assertFalse(taste_has_header($stream));

        $stream = Stream::open($this->getFilePathFor('headerCommaQuoteNonnumeric'));
        $this->assertTrue(taste_has_header($stream));

        $stream = Stream::open($this->getFilePathFor('noHeaderCommaNoQuotes'));
        $this->assertFalse(taste_has_header($stream));

        $stream = Stream::open($this->getFilePathFor('noHeaderCommaNoQuotes'));
        $this->assertFalse(taste_has_header($stream));

        $stream = Stream::open($this->getFilePathFor('noHeaderCommaNoQuotes'));
        $this->assertFalse(taste_has_header($stream));
    }

    public function testStreamResourceFunctionReturnsResourceObject()
    {
        $res = stream_resource(
            $this->getFilePathFor('headerDoubleQuote'),
            'c+b',
            $ctxt = stream_context_create($ctxarr = [
                'vfs' => ['foo' => 'bar']
            ])
        );
        $this->assertInstanceOf(StreamResource::class, $res);
        $this->assertFalse($res->isConnected());
        $this->assertTrue($res->isLazy());
        $this->assertEquals($ctxt, $res->getContext());
        $this->assertEquals($ctxarr, stream_context_get_options($res->getContext()));
    }

    public function testStreamResourceFunctionAcceptsArray()
    {
        $res = stream_resource(
            $this->getFilePathFor('headerDoubleQuote'),
            'c+b',
            [
                'options' => [
                    'http' => ['method' => 'post']
                ],
                'params' => [
                ]
            ]
        );
        $this->assertInstanceOf(StreamResource::class, $res);
    }

    public function testStreamResourceFunctionAcceptsArrayWithoutExpectedKeys()
    {
        $res = stream_resource(
            $this->getFilePathFor('headerDoubleQuote'),
            'c+b',
            [
                'params' => [
                ]
            ]
        );
        $this->assertInstanceOf(StreamResource::class, $res);
        $res = stream_resource(
            $this->getFilePathFor('headerDoubleQuote'),
            'c+b',
            [
                'options' => [
                    'http' => ['method' => 'post']
                ],
            ]
        );
        $this->assertInstanceOf(StreamResource::class, $res);
        $this->assertEquals([
            'http' => [
                'method' => 'post'
            ]
        ], stream_context_get_options($res->getContext()));
        $res = stream_resource(
            $this->getFilePathFor('headerDoubleQuote'),
            'c+b'
        );
        $this->assertInstanceOf(StreamResource::class, $res);
    }

    public function testStreamResourceInvokeReturnsAStreamObject()
    {
        $res = stream_resource(
            $this->getFilePathFor('headerDoubleQuote'),
            'c+b',
            $ctxt = stream_context_create($ctxarr = [
                'vfs' => ['foo' => 'bar']
            ])
        );
        $this->assertInstanceOf(StreamResource::class, $res);
        $this->assertInstanceOf(Stream::class, $res());
    }

    public function testCollectionFactoryFunctionUsingArray()
    {
        $coll = collect($arr = [0,1,2,3,4,5,6,7,8,9]);
        $this->assertEquals($arr, $coll->toArray());
    }

    public function testCollectFluidMethods()
    {
        $coll = collect($arr = [
            'f' => 'a',
            1 => '',
            'a' => '',
            2 => 'a',
            3 => 'foobar'
        ])->unique();
        $this->assertEquals(['f' => 'a', 1 => '', 3 => 'foobar'], $coll->toArray());
    }

    // // @todo Create a collection object that works on a string so that you
    // // can call a function for every character in a string and various other
    // // functionality
    // public function testCollectFunctionAcceptsString()
    // {
    //
    // }

    public function testGetValueAcceptsCallbackAndVariadicArguments()
    {
        $this->assertEquals('Hello, Luke Visinoni!', invoke(function($first, $last) {
            return "Hello, {$first} {$last}!";
        }, 'Luke', 'Visinoni'));
    }

    public function testStreamizeAcceptsIOResourceObject()
    {
        $reg = new StreamResource($this->getFilePathFor('veryShort'), null, false);
        $this->assertTrue($reg->isConnected());
        $regstream = streamize($reg);
        $this->assertInstanceOf(Stream::class, $regstream);
        $this->assertTrue($reg->isConnected());

        $lazy = new StreamResource($this->getFilePathFor('veryShort'), null, true);
        $this->assertFalse($lazy->isConnected());
        $lazystream = streamize($lazy);
        $this->assertInstanceOf(Stream::class, $lazystream);
        $this->assertFalse($lazy->isConnected());
        $this->assertEquals("foo,bar,ba", $lazystream->read(10));
        $this->assertTrue($lazy->isConnected());
    }

}
