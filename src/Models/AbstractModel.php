<?php

namespace Reservations\Models;

use PDO;

/**
 * Responsible for storing a connection to the DB.
 */
abstract class AbstractModel 
{
    protected $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
}
