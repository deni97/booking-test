<?php

namespace Reservations\Controllers;

use Reservations\Models\TESTRegisterModel;

/**
 * FOR TESTING PURPOSES ONLY
 */
class TESTRegisterController extends AbstractController
{
    /**
     * Tries to register an user.
     * 
     * @return string a page displaying the result
     */
    public function register(): string
    {
        // Returns if trying to access it without submitting a form
        if (!$this->request->isPost()) {
            return $this->render('register.twig', []);
        }

        $params = $this->request->getParams();

        // If email and/or password is not present,
        // return a page with an error message
        if (!$params->has('email')) {
            $params = ['errorMessage' => 'Specify email.'];
            return $this->render('register.twig', $params);
        }
        
        if (!$params->has('password')) {
            $params = ['errorMessage' => 'Specify password.'];
            return $this->render('register.twig', $params);
        }

        $registerModel = new TESTRegisterModel($this->db);
        // Hashes a password using php's default hashing algo
        $hash = password_hash($params->getString('password'), PASSWORD_DEFAULT);
        $email = $params->getString('email');
        
        // Tries to register a user in the DB
        # TO-DO: exception handling
        $registerModel->register($email, $hash);

        $params = [
            'message' => "Registered $email"
        ];

        return $this->render('register.twig', $params);
    }
}
