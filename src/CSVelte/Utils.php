<?php namespace CSVelte;
/**
 * CSVelte\Utils
 * A PHP CSV utility library (formerly PHP CSV Utilities).
 *
 * @package   CSVelte
 * @copyright (c) 2016, Luke Visinoni <luke.visinoni@gmail.com>
 * @author    Luke Visinoni <luke.visinoni@gmail.com>
 * @todo      Make all of these available as global functions via a utils.php file
 *            file (just create aliases)
 */
class Utils
{
    public static function array_get($arr, $key, $default)
    {
        return array_key_exists($key, $arr) ? $arr[$key] : $default;
    }

    public static function array_items($arr)
    {
        $items = array();
        foreach ($arr as $key => $val) {
            $items[] = array($key, $val);
        }
        return $items;
    }

    public static function array_remove($arr, $item)
    {
        $unset = null;
        foreach ($arr as $key => $val) {
            if ($item == $val) {
                unset($arr[$key]);
                return;
            }
        }
        // @todo Not sure if this is the right exception
        throw new \OutOfBoundsException("array_remove: cannot find item within array");
    }

    public static function average($arr)
    {
        if (!is_array($arr)) throw new \InvalidArgumentException('"average" function expected array, got ' . gettype($arr));
        return array_sum($arr) / count($arr);
    }

    public static function array_average($arr)
    {
        $return = array();
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $return[$key] = self::average($val);
            }
        }
        return $return;
    }

    public static function mode($arr)
    {
        if (!is_array($arr)) throw new \InvalidArgumentException('"mode" function expected array, got ' . gettype($arr));
        $vals = array();
        foreach ($arr as $key => $val) {
            $vals[$val] = self::array_get($vals, $val, 0) + 1;
        }
        arsort($vals);
        return key($vals);
    }

    public static function array_mode($arr)
    {
        $return = array();
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $return[$key] = self::mode($val);
            }
        }
        return $return;
    }

    // if (!function_exists('array_column')) {
    //     throw new \Exception("Need to implement this");
    // }
}