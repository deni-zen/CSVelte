<?php
/**
 * CSVelte: Slender, elegant CSV for PHP
 *
 * Inspired by Python's CSV module and Frictionless Data and the W3C's CSV
 * standardization efforts, CSVelte was written in an effort to take all the
 * suck out of working with CSV.
 *
 * @version   v${CSVELTE_DEV_VERSION}
 * @copyright Copyright (c) 2016 Luke Visinoni <luke.visinoni@gmail.com>
 * @author    Luke Visinoni <luke.visinoni@gmail.com>
 * @license   https://github.com/deni-zen/csvelte/blob/master/LICENSE The MIT License (MIT)
 */
namespace CSVelte\Collection;

use function CSVelte\is_traversable;

class TabularCollection extends MultiCollection
{
    /**
     * Is input data structure valid?
     *
     * In order to determine whether a given data structure is valid for a
     * particular collection type (tabular, numeric, etc.), we have this method.
     *
     * @param mixed $data The data structure to check
     * @return boolean True if data structure is tabular
     */
    protected function isConsistentDataStructure($data)
    {
        return static::isTabular($data);
    }

    /**
     * @inheritdoc
     */
    public function map(callable $callback)
    {
        $ret = [];
        foreach ($this->data as $key => $row) {
            $ret[$key] = $callback(static::factory($row));
        }
        return static::factory($ret);
    }

    public function average($column)
    {
        $coll = $this->getColumnAsCollection($column);
        return $coll->sum() / $coll->count();
    }

    public function mode($column)
    {
        return $this->getColumnAsCollection($column)->mode();
    }

    public function sum($column)
    {
        return $this->getColumnAsCollection($column)->sum();
    }

    public function median($column)
    {
        return $this->getColumnAsCollection($column)->median();
    }

    protected function getColumnAsCollection($column)
    {
        return (new NumericCollection(array_column($this->data, $column)));
    }
}