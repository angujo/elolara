<?php

namespace {namespace};

/**
 * @see https://laravel.com/docs/eloquent-relationships#custom-polymorphic-types
 *
 * This class should be called in the project's service provider as documented.
 * Can be used directly as;
 * Relation::morphMap(App\Models\Extensions\RelationMorphMap::getMaps());
 * OR
 * By extending with own custom morphs;
 * Relation::morphMap(array_merge(App\Models\Extensions\RelationMorphMap::getMaps(),[...],[...],...));
 * Recommended to be used within the [ AppServiceProvider ] -> [ boot ] method
 *
 */
class {name}
{
    /**
     * Method to get morph relation(s)
     *
     * @param null|ElolaraModel|string $class
     *
     * @return false|string[]|string
     */
    public static function getMaps($class = null)
    {
        $morphs=[
        {morphs}];

        if ($class) {
            if (is_object($class)) $class = get_class($class);
            if ($class && !is_string($class)) return null;
            return array_search($class, $morphs);
        }
        return $morphs;
    }
}