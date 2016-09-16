<?php
/**
 * CSVelte: Slender, elegant CSV for PHP.
 *
 * Inspired by Python's CSV module and Frictionless Data and the W3C's CSV
 * standardization efforts, CSVelte was written in an effort to take all the
 * suck out of working with CSV.
 *
 * @version   v0.2
 * @copyright Copyright (c) 2016 Luke Visinoni <luke.visinoni@gmail.com>
 * @author    Luke Visinoni <luke.visinoni@gmail.com>
 * @license   https://github.com/deni-zen/csvelte/blob/master/LICENSE The MIT License (MIT)
 */
namespace CSVelte\IO;

use CSVelte\Traits\IsReadable;
use CSVelte\Traits\IsWritable;
use CSVelte\Traits\IsSeekable;

use CSVelte\Contract\Readable;
use CSVelte\Contract\Writable;
use CSVelte\Contract\Seekable;

use \InvalidArgumentException;
use CSVelte\Exception\NotYetImplementedException;
use CSVelte\Exception\EndOfFileException;
use CSVelte\Exception\IOException;

/**
 * CSVelte Stream.
 *
 * Represents a stream for input/output. Implements both readable and writable
 * interfaces so that it can be passed to either ``CSVelte\Reader`` or
 * ``CSVelte\Writer``.
 *
 * @package    CSVelte
 * @subpackage CSVelte\IO
 * @copyright  (c) 2016, Luke Visinoni <luke.visinoni@gmail.com>
 * @author     Luke Visinoni <luke.visinoni@gmail.com>
 * @since      v0.2
 */
class Stream implements Readable, Writable, Seekable
{
    use IsReadable, IsWritable, IsSeekable;
    /**
     * Hash of readable/writable stream open mode types.
     *
     * Mercilessly stolen from:
     * https://github.com/guzzle/streams/blob/master/src/Stream.php
     *
     * My kudos and sincere thanks go out to Michael Dowling and Graham Campbell
     * of the guzzle/streams PHP package. Thanks for the inspiration (in some cases)
     * and the not suing me for outright theft (in this case).
     *
     * @var array Hash of readable and writable stream types
     */
    protected static $readWriteHash = [
        'read' => [
            'r' => true, 'w+' => true, 'r+' => true, 'x+' => true, 'c+' => true,
            'rb' => true, 'w+b' => true, 'r+b' => true, 'x+b' => true,
            'c+b' => true, 'rt' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a+' => true,
        ],
        'write' => [
            'w' => true, 'w+' => true, 'rw' => true, 'r+' => true, 'x+' => true,
            'c+' => true, 'wb' => true, 'w+b' => true, 'r+b' => true,
            'x+b' => true, 'c+b' => true, 'w+t' => true, 'r+t' => true,
            'x+t' => true, 'c+t' => true, 'a' => true, 'a+' => true,
        ],
    ];

    /**
     * @var resource An open stream resource
     */
    protected $stream;

    /**
     * Meta data about stream resource.
     * Just contains the return value of stream_get_meta_data.
     * @var array The return value of stream_get_meta_data
     */
    protected $meta;

    /**
     * Is stream seekable
     * @var boolean True if stream is seekable, false otherwise
     */
    protected $seekable;

    /**
     * Is stream readable
     * @var boolean True if stream is readable, false otherwise
     */
    protected $readable;

    /**
     * Is stream writable
     * @var boolean True if stream is writable, false otherwise
     */
    protected $writable;

    /**
     * Converts object/string to a usable stream
     *
     * Mercilessly stolen from:
     * https://github.com/guzzle/streams/blob/master/src/Stream.php
     *
     * My kudos and sincere thanks go out to Michael Dowling and Graham Campbell
     * of the guzzle/streams PHP package. Thanks for the inspiration (in some cases)
     * and the not suing me for outright theft (in this case).
     *
     * @param object|string The string/object to convert to a stream
     * @param array Options to pass to the newly created stream
     * @return \CSVelte\IO\Stream
     * @throws \InvalidArgumentException
     */
    public static function streamize($resource = '')
    {
        $type = gettype($resource);

        if ($type == 'string') {
            $stream = self::open('php://temp', 'r+');
            if ($resource !== '') {
                fwrite($stream, $resource);
                fseek($stream, 0);
            }
            return new self($stream);
        }

        if ($type == 'object' && method_exists($resource, '__toString')) {
            return self::streamize((string) $resource);
        }

        throw new InvalidArgumentException('Invalid resource type: ' . $type);
    }

    /**
     * Stream Object Constructor.
     *
     * Instantiates the stream object
     *
     * @param string|object|resource $stream Either a valid stream URI or an open
     *     stream resource (using fopen, fsockopen, et al.)
     * @param string file/stream open mode as passed to native php
     *     ``fopen`` function
     * @param array Stream context options array as passed to native php
     *     ``stream_context_create`` function
     * @see http://php.net/manual/en/function.fopen.php
     * @see http://php.net/manual/en/function.stream-context-create.php
     */
    public function __construct($stream, $mode = null, $context = null)
    {
        $this->setMetaData($this->stream = self::open($stream, $mode, $context));
    }

