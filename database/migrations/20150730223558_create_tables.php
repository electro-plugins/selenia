<?php

use Phinx\Migration\AbstractMigration;

class CreateTables extends AbstractMigration
{
  /**
   * Change Method.
   *
   * Write your reversible migrations using this method.
   *
   * More information on writing migrations is available here:
   * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
   */
  public function change ()
  {
    if (!$this->table ('users')->exists ()) {
      $this
        ->table ('users')
        ->addColumn ('username', 'string', ['limit' => 30])
        ->addIndex (['username'], ['unique' => true])
        ->addColumn ('password', 'string', ['limit' => 60])
        ->addColumn ('realName', 'string', ['limit' => 30])
        ->addColumn ('token', 'string', ['limit' => 60, 'null' => true])
        ->addColumn ('created_at', 'datetime')
        ->addColumn ('updated_at', 'datetime')
        ->addColumn ('lastLogin', 'datetime', ['null' => true])
        ->addColumn ('role', 'integer')
        ->addColumn ('active', 'boolean', ['default' => 0])
        ->create ();
      $now = date ('Y-m-d H:i:s');
      $this->execute ("
      INSERT INTO users (username, created_at, role, active)
      VALUES ('admin', '$now', 2, 1);
");
    }

    if (!$this->table ('files')->exists ()) {
      $this
        ->table ('files', ['id' => false, 'primary_key' => ['id']])
        ->addColumn ('id', 'string', ['limit' => 13])
        ->addColumn ('name', 'string', ['limit' => 64])
        ->addColumn ('ext', 'string', ['limit' => 4])
        ->addColumn ('owner_type', 'string', ['limit' => 45])
        ->addColumn ('owner_id', 'integer')
        ->addColumn ('created_at', 'datetime')
        ->addColumn ('updated_at', 'datetime')
        ->addColumn ('image', 'boolean')
        ->addColumn ('path', 'string', ['limit' => 1024])
        ->addColumn ('metadata', 'string', ['limit' => 1024, 'null' => true])
        ->addColumn ('sort', 'integer', ['default' => 0])
        ->addIndex (['owner_type', 'owner_id'])
        ->addIndex ('image')
        ->addIndex ('sort')
        ->create ();
    }
/*
    $this
      ->table ('strings', ['id' => false, 'primary_key' => ['id', 'lang']])
      ->addColumn ('id', 'string', ['limit' => 13])
      ->addColumn ('lang', 'string', ['limit' => 5])
      ->addColumn ('from', 'string', ['limit' => 45, 'null' => true])
      ->addColumn ('text', 'string', ['limit' => 255])
      ->addIndex ('from')
      ->create ();

    $this
      ->table ('texts', ['id' => false, 'primary_key' => ['id', 'lang']])
      ->addColumn ('id', 'string', ['limit' => 13])
      ->addColumn ('lang', 'string', ['limit' => 5])
      ->addColumn ('from', 'string', ['limit' => 45, 'null' => true])
      ->addColumn ('text', 'text')
      ->addIndex ('from')
      ->create ();
    */
  }
}
