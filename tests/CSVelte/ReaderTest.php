<?php
/**
 * CSVelte: Slender, elegant CSV for PHP
 *
 * Inspired by Python's CSV module and Frictionless Data and the W3C's CSV
 * standardization efforts, CSVelte was written in an effort to take all the
 * suck out of working with CSV.
 *
 * @copyright Copyright (c) 2018 Luke Visinoni
 * @author    Luke Visinoni <luke.visinoni@gmail.com>
 * @license   See LICENSE file (MIT license)
 */
namespace CSVelteTest;

use CSVelte\Dialect;
use CSVelte\Reader;

use function CSVelte\to_stream;
use function Noz\collect;

class ReaderTest extends UnitTestCase
{
    public function testInstantiateReaderWithoutDialectUsesDefault()
    {
        $source = fopen($this->getFilePathFor('veryShort'), 'r+');
        $reader = new Reader(to_stream($source));
        $this->assertInstanceOf(Dialect::class, $reader->getDialect());
    }

    public function testInstantiateReaderWithCustomDialectUsesCustomDialect()
    {
        $source = fopen($this->getFilePathFor('veryShort'), 'r+');
        $dialect = new Dialect([
            'header' => false,
        ]);
        $reader = new Reader(to_stream($source), $dialect);
        $this->assertInstanceOf(Dialect::class, $reader->getDialect());
        $this->assertSame($dialect, $reader->getDialect());
        $this->assertFalse($reader->getDialect()->hasHeader());
    }

    public function testSetDialectDoesTheSameAsSettingItInConstructor()
    {
        $source = fopen($this->getFilePathFor('veryShort'), 'r+');
        $dialect = new Dialect([
            'header' => false,
        ]);
        $reader = new Reader(to_stream($source));
        $this->assertInstanceOf(Dialect::class, $reader->getDialect());
        $this->assertNotSame($dialect, $reader->getDialect());
        $this->assertTrue($reader->getDialect()->hasHeader());
        $reader->setDialect($dialect);
        $this->assertSame($dialect, $reader->getDialect());
        $this->assertFalse($reader->getDialect()->hasHeader());
    }

    public function testSetDialectRewindsAndResetsReader()
    {
        $source = fopen($this->getFilePathFor('commaNewlineHeader'), 'r+');
        $dialect = new Dialect([
            'header' => false,
        ]);
        $reader = new Reader(to_stream($source), $dialect);
        $this->assertSame([
            0 => 'Bank Name',
            1 => 'City',
            2 => 'ST',
            3 => 'CERT',
            4 => 'Acquiring Institution',
            5 => 'Closing Date',
            6 => 'Updated Date'
        ], $reader->current());

        $newdialect = new Dialect(['header' => true]);
        $reader->setDialect($newdialect);
        $this->assertSame([
            'Bank Name' => 'First CornerStone Bank',
            'City' => "King of\nPrussia",
            'ST' => 'PA',
            'CERT' => '35312',
            'Acquiring Institution' => 'First-Citizens Bank & Trust Company',
            'Closing Date' => '6-May-16',
            'Updated Date' => '25-May-16'
        ], $reader->current());
    }

    public function testFetchRowReturnsCurrentRowAndAdvancesPointerToNextLine()
    {
        $source = to_stream(fopen($this->getFilePathFor('commaNewlineHeader'), 'r+'));
        $reader = new Reader($source);
        $this->assertEquals(0, $reader->key());
        $this->assertSame([
            'Bank Name' => 'First CornerStone Bank',
            'City' => "King of\nPrussia",
            'ST' => 'PA',
            'CERT' => '35312',
            'Acquiring Institution' => 'First-Citizens Bank & Trust Company',
            'Closing Date' => '6-May-16',
            'Updated Date' => '25-May-16'
        ], $reader->getRow());
        $this->assertEquals(1, $reader->key());
        $this->assertSame([
            'Bank Name' => 'Trust Company Bank',
            'City' => 'Memphis',
            'ST' => 'TN',
            'CERT' => '9956',
            'Acquiring Institution' => 'The Bank of Fayette County',
            'Closing Date' => '29-Apr-16',
            'Updated Date' => '25-May-16'
        ], $reader->getRow());
        $this->assertEquals(2, $reader->key());
        $this->assertSame([
            'Bank Name' => 'North Milwaukee State Bank',
            'City' => 'Milwaukee',
            'ST' => 'WI',
            'CERT' => '20364',
            'Acquiring Institution' => 'First-Citizens Bank & Trust Company',
            'Closing Date' => '11-Mar-16',
            'Updated Date' => '16-Jun-16'
        ], $reader->getRow());
        $this->assertEquals(3, $reader->key());
    }

