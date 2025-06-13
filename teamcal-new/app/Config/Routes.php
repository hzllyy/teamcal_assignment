<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->post('availability/get-slots', 'Availability::getSlots');
$routes->get('availability/get-slots', 'Availability::getSlots');
