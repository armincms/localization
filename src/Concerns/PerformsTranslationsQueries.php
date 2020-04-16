<?php 

namespace Armincms\Localization\Concerns;    
 

trait PerformsTranslationsQueries
{

    /**
     * Apply any applicable orderings to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $orderings
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function applyOrderings($query, array $orderings)
    {
        if (empty($orderings)) { 
            return parent::applyOrderings($query, $orderings);
        } 

        foreach ($orderings as $column => $direction) { 
            if(in_array($column, static::searchableTranslationsColumns())) {  
                $model = $query->getModel();
                $translationInstance = $model->makeTranslationInstance(); 

                $query->join(
                    $model->getTranslationTable(), 
                    $model->getQualifiedKeyName(), 
                    '=', 
                    $model->makeTranslationInstance()->getQualifiedKeyName()
                )->orderBy(
                    $model->makeTranslationInstance()->qualifyColumn($column), $direction
                ); 
            } else { 
                $query->orderBy($column, $direction);
            }
        }

        return $query;
    }

    /**
     * Apply the search query to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function applySearch($query, $search)
    { 
        return tap(parent::applySearch($query, $search), function($query) use ($search) { 
            if($searchable = static::searchableTranslationsColumns()) { 
                return static::applyTranslationSearch($query, $search);
            }   
        }); 
    } 

    /**
     * Apply the translations search query to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function applyTranslationSearch($query, $search)
    {
        $model = $query->getModel();

        $where = function($query) use ($model, $search) {  
            $query->where(function($query) use ($model, $search) {  
                $connectionType = $model->getConnection()->getDriverName(); 

                $likeOperator = $connectionType == 'pgsql' ? 'ilike' : 'like';

                $query->where($model->getQualifiedKeyName(), $search);

                foreach (static::searchableTranslationsColumns() as $column) {  
                    $query->orWhere(
                        $query->qualifyColumn($column), $likeOperator, '%'.$search.'%'
                    ); 
                } 
            }); 
        };

        return $query->orWhereHas('translations', $where);   
    }

    /**
     * Get the searchable translations columns for the resource.
     *
     * @return array
     */
    public static function searchableTranslationsColumns()
    {
        if(property_exists(static::class, 'searchTranslations')) {
            return (array) static::$searchTranslations;
        }

        return [];
    }
}
