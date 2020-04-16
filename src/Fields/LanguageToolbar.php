<?php

namespace Armincms\Localization\Fields; 
 
use Laravel\Nova\Fields\Field; 

class LanguageToolbar extends Field
{   

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'language-toolbar';  

    /**
     * Indicates if the element should be shown on the index view.
     *
     * @var \Closure|bool
     */
    public $showOnIndex = false;

    /**
     * Create a new field.
     *
     * @param  string  $name
     * @param  string|callable|null  $attribute
     * @param  callable|null  $resolveCallback
     * @return void
     */
    public function __construct($name, $attribute = null, callable $resolveCallback = null)
    {
        parent::__construct($name, '', $resolveCallback);

        $this->withMeta([
            'locales' => app('armincms.localization')->filter(function($locale) {
                return boolval($locale['active']);
            }),
            'activeLocale' => app()->getLocale(), 
        ])->fillUsing(function() {});
    }
}