    public function testGetRowReturnsFalseIfAtEndOfInput()
    {
        $source = to_stream(fopen($this->getFilePathFor('commaNewlineHeader'), 'r+'));
        $reader = new Reader($source);
        $source->seek($source->getSize());
        $this->assertFalse($reader->getRow());
    }

    public function testGetRowUsingOffsetReturnsRowAtGivenOffset()
    {
        $source = to_stream(fopen($this->getFilePathFor('commaNewlineHeader'), 'r+'));
        $reader = new Reader($source);
        $this->assertSame([
            "Bank Name" => "Premier Bank",
            "City" => "Denver",
            "ST" => "CO",
            "CERT" => "34112",
            "Acquiring Institution" => "United Fidelity \r\n \r \r \n \r\n Bank, fsb",
            "Closing Date" => "10-Jul-15",
            "Updated Date" => "17-Dec-15"
        ], $reader->getRow(5));
        $this->assertSame([
            "Bank Name" => "First National Bank of Crestview",
            "City" => "Crestview",
            "ST" => "FL",
            "CERT" => "17557",
            "Acquiring Institution" => "First NBC Bank",
            "Closing Date" => "16-Jan-15",
            "Updated Date" => "15-Jan-16"
        ], $reader->getRow(10));
        $this->assertSame([
            "Bank Name" => "Millennium Bank, National\n Association",
            "City" => "Sterling",
            "ST" => "VA",
            "CERT" => "35096",
            "Acquiring Institution" => "WashingtonFirst Bank",
            "Closing Date" => "28-Feb-14",
            "Updated Date" => "3-Mar-15"
        ], $reader->getRow(25));
    }

    public function testGetColumnReturnsColumn()
    {
        $source = to_stream(fopen($this->getFilePathFor('commaNewlineHeader'), 'r+'));
        $reader = new Reader($source);
        $column = $reader->getColumn('CERT');
        $this->assertSame([
            0 => '35312',
            1 => '9956',
            2 => '20364',
            3 => '35156',
            4 => '35259',
            5 => '34112',
            6 => '57772',
            7 => '32102',
            8 => '33938',
            9 => '20290',
            10 => '17557',
            11 => '34983',
            12 => '34738',
            13 => '916',
            14 => '4862',
            15 => '28462',
            16 => '58125',
            17 => '12483',
            18 => '21793',
            19 => '10450',
            20 => '32368',
            21 => '32284',
            22 => '57866',
            23 => '15062',
            24 => '58531',
            25 => '35096',
            26 => '34296',
            27 => '17967',
            28 => '5732'
        ], $column);
    }

    // @see https://github.com/nozavroni/csvelte/issues/190
    public function testBugFixReaderSplitsFieldsIncorrectlyWhenHasSpacesAroundDelimiter()
    {
        $csv = "\"policyID\",\"statecode\",\"county\",\"eq_site_limit\",\"hu_site_limit\",\"fl_site_limit\",\"fr_site_limit\", \"tiv_2011\",\"tiv_2012\",\"eq_site_deductible\",\"hu_site_deductible\",\"fl_site_deductible\",\"fr_site_deductible\",\"point_latitude\",\"point_longitude\",\"line\",\"construction\",\"point_granularity\"\n119736, \"FL\" ,\"CLAY COUNTY\",498960,498960,498960,498960,498960,792148.9,0,9979.2,0,0,30.102261,-81.711777,\"Residential\",\"Masonry\",1\n";
        $reader = new Reader(to_stream($csv));
        $rows = $reader->toArray();
        $this->assertEquals('tiv_2011', array_keys($rows[0])[7]);
        $this->assertEquals('FL', $rows[0]['statecode']);
    }

