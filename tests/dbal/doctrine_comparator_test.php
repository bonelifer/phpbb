<?php
class phpbb_dbal_doctrine_comparator_test extends phpbb_test_case
{
	public function test_modified_index_is_preserved(): void
	{
		foreach ([new \Doctrine\DBAL\Platforms\MySQLPlatform(), new \Doctrine\DBAL\Platforms\PostgreSQLPlatform()] as $platform)
		{
			$old = new \Doctrine\DBAL\Schema\Table('articles');
			foreach (['article_id', 'word_id', 'title_match', 'descr_match'] as $column)
			{
				$old->addColumn($column, 'integer');
			}
			$old->addUniqueIndex(['article_id', 'word_id', 'title_match'], 'article_match');
			$new = clone $old;
			$new->dropIndex('article_match');
			$new->addUniqueIndex(['article_id', 'word_id', 'title_match', 'descr_match'], 'article_match');
			$comparator = new \phpbb\db\doctrine\comparator($platform);
			$queries = $platform->getAlterTableSQL($comparator->compareTables($old, $new));
			$this->assertNotEmpty($queries);
			$this->assertStringContainsString('descr_match', implode(' ', $queries));
		}
	}
	public function test_legacy_key_name_drops_the_generated_index_when_both_names_exist(): void
	{
		$schema = new \Doctrine\DBAL\Schema\Schema();
		$table = $schema->createTable('phpbb_kb_src_wrdmtch');
		foreach (['article_id', 'word_id', 'title_match', 'descr_match'] as $column)
		{
			$table->addColumn($column, 'integer');
		}
		$table->addUniqueIndex(['article_id', 'word_id', 'title_match'], 'kbsrcwrdmt_un_mtch');
		$table->addUniqueIndex(['article_id', 'word_id', 'title_match', 'descr_match'], 'un_mtch');
		$reflection = new \ReflectionClass(\phpbb\db\tools\doctrine::class);
		$tools = $reflection->newInstanceWithoutConstructor();
		$tools->set_table_prefix('phpbb_');
		$drop = $reflection->getMethod('schema_index_drop');
		$drop->invoke($tools, $schema, $table->getName(), 'un_mtch', true);
		$this->assertFalse($table->hasIndex('kbsrcwrdmt_un_mtch'));
		$this->assertTrue($table->hasIndex('un_mtch'));
	}

}
