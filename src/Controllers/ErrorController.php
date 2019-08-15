<?php

namespace Reservations\Controllers;

class ErrorController extends AbstractController
{
    /**
     * A function responsible for rendering an error page.
     * 
     * @return string not found page
     */
    public function notFound(): string
    {
        $properties = ['errorMessage' => 'Page not found!'];
        return $this->render('error.twig', $properties);
    }
}
