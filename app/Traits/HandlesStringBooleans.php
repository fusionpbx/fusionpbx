<?php
namespace App\Traits;

/**
 * Trait HandlesStringBooleans
 * 
 * This trait handles string representations of boolean values in database fields.
 * 
 * IMPORTANT: Any model using this trait should define a static $stringBooleanFields array
 * that contains the names of all fields to be handled as string booleans.
 * 
 * @property static array $stringBooleanFields Fields to be handled as string booleans
 */
trait HandlesStringBooleans
{
    /**
     * Get the string boolean fields for the current model.
     * 
     * @return array
     */
    protected static function getStringBooleanFields(): array
    {
        return static::$stringBooleanFields ?? [];
    }
    
    /**
     * Boot the trait and register model events.
     * This is automatically called by Laravel during model booting.
     */
    public static function bootHandlesStringBooleans()
    {
        static::creating(function ($model) {
            foreach (static::getStringBooleanFields() as $field) {
                if (!isset($model->attributes[$field])) {
                    $model->attributes[$field] = 'false';
                }
            }
        });
        
        static::updating(function ($model) {
            foreach (static::getStringBooleanFields() as $field) {
                if (!isset($model->attributes[$field]) || $model->attributes[$field] === null) {
                    $model->attributes[$field] = 'false';
                }
            }
        });
    }
    
    /**
     * Initialize the trait.
     * This is automatically called by Laravel during model initialization.
     */
    public function initializeHandlesStringBooleans()
    {
        $stringBooleanFields = static::getStringBooleanFields();
        if (!empty($stringBooleanFields)) {
            foreach ($stringBooleanFields as $field) {
                $this->casts[$field] = 'boolean';
            }
        }
    }

    /**
     * Get the attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $stringBooleanFields = static::getStringBooleanFields();
        if (in_array($key, $stringBooleanFields)) {
            $value = $this->getAttributeValue($key);
            return $value === 'true';
        }
        
        return parent::getAttribute($key);
    }
    
    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        $stringBooleanFields = static::getStringBooleanFields();
        if (in_array($key, $stringBooleanFields)) {
            $this->attributes[$key] = $value === true || $value === 'on' ? 'true' : 'false';
            return $this;
        }
        
        return parent::setAttribute($key, $value);
    }
}