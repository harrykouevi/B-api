<?php
/*
 * File name: HasTranslations.php
 * Last modified: 2024.04.18 at 17:22:50
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace App\Traits;

use Spatie\Translatable\HasTranslations as BaseHasTranslations;

trait HasTranslations
{
    use BaseHasTranslations;

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $attributes = parent::toArray();
        foreach ($this->getTranslatableAttributes() as $field) {
            if (isset($attributes[$field]) && isJson($attributes[$field])) {
                $attributes[$field] = json_decode($attributes[$field]);
            }
        }
        return $attributes;
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toTranslatableArray(): array
    {
        $attributes = $this->attributesToArray(); // attributes selected by the query
// remove attributes if they are not selected
        $translatables = array_filter($this->getTranslatableAttributes(), function ($key) use ($attributes) {
            return array_key_exists($key, $attributes);
        });
        foreach ($translatables as $field) {
            $attributes[$field] = $this->getTranslation($field, \App::getLocale());
        }
        return array_merge($attributes, $this->relationsToArray());
    }

    public function getAttributeValue($key)
    {
        if (!$this->isTranslatableAttribute($key)) {
            return parent::getAttributeValue($key);
        } elseif (!isJson(parent::getAttributeValue($key))) {
            return parent::getAttributeValue($key);
        }
        return $this->getTranslation($key, $this->getLocale());

    }

    public function getCasts(): array
    {
        return array_merge(
            parent::getCasts(),
            array_fill_keys($this->getTranslatableAttributes(), 'string')
        );
    }

    /**
     * Encode the given value as JSON.
     *
     * @param mixed $value
     * @return string
     */
    protected function asJson($value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}


