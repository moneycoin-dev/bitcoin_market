<?php

namespace App\Model;

use Nette;
use Nette\Security\Passwords;
use dibi;

/**
 * Users management.
 */
class UserManager extends Nette\Object implements Nette\Security\IAuthenticator
{
    const   TABLE_NAME = "users",
            COLUMN_ID = "id",
            COLUMN_NAME = "login",
            COLUMN_PASSWORD_HASH = "password",
            COLUMN_ROLE = "access_level";

    private $database;

    public $table= "users";

    public function __construct(\DibiConnection $db)
    {
            $this->database = $db;
    }

    /**
     * Performs an authentication.
     * @return Nette\Security\Identity
     * @throws Nette\Security\AuthenticationException
     */
    public function authenticate(array $credentials)
    {
        $username = $credentials[0];
        $password = $credentials[1];

        $row = $this->database->select("*")->from("users")
                ->where("login = %s", $username)
                ->fetch();

        if (!$row) {
            throw new Nette\Security\AuthenticationException("The username is incorrect.", self::IDENTITY_NOT_FOUND);

        } elseif (!Passwords::verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
            throw new Nette\Security\AuthenticationException("The password is incorrect.", self::INVALID_CREDENTIAL);

        } elseif (Passwords::needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
            $row->update(array(self::COLUMN_PASSWORD_HASH => Passwords::hash($password),));
        }

        $arr = $row->toArray();
        unset($arr[self::COLUMN_PASSWORD_HASH]);
        return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);
    }
    
    public function hasFEallowed($login){
       $q = dibi::select("fe_allowed")->from("users")->where("login = %s", $login)
               ->fetch()["fe_allowed"];
       
       if (isset($q)){
           return TRUE;
        }
       
       return FALSE;
    }
    
    public function allowFE($login){
        dibi::update("users", array("fe_allowed" => "yes"))
                ->where("login = %s", $login)->execute();           
    }
}
