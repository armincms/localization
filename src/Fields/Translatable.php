<?php

namespace Armincms\Localization\Fields; 

use Armincms\Localization\Contracts\Translatable as TranslatableContract;
use Armincms\Localization\LocaleHelper;
use Illuminate\Http\Resources\MergeValue;
use Illuminate\Support\Collection; 
use Laravel\Nova\Fields\Field;  
use Illuminate\Http\Request; 

class Translatable extends MergeValue 
{     
    protected $dontSupported = [
        \Laravel\Nova\Fields\File::class
    ]; 

    /**
     * Create a new panel instance.
     *
     * @param  string  $name
     * @param  \Closure|array  $fields
     * @return void
     */
    public function __construct($fields = [])
    {   
        parent::__construct($this->prepareFields(is_callable($fields) ? $fields() : $fields));  
    }  

    /**
     * Create a new element.
     *
     * @return static
     */
    public static function make(array $fields = [])
    {
        return new static($fields);
    }  

    /**
     * Prepare the given fields.
     *
     * @param  \Closure|array  $fields
     * @return array
     */
    protected function prepareFields(array $fields)
    {   
        return Collection::make($fields)->map(function($field) { 
            return $field instanceof MergeValue 
                    ? $this->prepareFields($field->data) 
                    : [$field];
        })->flatten()->filter([$this, 'supporting'])->map([
            $this, 'prepareTranslationsFields'
        ])->flatten(1);
    }   

    /**
     * Check field support.
     * 
     * @param \Laravel\Nova\Fields\Field $field  
     * @return boolean
     */
    public function supporting(Field $field)
    {
        foreach ($this->dontSupported as $excepted) {
            if(is_subclass_of($field, $excepted) ) {
                return false;
            }
        } 

        return true;
    }  

    /**
     * Make localization fields.
     * 
     * @param \Laravel\Nova\Fields\Field $field 
     * @return array        
     */
    public function prepareTranslationsFields(Field $field)
    { 
        return Collection::make(LocaleHelper::activeLocales())->map(function($locale) use ($field) { 
            return $this->prepareTranslationField($field, $locale); 
        })->prepend($this->detailField($field, app()->getLocale())); 
    }

    /**
     * Replace the field by corresponding localized field.
     * 
     * @param \Laravel\Nova\Fields\Field $field 
     * @param  array $locale 
     * @return \Laravel\Nova\Fields\Field        
     */
    public function prepareTranslationField(Field $field, array $locale)
    { 
        $clone = clone $field;

        $clone->attribute = LocaleHelper::attach($field->attribute, $locale['name']); 
        $clone->name = LocaleHelper::attach($field->name, $locale['name']);
        $clone->withMeta([
            'singularLabel'   => $field->name, 
            'locale'          => $locale['name'], 
            'placeholder'     => "{$locale['label']} {$field->name}",
        ]);  

        return tap($clone->onlyOnForms(), function($clone) use ($field) {
            is_callable($field->fillCallback) || $clone->fillUsing([$this, 'fillUsing']);  
        }); 
    } 


    /**
     * Hydrate the model attribute for the localized field.
     *  
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @param  string  $requestAttribute
     * @param  object  $model
     * @param  string  $attribute
     * @return void                  
     */
    public function fillUsing($request, $model, $attribute, $requestAttribute)
    {    
        if($model instanceof TranslatableContract) {   
            list($attribute, $locale, $force) = LocaleHelper::parse($requestAttribute);

            $value = $this->fetchAttributeFromRequest($request, $requestAttribute);

            $model->saving(function($model) use ($locale, $attribute, $value) {  
                $model->setTranslate($locale, $attribute, $value); 
            });  
        }
    } 

    /**
     * Get attribute value from the request.
     * 
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request         
     * @param  string  $requestAttribute 
     * @return void                    
     */
    protected function fetchAttributeFromRequest(Request $request, string $requestAttribute)
    {  
        $value = $request->exists($requestAttribute) ? $request[$requestAttribute] : null;

        return is_null($value) || ! $this->jsonSerialized($value) ? $value : json_decode($value, true);  
    } 

    /**
     * Detect if value is json serialized.
     * 
     * @param  mixed $value
     * @return bool       
     */
    protected function jsonSerialized($value)
    { 
        return is_string($value) && json_decode($value, true) && json_last_error() == JSON_ERROR_NONE;
    }

    /**
     * Preapre field for detail page.
     * 
     * @param  Field  $field  
     * @param  string $locale 
     * @return \Laravel\Nova\Fields\Field         
     */
    public function detailField(Field $field, string $locale)
    { 
        return tap($field, function($field) use ($locale) {
            if(Collection::make(LocaleHelper::activeLocales())->count() > 1) {
                $locale = app('armincms.localization')->get($locale);

                $field->name = "{$field->name} - ({$locale['label']})"; 
            }

            $field->hideWhenCreating()->hideWhenUpdating()->readonly();
        });
    }

    /**
     * Add language toolbar field for navigate through locales
     * 
     * @return [type] [description]
     */
    public function withToolbar()
    {
        array_unshift($this->data, LanguageToolbar::make(__('Choose Language'))->onlyOnForms());

        return $this;
    } 

    /**
     * When the panel attached to the translatable field, we'll attach it to fields.
     * 
     * @param string $key   
     * @param mixed $value 
     */
    public function __set($key, $value)
    {  
        if($key === 'panel') {
            $this->data = array_map(function($field) use ($value) {
                $field->panel = $value;

                return $field;
            }, $this->data);
        }   
 
        $this->{$key} = $value; 
    }
}

