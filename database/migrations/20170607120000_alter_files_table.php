<?php

use Electro\Plugins\IlluminateDatabase\AbstractMigration;
use Illuminate\Database\Schema\Blueprint;

class AlterFilesTable extends AbstractMigration
{
  /**
   * Reverse the migration.
   *
   * @return void
   */
  function down ()
  {
    $schema = $this->db->schema ();

    if ($this->db->hasTable ('files')) 
	{
		$schema->table ('files', function (Blueprint $t) {
			$t->integer('owner_id')->unsigned()->change();
		});
    }
    else $this->output->writeln (" == Table <info>files</info> doesn't exist. Skipped.");
  }

  /**
   * Run the migration.
   *
   * @return void
   */
  function up ()
  {
    $schema = $this->db->schema ();

    if ($this->db->hasTable ('files'))
	{
		$schema->table ('files', function (Blueprint $t) {
			$t->integer('owner_id')->nullable()->change();
	    });
	}
	else $this->output->writeln (" == Table <info>files</info> doesn't exist. Skipped.");
  }
}
