<?php
/**
 * @author       bangujo ON 2021-04-30 04:09
 * @project      laravelmodel
 * @ide          PhpStorm
 * @originalFile HasCompositeKeys.php
 */

namespace Angujo\LaravelModel\Lib;


use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasCompositeKeys
 * @see https://stackoverflow.com/questions/31415213/how-i-can-put-composite-keys-in-models-in-laravel-5
 * Thanks to @mopo922 response and contribution
 * @package Angujo\LaravelModel\Lib
 */
trait HasCompositeKeys
{
    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Set the keys for a save update query.
     *
     * @param Builder $query
     *
     * @return Builder
     * @throws \Exception
     */
    protected function setKeysForSaveQuery( $query)
    {
        foreach ($this->getKeyName() as $key) {
            if (isset($this->$key))
                $query->where($key, '=', $this->$key);
            else
                throw new \Exception(__METHOD__ . 'Missing part of the primary key: ' . $key);
        }

        return $query;
    }

    /**
     * Execute a query for a single record by ID.
     *
     * @param  array  $ids Array of keys, like [column => value].
     * @param  array  $columns
     * @return mixed|static
     */
    public static function find($ids, $columns = ['*'])
    {
        $me = new self;
        $query = $me->newQuery();
        foreach ($me->getKeyName() as $key) {
            $query->where($key, '=', $ids[$key]);
        }
        return $query->first($columns);
    }
}