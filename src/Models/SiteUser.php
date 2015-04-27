<?php
namespace Selene\Modules\Admin\Models;

class SiteUser extends \DataObject
{
  public $fieldNames = ['id', 'email', 'password', 'created_at', 'updated_at', 'lastaccess_at', 'active'];

  public $tableName        = 'appusers';
  public $primarySortField = 'email';
  public $booleanFields    = ['active'];

  public $id;
  public $email;
  public $password;
  public $created_at;
  public $updated_at;
  public $lastaccess_at;
  public $active;

}







