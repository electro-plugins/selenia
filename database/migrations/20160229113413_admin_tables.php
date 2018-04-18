<?php
use Carbon\Carbon;
use Electro\Interfaces\UserInterface;
use Electro\Plugins\IlluminateDatabase\AbstractMigration;
use Illuminate\Database\Schema\Blueprint;

class AdminTables extends AbstractMigration
{
  /**
   * Reverse the migration.
   *
   * @return void
   */
  function down ()
  {
    $schema = $this->db->schema ();

    if ($this->db->hasTable ('files')) {
      $schema->drop ('files');
      $this->output->writeln ("  Dropped table <info>files</info>.");
    }
    else $this->output->writeln (" == Table <info>files</info> doesn't exist. Skipped.");

    if ($this->db->hasTable ('users')) {
      $schema->drop ('users');
      $this->output->writeln ("  Dropped table <info>users</info>.");
    }
    else $this->output->writeln (" == Table <info>users</info> doesn't exist. Skipped.");
  }

  /**
   * Run the migration.
   *
   * @return void
   */
  function up ()
  {
    $schema = $this->db->schema ();

    if (!$this->db->hasTable ('users')) {
      $schema->create ('users', function (Blueprint $t) {
        $t->increments ('id');
        $t->timestamps ();
        $t->timestamp ('lastLogin')->nullable ();
        $t->timestamp ('registrationDate');
        $t->string ('username', 30)->unique ();
        $t->string ('email', 100)->unique ();
        $t->string ('password', 60);
        $t->string ('realName', 30);
        $t->tinyInteger ('role');
        $t->tinyInteger ('enabled')->default (true);
        $t->boolean ('active')->default (false);
        $t->string ('token', 100);
      });
      $now = Carbon::now ();
      $this->db->table ('users')->insert ([
        'username'         => 'admin',
        'password'         => '',
        'realName'         => 'Admin',
        'email'            => 'Admin',
        'role'             => UserInterface::USER_ROLE_ADMIN,
        'created_at'       => $now,
        'updated_at'       => $now,
        'registrationDate' => $now,
        'active'           => true,
        'enabled'          => true,
        'token'            => '',
      ]);
    }
    else $this->output->writeln (" == Table <info>users</info> already exists. Skipped.");

    if (!$this->db->hasTable ('files'))
      $schema->create ('files', function (Blueprint $t) {
        $t->uuid ('id');
        $t->timestamps ();
        $t->string ('name', 64);
        $t->string ('ext', 4);
        $t->string ('mime', 129);
        $t->morphs ('owner');
        $t->string ('group', 16)->nullable ();
        $t->boolean ('image')->default (false);
        $t->string ('path', 255);
        $t->string ('metadata', 1024)->nullable ();

        $t->primary ('id');
        $t->index ('image');
        $t->index ('path');
      });

  }

}
