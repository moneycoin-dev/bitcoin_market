<?php

namespace App\Auth;

use Nette\Security\Permission;

class AuthorizatorFactory
{
    /**
     * @return \Nette\Security\IAuthorizator
     */
    public function create()
    {
        $permission = new Permission();

        /* seznam uživatelských rolí */
        $permission->addRole('guest');
        $permission->addRole('registered');
        $permission->addRole('admin', 'registered');

        /* seznam zdrojů */
        $permission->addResource('dashboard');
        $permission->addResource('wallet');
		$permission->addResource('messages');
        $permission->addResource('settings');
        $permission->addResource('login');
        $permission->addResource('register');
        $permission->addResource('listings');
        $permission->addResource('profile');
        $permission->addResource('orders');

        /* seznam pravidel oprávnění */
        $permission->allow('registered', 'dashboard', 'list');
        $permission->allow('registered', 'wallet', 'list');
        $permission->allow('registered', 'messages', 'list');
        $permission->allow('registered', 'settings', 'list');
        $permission->allow('registered', 'listings', 'list');
        $permission->allow('registered', 'profile', 'list');
        $permission->allow('registered', 'orders', 'list');
        $permission->allow('guest', 'login', 'list');
        $permission->allow('guest', 'register', 'list');
        $permission->deny('registered', 'login', 'list');
        $permission->deny('registered', 'register', 'list');

        /* admin má práva na všechno */
        $permission->allow('admin', Permission::ALL, Permission::ALL);

        return $permission;
    }
}
