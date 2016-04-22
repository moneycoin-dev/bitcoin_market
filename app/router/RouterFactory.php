<?php

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


class RouterFactory
{
	/**
	 * @return Nette\Application\IRouter
	 */
	public static function createRouter()
	{
		$router = new RouteList;
		$router[] = new Route('<presenter>/<action>[/<id>]', 'Entry:in');
                $router[] = new Route('login', 'Login:in');
                $router[] = new Route('listings', 'Listings:in');
                $router[] = new Route('<presenter>/<action>/<id>', 'Listings:EditListing');
                $router[] = new Route('<presenter>/<action>/<id>', 'Profile:View');
                $router[] = new Route('<presenter>/<action>/<id>', 'Listings:View');
                $router[] = new Route('<presenter>/<action>/<id>', 'Listings:Buy');
                $router[] = new Route('orders', 'Orders:in');
                $router[] = new Route('sales', 'Sales:in');
                $router[] = new Route('register', 'Register:in');
                $router[] = new Route('dashboard', 'Dashboard:in');
                $router[] = new Route('settings', 'Settings:in');
                $router[] = new Route('messages', 'Messages:in');
                $router[] = new Route('wallet', 'Wallet:in');
                         
		return $router;
	}
}
