<?php

/*
 * CSVelte: Slender, elegant CSV for PHP
 * Inspired by Python's CSV module and Frictionless Data and the W3C's CSV
 * standardization efforts, CSVelte was written in an effort to take all the
 * suck out of working with CSV.
 *
 * @version   {version}
 * @copyright Copyright (c) 2016 Luke Visinoni <luke.visinoni@gmail.com>
 * @author    Luke Visinoni <luke.visinoni@gmail.com>
 * @license   https://github.com/deni-zen/csvelte/blob/master/LICENSE The MIT License (MIT)
 */
namespace CSVelte;

/*
 * Library Functions
 *
 * @package CSVelte
 * @subpackage functions
 * @since v0.2.1
 */

use CSVelte\Collection\AbstractCollection;
use CSVelte\Collection\Collection;
use CSVelte\Contract\Streamable;
use CSVelte\IO\IteratorStream;
use CSVelte\IO\Stream;
use CSVelte\IO\StreamResource;
use InvalidArgumentException;

use Iterator;

/**
 * Stream - streams various types of values and objects.
 *
 * You can pass a string, or an iterator, or an object with a __toString()
 * method to this function and it will find the best possible way to stream the
 * data from that object.
 *
 * @param mixed $obj The item you want to stream
 *
 * @throws InvalidArgumentException
 *
 * @return Streamable
 *
 * @since v0.2.1
 */
function streamize($obj = '')
{
    if ($obj instanceof Streamable) {
        return $obj;
    }

    if ($obj instanceof StreamResource) {
        return $obj();
    }

    if (is_resource($obj) && get_resource_type($obj) == 'stream') {
        return new Stream(new StreamResource($obj));
    }

    if ($obj instanceof Iterator) {
        return new IteratorStream($obj);
    }

    if (is_object($obj) && method_exists($obj, '__toString')) {
        $obj = (string) $obj;
    }
    if (is_string($obj)) {
        $stream = Stream::open('php://temp', 'r+');
        if ($obj !== '') {
            $res = $stream->getResource();
            fwrite($res->getHandle(), $obj);
            fseek($res->getHandle(), 0);
        }

        return $stream;
    }

    throw new InvalidArgumentException(sprintf(
        'Invalid argument type for %s: %s',
        __FUNCTION__,
        gettype($obj)
    ));
}

/**
 * StreamResource factory.
 *
 * This method is just a shortcut to create a stream resource object using
 * a stream URI string.
 *
 * @param string         $uri     A stream URI
 * @param string         $mode    The access mode string
 * @param array|resource $context An array or resource with stream context options
 * @param bool           $lazy    Whether to lazy-open
 *
 * @return StreamResource
 *
 * @since v0.2.1
 */
function stream_resource(
    $uri,
    $mode = null,
    $context = null,
    $lazy = true
) {
    if (is_array($context)) {
        if (!isset($context['options'])) {
            $context['options'] = [];
        }
        if (!isset($context['params'])) {
            $context['params'] = [];
        }
        $context = stream_context_create($context['options'], $context['params']);
    }
    $res = (new StreamResource($uri, $mode, null, true))
        ->setContextResource($context);
    if (!$lazy) {
        $res->connect();
    }

    return $res;
}

/**
 * Stream factory.
 *
 * This method is just a shortcut to create a stream object using a URI.
 *
 * @param string         $uri     A stream URI to open
 * @param string         $mode    The access mode string
 * @param array|resource $context An array or stream context resource of options
 * @param bool           $lazy    Whether to lazy-open
 *
 * @return Stream
 *
 * @since v0.2.1
 */
function stream(
    $uri,
    $mode = null,
    $context = null,
    $lazy = true
) {
    $res = stream_resource($uri, $mode, $context, $lazy);

    return $res();
}

/**
 * "Taste" a stream object.
 *
 * Pass any class that implements the "Streamable" interface to this function
 * to auto-detect "flavor" (formatting attributes).
 *
 * @param Contract\Streamable Any streamable class to analyze
 *
 * @return Flavor A flavor representing stream's formatting attributes
 *
 * @since v0.2.1
 */
function taste(Streamable $str)
{
    $taster = new Taster($str);

    return $taster();
}

/**
 * Does dataset being streamed by $str have a header row?
 *
 * @param Contract\Streamable $str Stream object
 *
 * @return bool Whether stream dataset has header
 *
 * @since v0.2.1
 */
function taste_has_header(Streamable $str)
{
    $taster = new Taster($str);
    $flv    = $taster();

    return $taster->lickHeader(
        $flv->delimiter,
        $flv->lineTerminator
    );
}

/**
 * Collection factory.
 *
 * Simply an alias to (new Collection($in)). Allows for a little more concise and
 * simpler instantiation of a collection. Also I plan to eventually support
 * additional input types that will make this function more flexible and forgiving
 * than simply instantiating a Collection object, but for now the two are identical.
 *
 * @param array|Iterator $in Either an array or an iterator of data
 *
 * @return AbstractCollection A collection object containing data from $in
 *
 * @since v0.2.1
 * @see AbstractCollection::__construct() (alias)
 */
function collect($in = null)
{
    return Collection::factory($in);
}

/**
 * Invoke a callable and return result.
 *
 * Pass in a callable followed by whatever arguments you want passed to
 * it and this function will invoke it with your arguments and return
 * the result.
 *
 * @param callable $callback The callback function to invoke
 * @param array ...$args The args to pass to your callable
 *
 * @return mixed The result of your invoked callable
 *
 * @since v0.2.1
 */
function invoke(callable $callback, ...$args)
{
    return $callback(...$args);
}

/**
 * Determine if data is traversable.
 *
 * Pass in any variable and this function will tell you whether or not it
 * is traversable. Basically this just means that it is either an array or an iterator.
 * This function was written simply because I was tired of if statements that checked
 * whether a variable was an array or a descendant of \Iterator. So I wrote this guy.
 *
 * @param mixed $input The variable to determine traversability
 *
 * @return bool True if $input is an array or an Iterator
 */
function is_traversable($input)
{
    return is_array($input) || $input instanceof Iterator;
}
