<?php

namespace Reservations\Controllers;

use Reservations\Models\TESTRegisterModel;

/**
 * FOR TESTING PURPOSES ONLY
 */

class TESTRegisterController extends AbstractController
{
    public function register(): string
    {
        if (!$this->request->isPost()) {
            return $this->render('register.twig', []);
        }

        $params = $this->request->getParams();

        if (!$params->has('email')) {
            $params = ['errorMessage' => 'Specify email.'];
            return $this->render('register.twig', $params);
        }
        
        if (!$params->has('password')) {
            $params = ['errorMessage' => 'Specify password.'];
            return $this->render('register.twig', $params);
        }

        $registerModel = new TESTRegisterModel($this->db);
        $hash = password_hash($params->getString('password'), PASSWORD_DEFAULT);
        $email = $params->getString('email');

        $registerModel->register($email, $hash);

        $params = [
            'message' => "Registered $email"
        ];

        return $this->render('register.twig', $params);
    }
}