<?php
namespace Selene\Modules\Admin\Models;

use Selene\Contracts\UserInterface;
use Selene\DataObject;

class User extends DataObject implements UserInterface
{
  public $fieldNames       = ['username', 'password', 'token', 'registrationDate', 'lastLogin', 'role', 'active'];
  public $primaryKeyName   = 'username';
  public $tableName        = 'users';
  public $primarySortField = 'username';
  public $filterFields     = ['role', 'active'];
  public $orderBy          = 'username';

  public $username;
  public $password;
  public $token;
  public $registrationDate;
  public $lastLogin;
  public $role;
  public $active;

  public function insert ($insertFiles = true)
  {
    $this->lastLogin = self::now ();
    parent::insert ($insertFiles);
  }

  public function findByName ($username)
  {
    $this->username = $username;
    return $this->read ();
  }

  function verifyPassword ($password)
  {
    if ($password == $this->password) {
      // Migrate plain text password to hashed version.
      $this->password ($password);
      $this->update ();
      return true;
    }
    return password_verify ($password, $this->password);
  }

  function id ($set = null)
  {
    if (isset($set))
      $this->username = $set;
    return $this->username;
  }

  function realName ()
  {
    return ucfirst ($this->username);
  }

  function username ($set = null)
  {
    if (isset($set))
      $this->username = $set;
    return $this->username;
  }

  function password ($set = null)
  {
    if (isset($set))
      $this->password = password_hash ($set, PASSWORD_BCRYPT);
    return $this->password;
  }

  function token ($set = null)
  {
    if (isset($set))
      $this->token = $set;
    return $this->token;
  }

  function registrationDate ($set = null)
  {
    if (isset($set))
      $this->registrationDate = $set;
    return $this->registrationDate;
  }

  function lastLogin ($set = null)
  {
    if (isset($set))
      $this->lastLogin = $set;
    return $this->lastLogin;
  }

  function role ($set = null)
  {
    if (isset($set))
      $this->role = $set;
    return $this->role;
  }

  function active ($set = null)
  {
    if (isset($set))
      $this->active = $set;
    return $this->active;
  }

  function onLogin ()
  {
    $this->lastLogin = self::now ();
    $this->update ();
  }
}