    // @see https://github.com/nozavroni/csvelte/issues/191
    public function testBugFixReaderIgnoresLastLineIfNoFinalLineEnding()
    {
        $csv = "\"newlineId\",\"statecode\",\"county\",\"eq_site_limit\",\"hu_site_limit\",\"fl_site_limit\",\"fr_site_limit\",\"tiv_2011\",\"tiv_2012\",\"eq_site_deductible\",\"hu_site_deductible\",\"fl_site_deductible\",\"fr_site_deductible\",\"point_latitude\",\"point_longitude\",\"line\",\"construction\",\"point_granularity\"\n119736,\"FL\",\"CLAY COUNTY\",498960,498960,498960,498960,498960,792148.9,0,9979.2,0,0,30.102261,-81.711777,\"Residential\",\"Masonry\",1\n119736,\"FL\",\"CLAY COUNTY\",498960,498960,498960,498960,498960,792148.9,0,9979.2,0,0,30.102261,-81.711777,\"Residential\",\"Masonry\",2";
        $reader = new Reader(to_stream($csv));
        $rows = $reader->toArray();
        $this->assertCount(2, $rows);
        $this->assertSame([
            0 => [
                'newlineId' => '119736',
                'statecode' => 'FL',
                'county' => 'CLAY COUNTY',
                'eq_site_limit' => '498960',
                'hu_site_limit' => '498960',
                'fl_site_limit' => '498960',
                'fr_site_limit' => '498960',
                'tiv_2011' => '498960',
                'tiv_2012' => '792148.9',
                'eq_site_deductible' => '0',
                'hu_site_deductible' => '9979.2',
                'fl_site_deductible' => '0',
                'fr_site_deductible' => '0',
                'point_latitude' => '30.102261',
                'point_longitude' => '-81.711777',
                'line' => 'Residential',
                'construction' => 'Masonry',
                'point_granularity' => '1'
            ],
            1 => [
                'newlineId' => '119736',
                'statecode' => 'FL',
                'county' => 'CLAY COUNTY',
                'eq_site_limit' => '498960',
                'hu_site_limit' => '498960',
                'fl_site_limit' => '498960',
                'fr_site_limit' => '498960',
                'tiv_2011' => '498960',
                'tiv_2012' => '792148.9',
                'eq_site_deductible' => '0',
                'hu_site_deductible' => '9979.2',
                'fl_site_deductible' => '0',
                'fr_site_deductible' => '0',
                'point_latitude' => '30.102261',
                'point_longitude' => '-81.711777',
                'line' => 'Residential',
                'construction' => 'Masonry',
                'point_granularity' => '2'
            ]
        ], $rows);
    }

    /** BEGIN: SPL implementation method tests */

    public function testCurrentReturnsCurrentLineFromInput()
    {
        $source = fopen($this->getFilePathFor('commaNewlineHeader'), 'r+');
        $dialect = new Dialect([
            'header' => false,
        ]);
        $reader = new Reader(to_stream($source), $dialect);
        $this->assertSame([
            0 => 'Bank Name',
            1 => 'City',
            2 => 'ST',
            3 => 'CERT',
            4 => 'Acquiring Institution',
            5 => 'Closing Date',
            6 => 'Updated Date'
        ], $reader->current());
    }

