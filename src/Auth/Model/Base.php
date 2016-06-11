<?php
/**
 * Copyright 2016 Andrew O'Rourke
 */

namespace Auth\Model;


class Base extends \Model
{
    /**
     * Helper method.
     *
     * @return \ORMWrapper
     */
    public static function factory($class_name = null, $connection_name = null) {
        if (is_null($class_name)) {
            $class_name = get_called_class();
        }
        return \Model::factory($class_name, $connection_name);
    }

    public static function getBy($field, $value) {
        return static::factory()->where($field, $value)->find_one();
    }

    /**
     * Sets this model's ID to a random string.
     *
     * @return Base
     */
    public function generateID() {
        $this->id = substr(hash('md5', uniqid()), 0, 8);
        return $this;
    }

    /**
     * Handles getters and setters.
     *
     * @param string $method
     * @param array $arguments
     * @return $this|null|string
     * @throws \ParisMethodMissingException
     */
    public function __call($method, $arguments)
    {
        if(preg_match('/^(s|g)et[A-Z]\w*$/', $method)) {
            $property = lcfirst(substr($method, 3));
            $type = substr($method, 0, 3);
            if ($type === 'get') {
                return $this->$property;
            }
            $this->$property = $arguments[0];
            return $this;
        } else {
            parent::__call($method, $arguments);
        }
    }
}