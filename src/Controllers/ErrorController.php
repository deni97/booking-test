<?php

namespace Reservations\Controllers;

class ErrorController extends AbstractController
{
    /**
     * Renders an error page.
     * 
     * @return string not found page
     */
    public function notFound(): string
    {
        $properties = ['errorMessage' => 'Page not found!'];
        return $this->render('error.twig', $properties);
    }
}
