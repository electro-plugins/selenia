<?php
namespace Selenia\Plugins\AdminInterface\Models;

use Carbon\Carbon;
use Selenia\Interfaces\UserInterface;
use Selenia\Plugins\IlluminateDatabase\BaseModel;

class User extends BaseModel implements UserInterface
{
  public $gender     = '$USER_THE';
  public $plural     = '$USERS';
  public $singular   = '$USER';
  public $timestamps = true;

  protected $casts = [
    'active' => 'boolean',
  ];
  protected $dates = ['registrationDate', 'lastLogin'];

  function activeField ($set = null)
  {
    if (isset($set))
      $this->active = $set;
    return $this->active;
  }

  public function findById ($id)
  {
    /** @var static $user */
    $user = static::find ($id);
    if ($user) {
      $this->forceFill ($user->getAttributes ());
      $this->exists = true;
      return true;
    }
    return false;
  }

  public function findByName ($username)
  {
    /** @var static $user */
    $user = static::where ('username', $username)->first ();
    if ($user) {
      $this->forceFill ($user->getAttributes ());
      $this->exists = true;
      return true;
    }
    return false;
  }

  function idField ($set = null)
  {
    if (isset($set))
      $this->id = $set;
    return $this->id;
  }

  function lastLoginField ($set = null)
  {
    if (isset($set))
      $this->lastLogin = $set;
    return $this->lastLogin;
  }

  function onLogin ()
  {
    $this->lastLogin = Carbon::now ();
    $this->save ();
  }

  function passwordField ($set = null)
  {
    if (isset($set))
      $this->password = password_hash ($set, PASSWORD_BCRYPT);
    return $this->password;
  }

  function realNameField ($set = null)
  {
    if (isset($set))
      return $this->realName = $set;
    return $this->realName ?: ucfirst ($this->usernameField ());
  }

  function registrationDateField ($set = null)
  {
    if (isset($set))
      $this->created_at = $set;
    return $this->created_at;
  }

  function roleField ($set = null)
  {
    if (isset($set))
      $this->role = $set;
    return $this->role;
  }

  function tokenField ($set = null)
  {
    if (isset($set))
      $this->token = $set;
    return $this->token;
  }

  function usernameField ($set = null)
  {
    if (isset($set))
      $this->username = $set;
    return $this->username;
  }

  function verifyPassword ($password)
  {
    if ($password == $this->password) {
      // Migrate plain text password to hashed version.
      $this->passwordField ($password);
      $this->save ();
      return true;
    }
    return password_verify ($password, $this->password);
  }

}
