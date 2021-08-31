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
    * Can be used directly as;
    * Relation::morphMap(App\Models\Extensions\RelationMorphMap::getMaps());
    * OR
    * By extending with own custom morphs;
    * Relation::morphMap(array_merge(App\Models\Extensions\RelationMorphMap::getMaps(),[...],[...],...));
    * Recommended to be used within the [ AppServiceProvider ] -> [ boot ] method
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

    /**
    * Method to load all observers within for elolara
    * Should be called within the [ AppServiceProvider ] -> [ boot ] method
    * Can be called AS IS i.e. App\Models\Extensions\RelationMorphMap::observers()
    */
    public static function observers()
    {
        {observers}
    }
}