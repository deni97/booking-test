<?php

namespace Reservations\Models;

use Reservations\Exceptions\NotFoundException;

/**
 * It is present here just for the ease of use.
 */
class TESTRegisterModel extends AbstractModel
{
    /**
     * Simply inserts an email and a hashed password into the DB.
     * 
     * @param string $email
     * @param string $hash hashed password
     * 
     * @return void
     */
    public function register(string $email, string $hash): void
    {
        $query = 'INSERT INTO staff(email, hash) VALUES (:email, :hash)';

        $stmt = $this->db->prepare($query);

        $params = [
            'email' => $email,
            'hash' => $hash
        ];

        if (!$stmt->execute($params)) {
            throw new DbException($stmt->errorInfo()[2]);
        }
    }
}
