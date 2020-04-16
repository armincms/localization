<?php 

namespace Armincms\Localization\Concerns; 

use Armincms\Localization\Translation;
use Armincms\Localization\LocaleHelper;
use Illuminate\Support\Str;  
 

trait HasTranslation
{  
    /**
     * Preapred data that want to store in translation.
     * 
     * @var array
     */
    protected static $preparedTranslations = [];

    /**
     * Trait boot callback
     * 
     * @return void
     */
    public static function bootHasTranslation()
    {
        self::saved(function($model) { 
            $model->performTranslations();
        });

        self::deleting(function($model) { 
            if(! $model->usesSoftDeletes() || $model->isForceDeleting()) { 
                $model->deleteTranslations();
            }
        });
    }  

    /**
     * Trait initialize callback
     * 
     * @return $this
     */
    public function initializeHasTranslation()
    {
        if(self::$loadTranslations ?? true)  { 
            $this->with[] = 'translations'; 
        }

        return $this;
    } 

    /**
     * Clone the model into a new, non-existing instance.
     *
     * @param  array|null  $except
     * @return static
     */
    public function replicate(array $except = null)
    {
        $this->ensureTranslationsIsLoaded();

        return tap(parent::replicate($except), [$this, 'replicateTranslations']);
    }

    /**
     * Clone and save the translations model into a new, non-existing instance.
     *
     * @param  $this  $instance
     * @return static
     */
    public function replicateTranslations(self $instance)
    {
        $instance->saved(function($model) {   
            $translations = $this->translations->map(function($translation) use ($model) { 
                return tap($translation->replicate(), function($instance) {
                    $instance->setTable($this->getTranslationTable());
                });
            }); 

            $model->translations()->saveMany($translations->all());
        });
    }

    /**
     * Define a one-to-many relationship to translations table.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    { 
        $instance   = $this->makeTranslationInstance();

        $foreignKey = $this->getForeignKey();

        $localKey   = $this->getKeyName();

        return $this->newHasMany(
            $instance->newQuery(), $this, $instance->qualifyColumn($foreignKey), $localKey
        );
    }   

    /**
     * Initialize Translation model instance.
     * 
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function makeTranslationInstance()
    {
        return $this->getTranslationModel()->setTable($this->getTranslationTable());
    }

    /**
     * Get Translation model instance.
     * 
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getTranslationModel()
    {
        $class = config('localization.translation_model');

        $model = class_exists($class) ? $class : Translation::class;

        return new $model;
    }

    /**
     * Get the translation database.
     * 
     * @return string
     */
    public function getTranslationTable()
    {
        return Str::singular($this->getTable()) . '_translations';
    }

    /**
     * Get the translation model by locale.
     * 
     * @param  string|null  $locale 
     * @param  bool|bool $force  
     * @return \Illuminate\Database\Eloquent\Model | null               
     */
    public function translation(string $locale = null, bool $shouldTranslate = false)
    {
        if(! $this->relationLoaded('translations')) return;

        $locale = is_null($locale) ? app()->getLocale() : $locale;

        $callback = function($translation) use ($locale) {
            return $translation->{static::LANGUAGE} === $locale;
        };

        return $this->translations->first(
            $callback, ($shouldTranslate ? $this->translations->first() : null)
        ); 
    }   

    /**
     * Determine if the given locale has translation.
     * 
     * @param  string  $locale 
     * @return bool         
     */
    public function hasTranslation(string $locale)
    {
        return ! is_null($this->translation($locale));
    }

    /**
     * Get an attribute from the translation.
     * 
     * @param  string       $attribute 
     * @param  string       $locale    
     * @param  mixed        $default   
     * @param  bool|bool $force     
     * @return mixed                  
     */
    public function translate(string $attribute, string $locale, $default=null, bool $force=false)
    {  
        if(! $this->hasTranslate($attribute, $locale) && $force === true) {
            // if translated value not exists for the locale will retry by default translated value
            // if default translated value not exists will retry by first translated value.
            return $this->shouldTranslate($attribute, $default);
        }

        return $this->getTranslate($attribute, $locale, $default); 
    }

    /**
     * Determine if the given attribute exists on translation.
     * 
     * @param  string  $attribute 
     * @param  string|null  $locale    
     * @return bool            
     */
    public function hasTranslate(string $attribute, string $locale = null)
    {
        $locale = is_null($locale) ? app()->getLocale() : $locale;

        return ! is_null($this->getTranslate($attribute, $locale));
    }

    /**
     * Get an attribute from the translation by specified locale.
     * 
     * @param  string $attribute 
     * @param  string $locale    
     * @param  mixed $default   
     * @return mixed            
     */
    public function getTranslate(string $attribute, string $locale, $default = null)
    {
        return data_get($this->translation($locale), $attribute, $default); 
    }

