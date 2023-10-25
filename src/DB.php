<?php

namespace Wpjscc\React\Orm;

use Illuminate\Database\Capsule\Manager as Capsule;
use React\Promise\PromiseInterface;
use React\Promise\Deferred;
use React\Promise\Timer\TimeoutException;
use Wpjscc\MySQL\Pool;

class DB extends Capsule
{
    protected static $query;
    protected static $pool;

    public static function init(Pool $pool = null)
    {

        static::$query = $query = new Query();
        static::$pool = $pool;

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'database',
            'username' => 'root',
            'password' => 'password',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        Capsule::macro('setPretending', function () {
            $this->pretending = true;
        });


        // 同步执行的。马上返回
        Capsule::beforeExecuting(function ($sql, $bindings) use ($query) {
            Capsule::setPretending(); //让sql不执行
            $query->sql = $sql;
            $query->bindings = $bindings;
        });

        static::extendReactConnection();
    }

    public static function extendReactConnection()
    {

        Capsule::macro('beginReact', function ($reactConnection) {
            return $reactConnection->query('BEGIN');
        });

        Capsule::macro('commitReact', function ($reactConnection) {
            return $reactConnection->query('COMMIT');
        });

        Capsule::macro('rollbackReact', function ($reactConnection) {
            return $reactConnection->query('ROLLBACK');
        });
        
       
    }



    public static function execute($query)
    {
        $sql = static::$query->sql;
        $bindings = static::$query->bindings;
        static::$query->sql = '';
        static::$query->bindings = [];

        return static::$pool->query($sql, $bindings);

    }

    public static function executeTL($query, $connection)
    {
        $sql = static::$query->sql;
        $bindings = static::$query->bindings;

        static::$query->sql = '';
        static::$query->bindings = [];

        return $connection->query($sql, $bindings);

    }


    public static function translation(callable $callable)
    {
       return static::$pool->translation($callable);
    }

}
