<?php
namespace Selene\Modules\Admin\Models;

class User extends \DataObject
{
  public $fieldNames       = ['username', 'password'];
  public $primaryKeyName   = 'username';
  public $tableName        = 'users';
  public $primarySortField = 'username';

  public $username;
  public $password;

}
