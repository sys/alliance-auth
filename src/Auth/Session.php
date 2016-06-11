<?php
/**
 * Copyright 2016 Andrew O'Rourke
 */

namespace Auth;

use Auth\Model\User;

/**
 * Session Class]
 *
 * Handles the management of user sessions across the application
 *
 * @package Auth
 * @author Andrew O'Rourke <andrew.orourke@barbon.com>
 */
class Session
{
    /**
     * @var Session
     */
    private static $instance = null;

    /**
     * @var \Predis\Client
     */
    private $redis;

    /**
     * @var string
     */
    private $sessionID;

    /**
     * @var stdClass
     */
    private $sessionData;

    /**
     * Session constructor.
     */
    private function __construct()
    {
        $this->redis = new \Predis\Client(REDIS_CONNECTION);

        $this->sessionID = Cookie::get('s', null);
        if(is_null($this->sessionID)) {
            $this->sessionID = substr(hash('md5', uniqid()), 0, 8);
            Cookie::set('s', $this->sessionID);
        }
        $this->sessionData = json_decode(
            $this->redis->get($this->sessionID)
        );
    }

    /**
     * Session destructor
     */
    public function __destruct()
    {
        $this->redis->set(
            $this->sessionID,
            json_encode($this->sessionData)
        );
    }

    /**
     * Returns the singleton instance
     *
     * @return Session
     */
    public static function current()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Session();
        }
        return self::$instance;
    }

    /**
     * Returns the logged in user
     *
     * @return mixed
     */
    public function getLoggedInUser()
    {
        return User::factory()->where('username', $this->__get('id'))->find_one();
    }

    /**
     * Clears the session
     *
     * @return void
     */
    public function clear()
    {
        $this->sessionData = new \stdClass();
    }

    /**
     * Magic Method - Handles getter and setter functions
     *
     * @param string $method
     * @param array $arguments
     * @return $this|mixed
     */
    public function __call($method, $arguments)
    {
        if(preg_match('/^(s|g)et[A-Z]\w*$/', $method)) {
            $property = lcfirst(substr($method, 3));
            $type = substr($method, 0, 3);
            if ($type === 'set') {
                $value = $arguments[0];
                $this->__set($property, $value);
                return $this;
            } else if ($type === 'get') {
                return $this->__get($property);
            }
        }
    }

    /**
     * Magic Method - Setter
     *
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->sessionData->$name = $value;
    }

    /**
     * Magic Method - Getter
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if($this->__isset($name)) {
            return $this->sessionData->$name;
        }
        return null;
    }

    /**
     * Magic Method - Is Set
     *
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->sessionData->$name);
    }

    /**
     * Magic Method - Unsetter
     *
     * @param $name
     * @return void
     */
    public function __unset($name)
    {
        unset($this->sessionData->$name);
    }
}