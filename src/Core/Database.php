<?php

namespace Jidaikobo\Kontiki\Core;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;

class Database
{
    protected Connection $connection;

    public function __construct(array $connection)
    {
        $capsule = new Capsule();
        $capsule->addConnection($connection);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        $this->connection = $capsule->getConnection();
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
