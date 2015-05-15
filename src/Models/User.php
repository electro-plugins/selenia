<?php
namespace Selene\Modules\Admin\Models;

use Selene\Contracts\UserInterface;
use Selene\DataObject;

class User extends DataObject implements UserInterface
{
  public $fieldNames       = ['username', 'password', 'token', 'type', 'active'];
  public $primaryKeyName   = 'username';
  public $tableName        = 'users';
  public $primarySortField = 'username';
  public $filterFields     = ['type'];
  public $orderBy          = 'username';

  public $username;
  public $password;
  public $token;
  public $type;
  public $active;

  /**
   * Finds the user record searching by the username (which may or may not be the primary key).
   * @param string $username
   * @return bool True if the user was found.
   */
  public function findByName ($username)
  {
    $this->username = $username;
    return $this->read ();
  }

  /**
   * Gets or sets the user record's primary key.
   *
   * > Note: it may be the same as the username or it may be a numeric id.
   *
   * @param string $set A setter value.
   * @return string
   */
  function id ($set = null)
  {
    if (isset($set))
      $this->username = $set;
    return $this->username;
  }

  /**
   * Gets the user's "real" name, which may be displayed on the application UI.
   *
   * > This may be the same as the username.
   *
   * @return string
   */
  function realName ()
  {
    return ucfirst ($this->username);
  }

  /**
   * Gets or sets the login username.
   *
   * > This may actually be an email address, for instance.
   *
   * @param string $set A setter value.
   * @return string
   */
  function username ($set = null)
  {
    if (isset($set))
      $this->username = $set;
    return $this->username;
  }

  /**
   * Gets or sets the login password.
   *
   * @param string $set A setter value.
   * @return string
   */
  function password ($set = null)
  {
    if (isset($set))
      $this->password = $set;
    return $this->password;
  }

  /**
   * Gets or sets the user role.
   *
   * > The predefined roles are set as constants on {@see UserInterface}.
   *
   * @param string $set A setter value.
   * @return string
   */
  function role ($set = null)
  {
    if (isset($set))
      $this->type = $set;
    return $this->type;
  }

  /**
   * Gets or sets the active state of the user.
   *
   * > Only active users may log in.
   *
   * @param bool $set A setter value.
   * @return string
   */
  function active ($set = null)
  {
    if (isset($set))
      $this->active = $set;
    return $this->active;
  }

}