    /**
     * Get an attribute from the translation by application locale. 
     * 
     * @param  string $attribute 
     * @param  string $locale    
     * @param  mixed  $default   
     * @return mixed            
     */
    protected function shouldTranslate(string $attribute, $default)
    { 
        return data_get($this->translation(null, true), $attribute, $default);
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    { 
        if(is_null($value = parent::getAttribute($key))) {
            list($attribute, $locale, $suffixed) = LocaleHelper::parse($key);  

            return $this->translate($attribute, $locale, null, ! $suffixed); 
        } 

        return $value;
    }   

    /**
     * Convert the model's and translation's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        $translates = (array) optional($this->translation(null, true))->toArray();  

        $difference = [];

        array_walk($translates, function ($value, $key) use ($difference) {
            $difference["_{$key}"] = $value;
        });

        return array_merge($difference, $translates, $attributes);
    }

    /**
     * Preparing data of the translation's model for storing.
     *
     * @param  string  $locale
     * @param  array   $attributes
     * @return $this
     */
    public function prepareTranslation(string $locale, array $attributes = [])
    {
        static::$preparedTranslations[$locale] = $attributes;
        
        return $this;
    }

    /**
     * Preparing attribute of the translation's model for storing.
     *
     * @param  string  $locale
     * @param  string  $attribute
     * @param  mixed   $value
     * @return $this
     */
    public function setTranslate(string $locale, string $attribute, $value)
    {
        static::$preparedTranslations[$locale] = array_merge(static::$preparedTranslations[$locale] ?? [], [
            $attribute => $value
        ]);
        
        return $this;
    }

    /**
     * Delete all translation's model.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function deleteTranslations()
    { 
        $this->ensureTranslationsIsLoaded();

        return $this->translations()->delete(); 
    }

    /**
     * Store preapred translations data.
     * 
     * @return array
     */
    public function performTranslations()
    { 
        $values = (array) static::$preparedTranslations; 

        return array_map(function(string $locale, array $attributes = []) {
            unset(static::$preparedTranslations[$locale]);
            
            return $this->performTranslation($locale, $attributes);
        }, array_keys($values), $values);
    }

    /**
     * Store the translation's data.
     * 
     * @param  string $locale 
     * @param  array  $attributes   
     * @return mixed         
     */
    public function performTranslation(string $locale, array $attributes = [])
    { 
        $this->ensureTranslationsIsLoaded();

        $filledData = array_filter($attributes, function($value) {
            return isset($value);
        });     

        if(! $this->hasTranslation($locale)) {
            return empty($filledData) ? false : $this->createTranslation($locale, $attributes);
        }

        if(empty($filledData) && $this->shouldCleanTranslations()) {
            return $this->deleteTranslation($locale);
        } 

        return $this->updateTranslation($locale, $attributes);
    } 

    /**
     * Determine if the translation's model loaded.
     *  
     * @return void               
     */
    public function ensureTranslationsIsLoaded()
    {  
        $this->relationLoaded('translations') || $this->load('translations');

        return $this;
    }

    /**
     * Determine if the translation's data should be clean if data is empty.
     * 
     * @return bool
     */
    public function shouldCleanTranslations()
    {
        return (bool) config('localization.clean_translations', true);
    }

    /**
     * Save a new translation model and return the instance.
     * 
     * @param  string $locale 
     * @param  array  $attributes   
     * @return \Illuminate\Database\Eloquent\Model         
     */
    public function createTranslation(string $locale, array $attributes = [])
    {
        return $this->translations()->create(array_merge([$this->getLanguageColumn() => $locale], $attributes));
    }

    /**
     * Update a record by the locale in the database.
     * 
     * @param  string $locale 
     * @param  array  $attributes   
     * @return bool      
     */
    public function updateTranslation(string $locale, array $attributes = [])
    { 
        return $this->translation($locale)->update($attributes); 
    } 

    /**
     * Delete the translation model by the locale from the database.
     * 
     * @param string $locale 
     * @return bool|null
     *
     * @throws \Exception
     */
    public function deleteTranslation(string $locale)
    { 
        return $this->translations()->where($model->getQualifiedLanguageColumn(), $locale)->delete(); 
    }

    /**
     * Get the fully qualified "deleted at" column.
     *
     * @return string
     */
    public function getQualifiedLanguageColumn()
    {
        return $this->qualifyColumn($this->getLanguageColumn());
    } 

    /**
     * Get the name of the "language" column.
     *
     * @return string
     */
    public function getLanguageColumn()
    {
        return static::LANGUAGE ?? 'language';
    }

    /**
     * Determine if the model using `softDeletes`.
     *
     * @param string|object|null $model
     * @return bool
     */
    protected function usesSoftDeletes($model = null)
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model ?? $this));
    }

    /**
     * Add an "order" for the search query.
     *
     * @param  string  $column
     * @param  string  $direction
     * @return $this
     */
    public function orderByTranslation($column, $direction = 'asc')
    { 
        return $this->joinTranslations()->orderBy(
            $this->makeTranslationInstance()->qualifyColumn($column), $direction
        ); 
    }

    public function joinTranslations()
    {
        return $query->join(
            $this->getTranslationTable(), 
            $this->getQualifiedKeyName(), 
            '=', 
            $this->makeTranslationInstance()->getQualifiedKeyName()
        );
    }
}