    /**
     * Stream Object Destructor.
     *
     * Closes stream connection.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Open a new stream URI and return stream resource.
     *
     * Pass in either a valid stream URI or a stream resource and this will
     * return a stream resource object.
     *
     * @param string|resource $stream Either stream URI or resource object
     * @param string $mode File/stream open mode (as passed to native php
     *     ``fopen`` function)
     * @param array $context Stream context options array as passed to native
     *     php ``stream_context_create`` function
     * @return stream resource object
     * @throws CSVelte\Exception\IOException on invalid stream uri/resource
     * @throws \InvalidArgumentException if context param is not an array
     * @see http://php.net/manual/en/function.fopen.php
     * @see http://php.net/manual/en/function.stream-context-create.php
     */
    protected static function open($stream, $mode = null, $context = null)
    {
        if (is_null($mode)) $mode = 'r+b';
        if (is_string($uri = $stream)) {
            if (is_null($context)) {
                $stream = @fopen($uri, $mode);
            } else {
                if (!is_array($context)) {
                    throw new InvalidArgumentException("Invalid argument for context. Expected array, got: " . gettype($context));
                }
                $context = stream_context_create($context);
                $stream = @fopen($uri, $mode, false, $context);
            }
            if (false === $stream) {
                throw new IOException("Invalid stream URI: " . $uri, IOException::ERR_INVALID_STREAM_URI);
            }
        }
        if (!is_resource($stream) || get_resource_type($stream) != 'stream') {
            throw new IOException("Expected stream resource, got: " . gettype($stream), IOException::ERR_INVALID_STREAM_RESOURCE);
        }
        return $stream;
    }

    /**
     * Close stream resource.
     *
     * @return boolean True on success or false on failure
     */
    public function close()
    {
        if (is_resource($this->stream)) {
            return fclose($this->stream);
        }
        return false;
    }

    /**
     * Set stream meta data via stream resource.
     *
     * Pass in stream resource to set this object's stream metadata as returned
     * by the native php function ``stream_get_meta_data``
     *
     * @param resource $stream Stream resource object
     * @return $this
     * @see http://php.net/manual/en/function.stream-get-meta-data.php
     */
    protected function setMetaData($stream)
    {
        $this->meta = stream_get_meta_data($stream);
        $this->seekable = (bool) $this->meta['seekable'];
        $this->readable = isset(self::$readWriteHash['read'][$this->meta['mode']]);
        $this->writable = isset(self::$readWriteHash['write'][$this->meta['mode']]);
        return $this;
    }

    /**
     * Get stream metadata (all or certain value).
     *
     * Get either the entire stream metadata array or a single value from it by key.
     *
     * @param string $key If set, must be one of ``stream_get_meta_data`` array keys
     * @return string|array Either a single value or whole array returned by ``stream_get_meta_data``
     * @see http://php.net/manual/en/function.stream-get-meta-data.php
     */
    public function getMetaData($key = null)
    {
        if (is_null($key)) return $this->meta;
        return (array_key_exists($key, $this->meta)) ? $this->meta[$key] : null;
    }

    /**
     * Accessor for seekability.
     *
     * Returns true if possible to seek to a certain position within this stream
     *
     * @return boolean True if stream is seekable
     */
    public function isSeekable()
    {
        return $this->seekable;
    }

    /**
     * Accessor for readability.
     *
     * Returns true if possible to read from this stream
     *
     * @return boolean True if stream is readable
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * Accessor for writability.
     *
     * Returns true if possible to write to this stream
     *
     * @return boolean True if stream is writable
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * Accessor for internal stream resource.
     *
     * Returns the internal stream resource pointer
     *
     * @return resource The open stream resource pointer
     */
    public function getResource()
    {
        return $this->stream;
    }

    /**
     * Accessor for stream URI.
     *
     * Returns the stream URI
     *
     * @return string The URI for the stream
     */
    public function getUri()
    {
        return $this->getMetaData('uri');
    }

    /**
     * Accessor for stream name.
     *
     * Alias for ``getUri()``
     *
     * @return string The name for this stream
     */
    public function getName()
    {
        return $this->getUri();
    }

    /**
     * Read $length bytes from stream.
     *
     * Reads $length bytes (number of characters) from the stream
     *
     * @param int $length Number of bytes to read from stream
     * @return string|boolean The data read from stream or false if at end of
     *     file or some other problem.
     * @throws CSVelte\Exception\IOException
     */
    public function read($length)
    {
        $this->assertIsReadable();
        if ($this->eof()) return false;
        return fread($this->stream, $length);
    }

    /**
     * Read the entire contents of file/stream.
     *
     * Read and return the entire contents of the stream.
     *
     * @param void
     * @return string The entire file contents
     * @throws CSVelte\Exception\IOException
     */
    public function getContents()
    {
        $buffer = '';
        while ($chunk = $this->read(1024)) {
            $buffer .= $chunk;
        }
        return $buffer;
    }

    /**
     * Is file pointer at the end of the stream?
     *
     * Returns true if internal pointer has reached the end of the stream.
     *
     * @return boolean True if end of stream has been reached
     */
    public function eof()
    {
        return feof($this->stream);
    }

    /**
     * Rewind pointer to beginning of stream.
     *
     * Rewinds the stream, meaning it returns the pointer to the beginning of the
     * stream as if it had just been initialized.
     *
     * @return boolean True on success
     */
    public function rewind()
    {
        return rewind($this->stream);
    }

    /**
     * Write to stream
     *
     * Writes a string to the stream (if it is writable)
     *
     * @param string $str The data to be written to the stream
     * @return int The number of bytes written to the stream
     * @throws CSVelte\Exception\IOException
     */
    public function write($str)
    {
        $this->assertIsWritable();
        return fwrite($this->stream, $str);
    }

    /**
     * Seek to position.
     *
     * Seek to a specific position within the stream (if seekable).
     *
     * @param int $offset The position to seek to
     * @param int $whence One of three native php ``SEEK_`` constants
     * @return boolean True on success false on failure
     * @throws CSVelte\Exception\IOException
     * @see http://php.net/manual/en/function.seek.php
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        $this->assertIsSeekable();
        return fseek($this->stream, $offset, $whence) === 0;
    }

}
