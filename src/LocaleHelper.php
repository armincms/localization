<?php

namespace Armincms\Localization; 

use Illuminate\Support\Str; 

class LocaleHelper  
{ 
	/**
	 * The attribute separator of the locale.
	 * 
	 * @var string
	 */
	public static $separator = '::';

	/**
	 * the splitting of attribute and key.
	 * 
	 * @param  string $key 
	 * @return array      
	 */
	public static function parse(string $string)
	{ 
        return [ 
            static::detach($string), //Determine attribute
            static::detach($string, false)?: app()->getLocale(), // Determine locale 
            static::hasSuffixes($string), // Determine if the string suffixed by locale
        ]; 
	}

	/**
	 * Attach locale to the given key.
	 * 
	 * @param  string $key    
	 * @param  string $locale 
	 * @return string         
	 */
	public static function attach(string $key, string $locale)
	{ 
        return $key.static::$separator.$locale;
	}

	/**
	 * Detach locale or attribute from the given key.
	 * 
	 * @param  string $key    
	 * @param  bool   $locale
	 * @return string               
	 */
	public static function detach(string $key, bool $locale = true)
	{ 
		$method = $locale ? 'before' : 'after';
		
        return Str::$method($key, static::$separator);
	}  

	/**
	 * Determine if the given string has suffix by the `locale`.
	 * 
	 * @param  string  $key      
	 * @param  array   $suffixes 
	 * @return boolean           
	 */
	public static function hasSuffixes(string $key, array $suffixes = [])
	{ 
		$suffixes  = $suffixes ?: static::suffixes();

        return Str::endsWith($key, $suffixes);
	}  

	/**
	 * The array of locale suffixes.
	 * 
	 * @return array
	 */
	public static function suffixes()
	{  
        return array_map(function($locale) {
            return static::attach('', $locale['name']);
        }, static::activeLocales()); 
	}

	/**
	 * The array of activated locale.
	 * 
	 * @return array
	 */
	public static function activeLocales()
	{
		return app('armincms.localization')->filter(function($locale) {
            return boolval($locale['active']);
        });
	}
}