<?php

namespace Reservations\Models;

use PDO;

/**
 * A parent class that is responsible for storing a connection to the DB.
 */
abstract class AbstractModel 
{
    protected $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
}
