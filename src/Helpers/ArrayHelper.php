<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Helpers;

use ArrayAccess;
use Closure;

class ArrayHelper
{
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param array|object $target
     * @param string|array|int|Closure|null $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(
        array|object $target,
        string|array|int|Closure|null $key = null,
        mixed $default = null
    ): mixed {
        if (is_null($key)) {
            return $target;
        }

        if ($key instanceof Closure) {
            return $key($target, $default);
        }

        $key = is_array($key) ? $key : explode('.', is_int($key) ? (string)$key : $key);

        while (!is_null($segment = array_shift($key))) {
            if (self::isArray($target) && isset($target[$segment])) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                if ($segment === '*') {
                    $data = [];
                    foreach ($target as $item) {
                        $data[] = self::get($item, $key, $default);
                    }
                    return $data;
                } else {
                    return $default;
                }
            }
        }
        return $target;
    }

    /**
     * @param object|array|null $target 目标数组
     * @param array|string|null $key 键值，支持点分方式 ep: db.host
     * @param mixed $value 值
     * @param bool $overwrite 是否覆盖原有的值
     * @return array|null
     */
    public static function set(
        object|array|null &$target,
        array|string|null $key,
        mixed $value,
        bool $overwrite = true
    ): ?array {
        $segments = is_array($key) ? $key : explode('.', is_int($key) ? (string)$key : $key);
        $segment = array_shift($segments);

        if (self::isArray($target)) {
            if ($segments) {
                if (!isset($target[$segment])) {
                    $target[$segment] = [];
                }
                self::set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || !isset($target[$segment])) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (!isset($target->{$segment})) {
                    $target->{$segment} = [];
                }
                self::set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || !isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];
            if ($segments) {
                self::set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }
        return $target;
    }

    /**
     * @param object|array|null $target 目标数组
     * @param array | int | string | null $key 键值，支持点分方式 ep: db.host
     * @return bool
     */
    public static function has(object|array|null $target, array|int|string|null $key): bool
    {
        if (is_null($key)) {
            return false;
        }

        $key = is_array($key) ? $key : explode('.', is_int($key) ? (string)$key : $key);
        while (!is_null($segment = array_shift($key))) {
            if (self::isArray($target) && isset($target[$segment])) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return false;
            }
        }
        return true;
    }

    public static function isArray($obj): bool
    {
        return is_array($obj) || $obj instanceof ArrayAccess;
    }

    /**
     * Removes an item from an array and returns the value. If the key does not exist in the array, the default value
     * will be returned instead.
     *
     * Usage examples,
     *
     * ```php
     *   $data = [
     *          [
     *              "category_name" => 'test1',
     *              'remove' => [1, 2, 3]
     *          ],
     *          [
     *              "category_name" => 'test2',
     *              'remove' => [4, 5, 6]
     *          ]
     *   ];
     *
     *   ArrayHelper::removes($data, '*.remove');
     *   // array content
     *   // $data = [["category_name" => 'test1'], [ "category_name" => 'test2']];
     *
     * ```
     *
     * @param mixed $target the array to extract value from
     * @param mixed $key key name of the array element
     */
    public static function removes(mixed &$target, mixed $key)
    {
        $segments = is_array($key) ? $key : explode('.', is_int($key) ? (string)$key : $key);
        $segment = array_shift($segments);

        if (!empty($segments)) {
            if (self::isArray($target) && isset($target[$segment])) {
                self::removes($target[$segment], $segments);
            } elseif (is_object($target) && isset($target->{$segment})) {
                self::removes($target->{$segment}, $segments);
            } else {
                if ($segment === '*') {
                    foreach ($target as $index => $item) {
                        self::removes($item, $segments);
                        $target[$index] = $item;
                    }
                } else {
                    self::removes($target[$segment], $segments);
                }
            }
        } else {
            self::remove($target, $segment);
        }
    }

    /**
     * Removes an item from an array and returns the value. If the key does not exist in the array, the default value
     * will be returned instead.
     *
     * Usage examples,
     *
     * ```php
     * // $array = ['type' => 'A', 'options' => [1, 2]];
     * // working with array
     * $type = \Yiisoft\Arrays\ArrayHelper::remove($array, 'type');
     * // $array content
     * // $array = ['options' => [1, 2]];
     * ```
     *
     * @param mixed $array the array to extract value from
     * @param mixed $key key name of the array element
     * @param mixed|null $default the default value to be returned if the specified key does not exist
     * @return mixed the value of the element if found, default value otherwise
     */
    public static function remove(mixed &$array, mixed $key, mixed $default = null): mixed
    {
        if (isset($array[$key]) || array_key_exists($key, $array)) {
            $value = $array[$key];
            unset($array[$key]);
            return $value;
        }

        return $default;
    }

    /**
     * Removes items with matching values from the array and returns the removed items.
     *
     * Example,
     *
     * ```php
     * $array = ['Bob' => 'Dylan', 'Michael' => 'Jackson', 'Mick' => 'Jagger', 'Janet' => 'Jackson'];
     * $removed = \Yiisoft\Arrays\ArrayHelper::removeValue($array, 'Jackson');
     * // result:
     * // $array = ['Bob' => 'Dylan', 'Mick' => 'Jagger'];
     * // $removed = ['Michael' => 'Jackson', 'Janet' => 'Jackson'];
     * ```
     *
     * @param array $array the array where to look the value from
     * @param mixed $value the value to remove from the array
     *
     * @return array the items that were removed from the array
     */
    public static function removeValue(array &$array, mixed $value): array
    {
        $result = [];
        foreach ($array as $key => $val) {
            if ($val === $value) {
                $result[$key] = $val;
                unset($array[$key]);
            }
        }

        return $result;
    }

    /**
     * Indexes and/or groups the array according to a specified key.
     * The input should be either multidimensional array or an array of objects.
     *
     * The $key can be either a key name of the sub-array, a property name of object, or an anonymous
     * function that must return the value that will be used as a key.
     *
     * $groups is an array of keys, that will be used to group the input array into one or more sub-arrays based
     * on keys specified.
     *
     * If the `$key` is specified as `null` or a value of an element corresponding to the key is `null` in addition
     * to `$groups` not specified then the element is discarded.
     *
     * For example:
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
     *     ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
     *     ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
     * ];
     * $result = ArrayHelper::index($array, 'id');
     * ```
     *
     * The result will be an associative array, where the key is the value of `id` attribute
     *
     * ```php
     * [
     *     '123' => ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
     *     '345' => ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone']
     *     // The second element of an original array is overwritten by the last element because of the same id
     * ]
     * ```
     *
     * An anonymous function can be used in the grouping array as well.
     *
     * ```php
     * $result = ArrayHelper::index($array, function ($element) {
     *     return $element['id'];
     * });
     * ```
     *
     * Passing `id` as a third argument will group `$array` by `id`:
     *
     * ```php
     * $result = ArrayHelper::index($array, null, 'id');
     * ```
     *
     * The result will be a multidimensional array grouped by `id` on the first level, by `device` on the second level
     * and indexed by `data` on the third level:
     *
     * ```php
     * [
     *     '123' => [
     *         ['id' => '123', 'data' => 'abc', 'device' => 'laptop']
     *     ],
     *     '345' => [ // all elements with this index are present in the result array
     *         ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
     *         ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
     *     ]
     * ]
     * ```
     *
     * The anonymous function can be used in the array of grouping keys as well:
     *
     * ```php
     * $result = ArrayHelper::index($array, 'data', [function ($element) {
     *     return $element['id'];
     * }, 'device']);
     * ```
     *
     * The result will be a multidimensional array grouped by `id` on the first level, by the `device` on the second one
     * and indexed by the `data` on the third level:
     *
     * ```php
     * [
     *     '123' => [
     *         'laptop' => [
     *             'abc' => ['id' => '123', 'data' => 'abc', 'device' => 'laptop']
     *         ]
     *     ],
     *     '345' => [
     *         'tablet' => [
     *             'def' => ['id' => '345', 'data' => 'def', 'device' => 'tablet']
     *         ],
     *         'smartphone' => [
     *             'hgi' => ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone']
     *         ]
     *     ]
     * ]
     * ```
     *
     * @param array $array the array that needs to be indexed or grouped
     * @param Closure|string|null $key the column name or anonymous function which result will be used to index the array
     * @param string|Closure[]|string[]|null $groups the array of keys, that will be used to group the input array
     *                                                by one or more keys. If the $key attribute or its value for the particular element is null and $groups is not
     *                                                defined, the array element will be discarded. Otherwise, if $groups is specified, array element will be added
     *                                                to the result array without any key.
     *
     * @return array the indexed and/or grouped array
     */
    public static function index(array $array, Closure|string|null $key, array|string|null $groups = []): array
    {
        $result = [];
        $groups = (array)$groups;

        foreach ($array as $element) {
            $lastArray = &$result;

            foreach ($groups as $group) {
                $value = static::get($element, $group);
                if (!array_key_exists($value, $lastArray)) {
                    $lastArray[$value] = [];
                }
                $lastArray = &$lastArray[$value];
            }

            if ($key === null) {
                if (!empty($groups)) {
                    $lastArray[] = $element;
                }
            } else {
                $value = static::get($element, $key);
                if ($value !== null) {
                    $lastArray[$value] = $element;
                }
            }
            unset($lastArray);
        }

        return $result;
    }

    /**
     * Builds a map (key-value pairs) from a multidimensional array or an array of objects.
     * The `$from` and `$to` parameters specify the key names or property names to set up the map.
     * Optionally, one can further group the map according to a grouping field `$group`.
     *
     * For example,
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'name' => 'aaa', 'class' => 'x'],
     *     ['id' => '124', 'name' => 'bbb', 'class' => 'x'],
     *     ['id' => '345', 'name' => 'ccc', 'class' => 'y'],
     * ];
     *
     * $result = ArrayHelper::map($array, 'id', 'name');
     * // the result is:
     * // [
     * //     '123' => 'aaa',
     * //     '124' => 'bbb',
     * //     '345' => 'ccc',
     * // ]
     *
     * $result = ArrayHelper::map($array, 'id', 'name', 'class');
     * // the result is:
     * // [
     * //     'x' => [
     * //         '123' => 'aaa',
     * //         '124' => 'bbb',
     * //     ],
     * //     'y' => [
     * //         '345' => 'ccc',
     * //     ],
     * // ]
     * ```
     *
     * @param array $array
     * @param Closure|string $from
     * @param Closure|string $to
     * @param string|Closure|null $group
     * @return array
     */
    public static function map(array $array, Closure|string $from, Closure|string $to, string|Closure|null $group = null): array
    {
        if ($group === null) {
            return array_column($array, $to, $from);
        }

        $result = [];
        foreach ($array as $element) {
            $key = static::get($element, $from);
            $value = static::get($element, $to);
            $result[static::get($element, $group)][$key] = $value;
        }

        return $result;
    }

    /**
     * Filters array according to rules specified.
     *
     * For example:
     *
     * ```php
     * $array = [
     *     'A' => [1, 2],
     *     'B' => [
     *         'C' => 1,
     *         'D' => 2,
     *     ],
     *     'E' => 1,
     * ];
     *
     * $result = \Yiisoft\Arrays\ArrayHelper::filter($array, ['A']);
     * // $result will be:
     * // [
     * //     'A' => [1, 2],
     * // ]
     *
     * $result = \Yiisoft\Arrays\ArrayHelper::filter($array, ['A', 'B.C']);
     * // $result will be:
     * // [
     * //     'A' => [1, 2],
     * //     'B' => ['C' => 1],
     * // ]
     *
     * $result = \Yiisoft\Arrays\ArrayHelper::filter($array, ['B', '!B.C']);
     * // $result will be:
     * // [
     * //     'B' => ['D' => 2],
     * // ]
     * ```
     *
     * @param array $array Source array
     * @param array $filters Rules that define array keys which should be left or removed from results.
     *                       Each rule is:
     *                       - `var` - `$array['var']` will be left in result.
     *                       - `var.key` = only `$array['var']['key'] will be left in result.
     *                       - `!var.key` = `$array['var']['key'] will be removed from result.
     *
     * @return array Filtered array
     */
    public static function filter(array $array, array $filters): array
    {
        $result = [];
        $forbiddenVars = [];

        foreach ($filters as $var) {
            $keys = explode('.', $var);
            $globalKey = $keys[0];
            $localKey = $keys[1] ?? null;

            if ($globalKey[0] === '!') {
                $forbiddenVars[] = [
                    substr($globalKey, 1),
                    $localKey,
                ];
                continue;
            }

            if (!array_key_exists($globalKey, $array)) {
                continue;
            }
            if ($localKey === null) {
                $result[$globalKey] = $array[$globalKey];
                continue;
            }
            if (!isset($array[$globalKey][$localKey])) {
                continue;
            }
            if (!array_key_exists($globalKey, $result)) {
                $result[$globalKey] = [];
            }
            $result[$globalKey][$localKey] = $array[$globalKey][$localKey];
        }

        foreach ($forbiddenVars as [$globalKey, $localKey]) {
            if (array_key_exists($globalKey, $result)) {
                unset($result[$globalKey][$localKey]);
            }
        }

        return $result;
    }

    /**
     * check value
     *  null?
     *  ''?
     *
     * @param array $array the array with keys to check
     * @param string $key the key to check
     *
     * @return bool
     */
    public static function isValidValue(array $array, string $key): bool
    {
        return self::keyExists($array, $key) && !StringHelper::isEmpty($array[$key]);
    }

    /**
     * Checks if the given array contains the specified key.
     * This method enhances the `array_key_exists()` function by supporting case-insensitive
     * key comparison.
     *
     * @param array $array the array with keys to check
     * @param string $key the key to check
     * @param bool $caseSensitive whether the key comparison should be case-sensitive
     *
     * @return bool whether the array contains the specified key
     */
    public static function keyExists(array $array, string $key, bool $caseSensitive = true): bool
    {
        if ($caseSensitive) {
            // Function `isset` checks key faster but skips `null`, `array_key_exists` handles this case
            // http://php.net/manual/en/function.array-key-exists.php#107786
            return isset($array[$key]) || array_key_exists($key, $array);
        }

        foreach (array_keys($array) as $k) {
            if (strcasecmp($key, $k) === 0) {
                return true;
            }
        }

        return false;
    }
}
