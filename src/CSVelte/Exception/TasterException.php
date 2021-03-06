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
namespace CSVelte\Exception;

/**
 * CSVelte\Exception\TasterException
 * Used by CSVelte\Taster to report errors in "flavor tasting" (format inference).
 *
 * @package   CSVelte\Exception
 *
 * @copyright (c) 2016, Luke Visinoni <luke.visinoni@gmail.com>
 * @author    Luke Visinoni <luke.visinoni@gmail.com>
 */
class TasterException extends CSVelteException
{
    /**
     * Could not determine delimiter.
     */
    const ERR_DELIMITER = 1;

    /**
     * Could not determine quote and delimiter at the same time.
     */
    const ERR_QUOTE_AND_DELIM = 2;

    /**
     * Invalid data sample.
     */
    const ERR_INVALID_SAMPLE = 3;
}
