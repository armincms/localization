<?php

namespace Armincms\Localization; 
 
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;

class Repository  
{
    /**
     * Application onfigurations.
     * 
     * @var Illuminate\Contracts\Config\Repository
     */
    protected $configs;

    public function __construct(Config $configs)
    {
        $this->configs = $configs;
    }

    /**
     * Retrive specific locale by name.
     * 
     * @param  string $name 
     * @return array       
     */
    public function get(string $name) : array
    {
        $matches =  $this->filter(function($locale) use ($name) {
            return $locale['name'] === $name;
        });  

        return empty($matches) ? $this->normalize($name, []) : array_shift($matches);
    }  

    /**
     * Add new locale.
     * 
     * @param  string $name 
     * @param  array  $data   
     * @return $this
     */
    public function set(string $name, array $data) : self
    {
        $this->configs->set(
            "localization.locales.{$name}", $this->normalize($name, $data)
        );

        return $this;
    }

    /**
     * List all availabe locales.
     * 
     * @return array
     */
    public function all() : array
    {   
        $keys = array_keys($values = $this->locales());

        return array_map([$this, 'normalize'], $keys, $values); 
    }

    /**
     * Filter all availabe locales.
     * 
     * @return array
     */
    public function filter(callable $callback) : array
    {  
        return array_filter($this->all(), $callback);
    } 

    /**
     * Retrieve configured locales.
     * 
     * @return array
     */
    protected function locales() : array
    { 
        return (array) $this->configs->get('localization.locales', []);
    } 

    /**
     * Normilize locale data.
     * 
     * @param  string $locale 
     * @param  array  $data   
     * @return array         
     */
    protected function normalize(string $locale, array $data = []) : array
    {  
        return array_merge([
            'locale'=> $locale,
            'name'  => $locale,
            'label' => strtoupper($locale), 
            'flag'  => null,
            'icon'  => $locale,
            'active'=> true,
        ], $data); 
    }
}

