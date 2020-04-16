<?php

namespace Armincms\Localization\Contracts; 

interface Translatable
{ 
    /**
     * The name of the "langauge" column.
     *
     * @var string
     */
    const LANGUAGE = 'language';  

    /**
     * Preparing data of the translation's model for storing.
     * 
     * @param  string $locale 
     * @param  array  $attributes   
     * @return mixed         
     */
    public function performTranslation(string $locale, array $attributes = []);

    /**
     * Preparing attribute of the translation's model for storing.
     *
     * @param  string  $locale
     * @param  string  $attribute
     * @param  mixed   $value
     * @return $this
     */
    public function setTranslate(string $locale, string $attribute, $value);

    /**
     * Get an attribute from the translation by specified locale.
     * 
     * @param  string $attribute 
     * @param  string $locale    
     * @param  mixed $default   
     * @return mixed            
     */
    public function getTranslate(string $attribute, string $locale, $default = null);

    /**
     * Get the translation's model by locale.
     * 
     * @param  string|null  $locale 
     * @param  bool|bool $force  
     * @return \Illuminate\Database\Eloquent\Model | null               
     */
    public function translation(string $locale = null, bool $force = false);
}