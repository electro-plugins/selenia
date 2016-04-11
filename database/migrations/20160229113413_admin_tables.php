<?php
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Selenia\Interfaces\UserInterface;
use Selenia\Plugins\IlluminateDatabase\Migration;

class AdminTables extends Migration
{
  /**
   * Reverse the migration.
   *
   * @return void
   */
  function down ()
  {
    $schema = $this->db->schema ();

    if ($schema->hasTable ('files')) {
      $schema->drop ('files');
      $this->output->writeln ("  Dropped table <info>files</info>.");
    }

    if ($schema->hasTable ('users')) {
      $schema->drop ('users');
      $this->output->writeln ("  Dropped table <info>users</info>.");
    }
  }

  /**
   * Run the migration.
   *
   * @return void
   */
  function up ()
  {
    $schema = $this->db->schema ();
    $this->db->connection ()->transaction (function () use ($schema) {

      if (!$schema->hasTable ('users')) {
        $schema->create ('users', function (Blueprint $t) {
          $t->increments ('id');
          $t->timestamps ();
          $t->timestamp ('lastLogin')->nullable ();
          $t->string ('username', 30)->unique ();
          $t->string ('password', 60);
          $t->string ('realName', 30);
          $t->tinyInteger ('role');
          $t->boolean ('active')->default (false);
          $t->rememberToken ();
        });
        $now = Carbon::now ();
        $this->db->table ('users')->insert ([
          'username'   => 'admin',
          'password'   => '',
          'realName'   => 'Admin',
          'role'       => UserInterface::USER_ROLE_ADMIN,
          'created_at' => $now,
          'updated_at' => $now,
          'active'     => true,
        ]);
      }
      else $this->output->writeln (" == Table <info>users</info> already exists. Skipped.");

      if (!$schema->hasTable ('files'))
        $schema->create ('files', function (Blueprint $t) {
          $t->uuid ('id');
          $t->timestamps ();
          $t->string ('name', 64);
          $t->string ('ext', 4);
          $t->string ('mime', 129);
          $t->morphs ('owner');
          $t->string ('group', 16)->nullable ();
          $t->boolean ('image')->default (false);
          $t->string ('path', 40);
          $t->string ('metadata', 1024)->nullable ();

          $t->primary ('id');
          $t->index ('owner_type');
          $t->index ('image');
        });

    });
  }

}
