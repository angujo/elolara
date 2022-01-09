<?php
/**
 * @author       bangujo ON 2021-04-17 09:11
 * @project      elolara
 * @ide          PhpStorm
 * @originalFile config.php
 */

/*
 * This is the configuration page for custom setup.
 * Worth noting that this library relies on directory levels for namespace
 * Accessible models will be directly placed on "base_dir"
 * Any subsequent extension or supporting directories will be set deeper into "base_dir"
 */

use Angujo\Elolara\Lib\UsesAccessor;
use Angujo\Elolara\Lib\UsesStaticAccessor;
use Illuminate\Database\Eloquent\Model;

return [
    /*
     * Class to be used for each and every generated model
     * Ensure it is or extends \Illuminate\Database\Eloquent\Model::class
     */
    'model_class' => Model::class,
    /*
     * Directory path to put the models
     */
    'base_dir' => app_path('Models'),
    /*
     * Namespace for the models
     */
    'namespace' => 'App\Models',
    /*
     * Enable composite keys in laravel
     * Currently on testing
     * Allows usage of Model::find($arr=[]) and multiple pri keys
     * If you find yourself using this, reconsider your DB structure
     */
    'composite_keys' => true,
    /*
     * Name of class to be used in customizing Eloquent to accommodate package changes.
     * E.g. models will be appended static class morphName() to allow access of relation name used.
     */
    'eloquent_extension_name' => 'Extension',
    /*
     * Create abstract classes to act as BASE abstract Class for the tables
     * It is HIGHLY RECOMMENDED to enable this.
     * Enables you to generate models based on changes without affecting your custom code
     * on child models.
     */
    'base_abstract' => true,
    /*
     * Prefix for the abstract classes
     * Default: Base
     */
    'base_abstract_prefix' => 'Base',
    /*
     * Set the model connection defined
     */
    'define_connection' => false,
    /*
     * Add the schema name to the table property
     * Applicable for postgreSQl with multiple schemas other than public.
     * Results to e.g. [schema_name].[table_name]
     */
    'add_table_schema' => false,
    /*
     * Set the date format for DB, serialization in array or json
     */
    'date_format' => null,
    /*
     * Add a validation rule to every column and avail as $rules for each model
     */
    'validation_rules' => true,
    /*
     * Function/Method name for calling the validation rules
     * E.g. if 'validate' then we can later call $user->validate();
     */
    'validation_method' => 'validate',
    /*
     * Try and auto validate before user calls save or update methods.
     * If set to FALSE, you can use or call the [validation_method] above e.g. $user->validate(); before calling save
     */
    'validate_on_save' => true,
    /*
     * Set if you want observers to be set up.
     * If [validate_on_save] = TRUE, [observers] will be assumed to be TRUE, irrespective of value below.
     */
    'observers' => true,
    /*
     * Directory path to put the models' observers
     * Observers are created once and not overwritten.
     * If validation enabled with option of [validate_on_save] set to TRUE,
     * the method will be initiated inside the observer's "saving" method.
     */
    'observers_dir' => app_path('Observers'),
    /*
     * Namespace for the observers
     */
    'observer_namespace' => 'App\Observers',
    /*
     * Suffix for the observer's file and class name
     * E.g. for model User + [observer_suffix='Observer'] = UserObserver[.php]
     */
    'observer_suffix' => 'Observer',
    /*
     * Enable to add @date on each Base Model every time it is run
     * If set to False, @date will be set on first instance
     */
    'date_base' => false,
    /*
     * Separate Models based on the database/schema
     * Recommended for cross database/schema system
     */
    'db_directories' => false,
    /*
     * Set Column names as CONST within the models
     * Allows column names to be called as User::EMAIL for email.
     */
    'constant_column_names' => false,
    /*
     * When [constant_column_names] is enable,
     * set the prefix to use.
     * e.g. prefix = 'COL_' then column email becomes User::COL_EMAIL
     */
    'constant_column_prefix' => null,
    /*
     * Column names that are used for soft delete.
     * If different naming across tables, add them here.
     * NOTE: No two names should be on same table.
     */
    'soft_delete_columns' => ['deleted_at', 'deleted'],
    /*
     * Column names to mark as create columns
     * If different naming across tables, add them here.
     * NOTE: No two names should be on same table.
     */
    'create_columns' => ['created_at', 'created'],
    /*
     * Columns to be used as update
     * If different naming across tables, add them here.
     * NOTE: No two names should be on same table.
     */
    'update_columns' => ['updated_at', 'updated'],

    /*
     * Tables to be excluded from model generation
     */
    'excluded_tables' => ['migrations', 'password_resets', 'oauth_access_tokens', 'oauth_auth_codes', 'oauth_clients', 'oauth_personal_access_clients', 'oauth_refresh_tokens',],
    /*
     *Tables to be run ONLY
     * The reset will be excluded
     */
    'only_tables' => [],
    /*
     * In Laravel 7+ a User table comes with setup and extends Authenticatable
     * This voids IDE intellisense on properties and relations
     * Setting User as Trait is way of trying to circumvent this.
     *
     * Enabling this prepares the system on availing traits for models
     * NOTE: Ensure to resolve any conflict that are already resolved in Trait
     */
    'model_trait' => true,

    /*
     * Only applies when [model_trait] is TRUE
     * Assist in getting the name of the tables to be set as traits
     * All Column constants are not attainable with this
     */
    'trait_model_tables' => ['users'],
    /*
     * Instead of extending the [ElolaraModel] the highlighted table base classes will extend respective class
     * Useful for classes such as [User] model where Illuminate\Foundation\Auth\User need to be extended instead of
     * Illuminate\Database\Eloquent\Model
     */
    'custom_extends' => ['users' => \Illuminate\Foundation\Auth\User::class],
    /*
     * While naming relations you need to select the order in which the names will be picked.
     * Ordering should start with most preferred.
     * Can only contain any of three entries; [column],[table],[constraint]
     * [column]
     * The column name will be used to identify the relation name
     * @see column_relation_pattern, relation_remove_prx and relation_remove_sfx
     * [table]
     * The target table name will be used.
     * [constraint]
     * The foreign key constrain name will be used.
     *
     * The checking and order preference is based on usage.
     * E.g. for column [manager_user_id] referencing table [users] with constraint [managers_user_id_foreign],
     * will be processed by checking if relation [manager_user] has been created by another relation,
     * if used will check if [user] has been created,
     * if used will check if [managers_user_id_foreign] has been used.
     * If all options used, will skip the relation
     */
    'relation_naming' => ['column', 'table', 'constraint'],
    /*
     * Column naming pattern to auto identify relations for Foreign Keys
     * This will check column names and set relation name based off them.
     * Percentage similarity will be set to 70%
     * Set to empty or null so as not to use.
     * E.g 1: if = [{relation_name}_id] or = [fk_{relation_name}] or = [fk_{relation_name}_id] when column name is user_manager_id then relation name will be userManager
     * i.e. use {relation_name} to indicate which part of column to be used as relation name.
     */
    'column_relation_pattern' => '{relation_name}_id',
    /*
     * Enable creation of relations based on column name.
     * This allows the using only [column_relation_pattern] on the column name to create a relation.
     * To work, the {relation_name} should referenced a table name in singular/plural format.
     * Foreign keys will not be used for further checks
     */
    'column_auto_relate' => true,
    /*
     * If you wish to rename pivot tables in belongsToMany relation,
     * Set regex for naming pattern below. The name should be in teh table's comment
     * E.g if set as '{pivot:(\w+)}', a table with comment "This is a table comment for {pivot:role_users}" will rename pivot to role_users instead of default pivot
     */
    'pivot_name_regex' => '{pivot:(\w+)}',
    /*
     * @see https://laravel.com/docs/eloquent-mutators#attribute-casting
     * Type Casting for properties and database values.
     * You can cast using a column name or data type.
     * To cast data type e.g. tinyint(1) to be boolean,
     * start with "type:" followed by the type i.e. "type:tinyint(1)"=>'boolean'
     */
    'type_casts' => ['type:tinyint(1)' => 'boolean', '%_json' => 'array', '%_array' => 'array', 'is_%' => 'boolean',
                     'type:date' => 'date:Y-m-d', 'type:datetime' => 'datetime:Y-m-d H:i:s'],
    /*
     * Overwrite files during generation.
     * Will be overwritten by the -f(--force) option in artisan cli
     * Need to be explicitly called on console to be implemented,
     * otherwise the value below is ignored
     */
    'overwrite_models' => false,
    /*
     * Fully import classes even on same namespace (FQDN)
     */
    'full_namespace_import' => false,
    /*
     * Indicate which columns to hide by default on specific tables
     * Set the table name as key and columns as array values
     * E.g. 'users'=>['password', 'remember_token',]
     */
    'hidden_columns' => ['users' => ['password', 'remember_token',]],
    /*
     * @see https://laravel.com/docs/eloquent-relationships#has-one-through
     * This is a complex relation and currently no direct way to implement.
     * Use below entry to define table relations.
     * Entries need to be sequential starting from parent table to target table and pivot in between.
     * THIS ENTRY WILL BE IGNORED UNLESS USED WITHIN [schemas] BELOW.
     */
    'has_one_through' => [['mechanics', 'cars', 'owners'], 'mechanics,cars,owners',],
    /*
     * @see https://laravel.com/docs/eloquent-relationships#has-many-through
     * This is a complex relation and currently no direct way to implement.
     * Use below entry to define table relations.
     * Entries need to be sequential starting from parent table to target table and pivot in between.
     * THIS ENTRY WILL BE IGNORED UNLESS USED WITHIN [schemas] BELOW.
     */
    'has_many_through' => [['countries', 'users', 'posts'], 'countries,users,posts',],
    /*
     * @see https://laravel.com/docs/eloquent-relationships#has-many-through
     * This is a complex relation and currently no direct way to implement.
     * Use below entry to define pivoting tables.
     * Entries should be list of table names that act as pivot for multiple other tables.
     * E.g. user and roles tables can have user_roles table as pivot table. therefore, 'user_roles' is entered below as pivot table.
     * The other relations will be auto-picked.
     * THIS ENTRY WILL BE IGNORED UNLESS USED WITHIN [schemas] BELOW.
     */
    'pivot_tables' => [],
    /*
     * Enter traits here used by all models.
     * When used within [schemas], table name can be used as well.
     * i.e. 'schemas'=>['db1'=>['traits'=[...],'tables'=>['table1'=>['traits'=>[...]]]]]
     * Full path(FQDN) should be used
     */
    'traits' => [UsesAccessor::class, UsesStaticAccessor::class,],
    /*
     * If handling multiple schema/DBs and there's need to separate schema configurations,
     * Use below with options above to be replaced.
     * An example has been commented out.
     * Schemas ignored by default are: mysql,sys,information_schema,master,template
     */
    'schemas' => [
        /*
         * Define [has_one_through] and [has_many_through] here within the schema name
         * E.g. 'db1'=>['has_many_through'        => [['countries', 'users', 'posts'], 'countries,users,posts',],
         *               'has_one_through'         => [['mechanics', 'cars', 'owners'], 'mechanics,cars,owners',],
         *               'pivot_tables'         => ['user_roles', 'role_rights',]
         */
        /*
          'information_schema' => [
            'excluded_tables' => ['migrations', 'password_resets'],
            'only_tables'     => [],
        ],
        */
    ],
];