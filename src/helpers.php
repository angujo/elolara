<?php
/**
 * @author       bangujo ON 2021-04-20 23:47
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile helpers.php
 */

use Angujo\Elolara\Database\DBTable;
use Angujo\Elolara\Laravel\Factory;

if (!function_exists('array_export')) {
    function array_export(array $arr, $print = false)
    {
        $v = var_export($arr, !$print);
        if ($v && !$print) {
            return preg_replace(['/(\s+)(\d+|(\'\w+\'))(\s+)?\=\>(\s+)?/', '/(,|\()[\n\r]+/',], ["\n\r$2 =>", '$1 '], $v);
        }
        return null;
    }
}

if (!function_exists('comma_list')) {
    function comma_list(...$list)
    {
        return implode(', ', array_filter(array_map('trim', $list)));
    }
}

if (!function_exists('function_name_single')) {
    function function_name_single($name)
    {
        return Str::camel(Str::singular($name));
    }
}

if (!function_exists('function_name_plural')) {
    function function_name_plural($name)
    {
        return Str::camel(Str::plural($name));
    }
}

if (!function_exists('class_path')) {
    function class_path($name, $base = false, $ext = false)
    {
        return $base ? ($ext ? basename($name) : class_name($name)).'::class' : ($ext ? $name : (\Angujo\Elolara\Config::namespace().'\\'.class_name($name)));
    }
}

if (!function_exists('class_name')) {
    function class_name($name)
    {
        return ucfirst(\Str::camel(\Str::singular($name)));
    }
}

if (!function_exists('progress_message')) {
    function progress_message(string $name)
    {
        if (!Factory::$BAR) return;
        Factory::$BAR->advance();
        Factory::$BAR->setMessage($name);
    }
}

if (!function_exists('array_combination')) {
    function array_combination(array $chars, int $size, array $combinations = [])
    {
        # in case of first iteration, the first set of combinations is the same as the set of characters
        if (empty($combinations)) {
            $combinations = $chars;
        }
        # size 1 indicates we are done
        if ($size == 1) {
            return $combinations;
        }
        # initialise array to put new values into it
        $new_combinations = [];
        # loop through the existing combinations and character set to create strings
        foreach ($combinations as $combination) {
            foreach ($chars as $char) {
                if (!is_array($combination) && 0 === strcasecmp($combination, $char)) continue;
                $new_combinations[] = array_unique(is_array($combination) ? array_merge($combination, [$char]) : [$combination, $char]);
            }
        }
        # call the same function again for the next iteration as well
        return array_combination($chars, $size - 1, $new_combinations);
    }
}

if (!function_exists('relation_keys')) {
    function relation_keys(array ...$columns)
    {
        $_columns = array_reverse($columns);
        $output   = [];
        foreach ($_columns as $column) {
            if (is_string($column) || (is_array($column) && 1 === count($column))) {
                $column = array_merge(Arr::wrap($column), Arr::wrap($column));
            }
            if (!is_array($column) || 2 !== count($column)) {
                return array_reverse($output);
            }
            if (empty($output) && 0 === strcasecmp($column[0], $column[1])) {
                continue;
            }
            $output[] = $column[1];
        }
        return array_reverse($output);
    }
}

if (!function_exists('foreign_key')) {
    function foreign_key(string $column_name, string $target_table_name, $both = false)
    {
        $sus = implode('_', [strtolower(Str::snake($target_table_name)), \Angujo\Elolara\Config::LARAVEL_PRIMARY_KEY]);
        return $both ? [$column_name, 0 === strcasecmp($column_name, $sus) ? null : $column_name] : $column_name;// 0 === strcasecmp($column_name, $sus) ? null : $column_name;
    }
}

if (!function_exists('local_key')) {
    /**
     * @param DBTable|string $table
     *
     * @return string|array|null
     */
    function local_key($table, $both = false)
    {
        if (is_a($table, DBTable::class) && $table->primary_column) {
            $table = $table->primary_column->name;
        }
        $sus = is_string($table) && 0 === strcasecmp(\Angujo\Elolara\Config::LARAVEL_PRIMARY_KEY, $table) ? $table : null;
        return $both ? [$table, $sus] : $sus;
    }
}

if (!function_exists('merged_columns')) {
    /**
     * @param string|array ...$columns
     */
    function merged_columns(...$columns)
    {
        $columns = array_filter(array_map(function($col){
            return (is_array($col)) ? foreign_key(...array_merge(array_slice($col, 0, 2), [true])) : local_key($col, true);
        }, $columns), 'is_array');
        if (empty(array_filter(array_column($columns, 1)))) {
            return null;
        }
        return implode(', ', array_truncate($columns));
    }
}

if (!function_exists('array_truncate')) {
    function array_truncate(array $array)
    {
        $keys   = array_keys($array);
        $array  = array_values($array);
        $values = [];
        foreach ($array as $i => $column) {
            if (is_null($column[1])) {
                if (empty(array_filter(array_slice($column, $i + 1)))) {
                    break;
                }
                $values[$keys[$i]] = $column[0];
            }
        }
        return $values;
    }
}

if (!function_exists('array_flatten')) {
    function array_flatten($array)
    : array
    {
        if (!is_array($array)) {
            return [];
        }
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, array_flatten($value));
            } else {
                $result = array_merge($result, [$key => $value]);
            }
        }
        return $result;
    }
}