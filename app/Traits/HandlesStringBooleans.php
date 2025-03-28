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
     *
     */
    public function initializeHandlesStringBooleans()
    {
        // Do not add to $this->casts as this conflicts with our custom getAttribute logic
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

        // Only apply our custom logic if the key is in our string boolean fields
        if (in_array($key, $stringBooleanFields)) {
            // Get the raw attribute value before any casting
            $value = $this->getAttributeFromArray($key);

            // More flexible comparison to handle different formats of 'true'
            return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
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
            // Handle various truthy values
            if (is_bool($value)) {
                $this->attributes[$key] = $value ? 'true' : 'false';
            } else if (is_string($value)) {
                $lowerValue = strtolower($value);
                $this->attributes[$key] = in_array($lowerValue, ['true', '1', 'yes', 'on']) ? 'true' : 'false';
            } else {
                $this->attributes[$key] = $value ? 'true' : 'false';
            }
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Get an attribute from the model's attributes array.
     * This method gets the actual value from the attributes array without applying casts.
     *
     * @param  string  $key
     * @return mixed
     */
    protected function getAttributeFromArray($key)
    {
        return $this->attributes[$key] ?? null;
    }
}
