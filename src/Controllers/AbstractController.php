<?php

namespace Reservations\Controllers;

use Reservations\Core\Request;
use Reservations\Utils\DependencyInjector;

abstract class AbstractController
{
    protected $request;
    protected $db;
    protected $config;
    protected $view;
    protected $di;
    protected $user;

    public function __construct(DependencyInjector $di, Request $request) 
    {
        $this->request = $request;
        $this->di = $di;

        $this->db = $di->get('PDO');
        $this->view = $di->get('Twig_Environment');
        $this->config = $di->get('Utils\Config');
    }

    public function setUser(string $user): void
    {
        $this->user = $user;
    }

    protected function render(string $template, array $params): string 
    {
        return $this->view->loadTemplate($template)->render($params);
    }
}
