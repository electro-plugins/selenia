<?php
namespace Selene\Modules\Admin\Models;

use Selene\DataObject;

class User extends DataObject
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

}
