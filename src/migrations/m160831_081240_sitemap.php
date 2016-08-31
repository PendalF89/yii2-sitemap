<?php

use yii\db\Migration;

class m160831_081240_sitemap extends Migration
{
	public function up()
	{
		$this->createTable('sitemap', [
			'loc'     => $this->string(255),
			'sitemap' => $this->string(255)->notNull(),
			'lastmod' => $this->dateTime(),
			'PRIMARY KEY(loc)',
		]);
	}

	public function down()
	{
		$this->dropTable('sitemap');
	}
}