    public function testNextMovesInputToNextLineAndLoadsItIntoMemory()
    {
        $source = fopen($this->getFilePathFor('commaNewlineHeader'), 'r+');
        $dialect = new Dialect([
            'header' => false,
        ]);
        $reader = new Reader(to_stream($source), $dialect);
        $this->assertSame([
            0 => 'Bank Name',
            1 => 'City',
            2 => 'ST',
            3 => 'CERT',
            4 => 'Acquiring Institution',
            5 => 'Closing Date',
            6 => 'Updated Date'
        ], $reader->current());
        $this->assertSame($reader, $reader->next());
        $this->assertSame([
            'First CornerStone Bank',
            "King of\nPrussia",
            'PA',
            '35312',
            'First-Citizens Bank & Trust Company',
            '6-May-16',
            '25-May-16'
        ], $reader->current());
    }

    public function testKeyReturnsLineNumber()
    {
        $source = fopen($this->getFilePathFor('commaNewlineHeader'), 'r+');
        $dialect = new Dialect([
            'header' => false,
        ]);
        $reader = new Reader(to_stream($source), $dialect);
        $this->assertSame([
            0 => 'Bank Name',
            1 => 'City',
            2 => 'ST',
            3 => 'CERT',
            4 => 'Acquiring Institution',
            5 => 'Closing Date',
            6 => 'Updated Date'
        ], $reader->current());
        $this->assertSame(0, $reader->key());
    }

    public function testKeyReturnsLineNumberNotIncludingHeaderLine()
    {
        $source = fopen($this->getFilePathFor('commaNewlineHeader'), 'r+');
        $reader = new Reader(to_stream($source));
        $this->assertSame(0, $reader->key());
        $this->assertSame([
            'Bank Name' => 'First CornerStone Bank',
            'City' => "King of\nPrussia",
            'ST' => 'PA',
            'CERT' => '35312',
            'Acquiring Institution' => 'First-Citizens Bank & Trust Company',
            'Closing Date' => '6-May-16',
            'Updated Date' => '25-May-16'
        ], $reader->current());
    }

    // disabling this test for now because it's more important for the reader to work even if no final newline
    /*
    public function testValidReturnsFalseIfInputIsAtEOF()
    {
        $source = fopen($this->getFilePathFor('commaNewlineHeader'), 'r+');
        $stream = to_stream($source);
        $reader = new Reader($stream);
        $this->assertFalse($stream->eof());
        $this->assertTrue($reader->valid());
        $stream->seek($stream->getSize()+1);
        $this->assertTrue($stream->eof());
        $this->assertFalse($reader->valid());
    }*/

    public function testRewindResetsReaderToBeginning()
    {
        $source = fopen($this->getFilePathFor('commaNewlineHeader'), 'r+');
        $stream = to_stream($source);
        $reader = new Reader($stream);
        $this->assertSame([
            'Bank Name' => 'Trust Company Bank',
            'City' => 'Memphis',
            'ST' => 'TN',
            'CERT' => '9956',
            'Acquiring Institution' => 'The Bank of Fayette County',
            'Closing Date' => '29-Apr-16',
            'Updated Date' => '25-May-16'
        ], $reader->next()->current());
        $this->assertSame($reader, $reader->rewind());
        $this->assertSame([
            'Bank Name' => 'First CornerStone Bank',
            'City' => "King of\nPrussia",
            'ST' => 'PA',
            'CERT' => '35312',
            'Acquiring Institution' => 'First-Citizens Bank & Trust Company',
            'Closing Date' => '6-May-16',
            'Updated Date' => '25-May-16'
        ], $reader->current());
    }

    public function testCountReturnsNumberOfLines()
    {
        $dialect = new Dialect(['header' => false]);
        $source = fopen($this->getFilePathFor('commaNewlineHeader'), 'r+');
        $data = explode("\n", $this->getFileContentFor('commaNewlineHeader'));
        $reader = new Reader(to_stream($source), $dialect);
        $this->assertEquals(30, $reader->count());
        $this->assertEquals(30, count($reader));
        $reader->setDialect(new Dialect(['header' => true]));
        $this->assertEquals(29, $reader->count());
        $this->assertEquals(29, count($reader));
    }
}