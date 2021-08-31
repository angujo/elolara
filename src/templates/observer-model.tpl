<?php

namespace {namespace};

{imports}

 /**
 * This is the Observer class for {model_name}
 *
 * @access public
 * @version {lm_version}
 * @php {php_version}
 * @package {lm_name}
 * @subpackage observers
 * @author {lm_author}
 * @generated {date}
 * @name {name}
 *
{phpdoc_props}
 *
 */
{abstract}class {name}
{
   /**
     * Handle the {model_name} "retrieved" event.
     *
     * @param  {model_class}  {var_name}
     * @return void
     */
    public function retrieved({model_name} {var_name})
    {
        {retrieved_content}
    }

   /**
     * Handle the {model_name} "creating" event.
     *
     * @param  {model_class}  {var_name}
     * @return void
     */
    public function creating({model_name} {var_name})
    {
        {creating_content}
    }

   /**
     * Handle the {model_name} "created" event.
     *
     * @param  {model_class}  {var_name}
     * @return void
     */
    public function created({model_name} {var_name})
    {
        {created_content}
    }

   /**
     * Handle the {model_name} "updating" event.
     *
     * @param  {model_class}  {var_name}
     * @return void
     */
    public function updating({model_name} {var_name})
    {
        {updating_content}
    }

   /**
     * Handle the {model_name} "updated" event.
     *
     * @param  {model_class}  {var_name}
     * @return void
     */
    public function updated({model_name} {var_name})
    {
        {updated_content}
    }

   /**
     * Handle the {model_name} "saving" event.
     *
     * @param  {model_class}  {var_name}
     * @return void
     */
    public function saving({model_name} {var_name})
    {
        {saving_content}
    }

   /**
     * Handle the {model_name} "saved" event.
     *
     * @param  {model_class}  {var_name}
     * @return void
     */
    public function saved({model_name} {var_name})
    {
        {saved_content}
    }

   /**
     * Handle the {model_name} "deleting" event.
     *
     * @param  {model_class}  {var_name}
     * @return void
     */
    public function deleting({model_name} {var_name})
    {
        {deleting_content}
    }

   /**
     * Handle the {model_name} "deleted" event.
     *
     * @param  {model_class}  {var_name}
     * @return void
     */
    public function deleted({model_name} {var_name})
    {
        {deleted_content}
    }

   /**
     * Handle the {model_name} "restoring" event.
     *
     * @param  {model_class}  {var_name}
     * @return void
     */
    public function restoring({model_name} {var_name})
    {
        {restoring_content}
    }

   /**
     * Handle the {model_name} "restored" event.
     *
     * @param  {model_class}  {var_name}
     * @return void
     */
    public function restored({model_name} {var_name})
    {
        {restored_content}
    }

   /**
     * Handle the {model_name} "replicating" event.
     *
     * @param  {model_class}  {var_name}
     * @return void
     */
    public function replicating({model_name} {var_name})
    {
        {replicating_content}
    }
}