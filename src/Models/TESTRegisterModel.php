<?php

namespace Reservations\Models;

use Reservations\Exceptions\NotFoundException;

class TESTRegisterModel extends AbstractModel
{
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