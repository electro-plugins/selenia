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

    if ($schema->hasTable ('users'))
      $schema->drop ('users');
  }

  /**
   * Run the migration.
   *
   * @return void
   */
  function up ()
  {
    $schema = $this->db->schema ();

    if (!$schema->hasTable ('users')) {
      $schema->create ('users', function (Blueprint $table) {
        $table->increments ('id');
        $table->timestamps ();
        $table->timestamp ('lastLogin')->nullable ();
        $table->string ('username', 30)->unique ();
        $table->string ('password', 60);
        $table->string ('realName', 30);
        $table->tinyInteger ('role');
        $table->boolean ('active')->default (false);
        $table->rememberToken ();
      });
      $now = Carbon::now ();
      $this->db->table ('users')->insert ([
        'username'   => 'admin',
        'role'       => UserInterface::USER_ROLE_ADMIN,
        'created_at' => $now,
        'updated_at' => $now,
        'active'     => true,
      ]);
    }
    else $this->output->writeln (" == Table <info>users</info> already exists. Skipped.");

    if (!$schema->hasTable ('files'))
      $schema->create ('files', function (Blueprint $table) {
        $table->uuid ('id');
        $table->timestamps ();
        $table->string ('name', 64);
        $table->string ('ext', 4);
        $table->morphs ('owner');
        $table->boolean ('image')->default (false);
        $table->string ('path', 1024);
        $table->json ('metadata')->nullable ();
        $table->integer ('sort')->default (0);

        $table->primary ('id');
        $table->index ('owner_type');
        $table->index ('image');
        $table->index ('sort');
      });

  }

}
