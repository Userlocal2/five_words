<?php

namespace App\Lib;

use Cake\Utility\Inflector;

/**
 * Class Convert
 * @package App\Lib
 */
class Convert
{
    const OBJECT_MERGE_METHOD_OVERWRITE = 'overwrite';
    const OBJECT_MERGE_METHOD_MERGE     = 'merge';

    /**
     * @param array $input
     *
     * @return array
     */
    public static function arrayToUnderscore(array $input) {
        if (!$input) {
            return $input;
        }
        $return = [];

        foreach ($input as $key => $value) {
            if (substr($key, 0, 1) == '_') {
                continue;
            }
            $key = Inflector::underscore($key);
            method_exists($value, 'toArray') && $value = $value->toArray();
            is_array($value) && $value = static::arrayToUnderscore($value);
            $return[$key] = $value;
        }

        return $return;
    }

    /**
     * @param array $input
     * @param bool  $ucfirst
     *
     * @return array
     */
    public static function arrayToCamelCase(array $input, bool $ucfirst = false) {
        if (!$input) {
            return $input;
        }
        $return = [];

        foreach ($input as $key => $value) {
            if (substr($key, 0, 1) == '_') {
                continue;
            }
            $key = Inflector::camelize($key);
            if (!$ucfirst) {
                $key = lcfirst($key);
            }
            method_exists($value, 'toArray') && $value = $value->toArray();
            is_array($value) && $value = static::arrayToCamelCase($value);
            $return[$key] = $value;
        }

        return $return;
    }


    /**
     * @param      $input
     * @param null $callback
     *
     * @return array
     */
    public static function arrayFilterRecursive($input, $callback = null) {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = self::arrayFilterRecursive($value, $callback);
            }
        }

        return array_filter($input, $callback);
    }

    /**
     * @param               $data
     *
     * @return array
     */
    public static function objectToArray($data) {
        return \GuzzleHttp\json_decode(\GuzzleHttp\json_encode($data), true);
    }


    /**
     * @param array $data
     *
     * @return \stdClass
     */
    public static function arrayToObject(array $data): \stdClass {
        if (!empty($data)) {
            return \GuzzleHttp\json_decode(\GuzzleHttp\json_encode($data));
        }

        return new \stdClass();
    }


    /**
     * @param      $object
     * @param      $objectToMap
     * @param null $jm
     *
     * @return object
     * @throws \JsonMapper_Exception
     */
    public static function mapToObject($object, $objectToMap, $jm = null) {
        if (empty($jm) || !($jm instanceof \JsonMapper)) {
            $jm = new \JsonMapper();
        }

        try {
            return $jm->map($object, $objectToMap);
        }
        catch (\JsonMapper_Exception $e) {
//            CJLogger::sysLog(
//                'Cannot map in ' . get_called_class() . PHP_EOL .
//                'From ' . get_class($object) . ' to ' . get_class($objectToMap),
//                'Message:' . $e->getMessage() .
//                PHP_EOL . 'Trace:' . $e->getTraceAsString()
//            );
            throw $e;
        }
    }

    /**
     * Checks whether array is associated
     *
     * @param $arr
     *
     * @return bool
     */
    public static function isAssoc($arr) {
        if ([] === $arr) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}
