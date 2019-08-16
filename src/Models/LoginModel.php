<?php

namespace Reservations\Models;

use Reservations\Exceptions\NotFoundException;
use PDO;

class LoginModel extends AbstractModel
{
    /**
     * A function that tries to fetch a hash for a certain specified email.
     * 
     * @param string $email
     * 
     * @return string hashed password
     */
    public function getHash(string $email): string
    {
        $query = 'SELECT * FROM staff WHERE email = :email';

        $stmt = $this->db->prepare($query);
        $stmt->execute(['email' => $email]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($data)) {
            throw new NotFoundException();
        }

        return $data['hash'];
    }
}
