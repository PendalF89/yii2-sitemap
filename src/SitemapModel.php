<?php

namespace pendalf89\sitemap;

use Yii;
use yii\base\Object;

/**
 * Class SitemapModel
 * Модель карты сайта
 *
 * @package common\components
 */
class SitemapModel extends Object
{
	/**
	 * @var string id компонента для работы с БД
	 */
	public $db = 'db';

	/**
	 * Обновляет дату последнего изменения урла, если переданная дата больше, чем существующая.
	 *
	 * @param string $loc URL
	 * @param string $lastmod дата в формате "Y-m-d H:i:s"
	 *
	 * @return int
	 * @throws \yii\db\Exception
	 */
	public function updateUrl($loc, $lastmod)
	{
		return $this->getDb()->createCommand(
			'UPDATE sitemap SET lastmod = :lastmod WHERE loc = :loc AND (lastmod < :lastmod OR ISNULL(lastmod))', [
			'loc'     => $loc,
			'lastmod' => $lastmod,
		])->execute();
	}

	/**
	 * Ищет запись в базе по URL (loc)
	 *
	 * @param string $loc URL
	 *
	 * @return array|false
	 */
	public function findUrl($loc)
	{
		return $this->getDb()->createCommand('SELECT * FROM sitemap WHERE loc = :loc', ['loc' => $loc])->queryOne();
	}

	/**
	 * Добавляет новый URL в базу
	 *
	 * @param string $loc URL
	 * @param string $sitemap название карты сайта
	 * @param string $lastmod дата в формате "Y-m-d H:i:s"
	 *
	 * @return int
	 * @throws \yii\db\Exception
	 */
	public function insertUrl($loc, $sitemap, $lastmod = null)
	{
		return $this->getDb()->createCommand('INSERT INTO sitemap VALUES (:loc, :sitemap, :lastmod)', [
			'loc'     => $loc,
			'sitemap' => $sitemap,
			'lastmod' => $lastmod,
		])->execute();
	}

	/**
	 * Удаляет URL из базы
	 *
	 * @param string $loc URL
	 *
	 * @return int
	 * @throws \yii\db\Exception
	 */
	public function deleteUrl($loc)
	{
		return $this->getDb()->createCommand('DELETE FROM sitemap WHERE loc = :loc', ['loc' => $loc])->execute();
	}

	/**
	 * Ищет все URL карты сайта
	 *
	 * @param string $sitemap название карты сайта
	 *
	 * @return array
	 */
	public function findUrls($sitemap)
	{
		return $this->getDb()->createCommand('SELECT * FROM sitemap WHERE sitemap = :sitemap ORDER BY lastmod DESC', [
			'sitemap' => $sitemap,
		])->queryAll();
	}

	/**
	 * Ищет все карты сайта
	 *
	 * @return array
	 */
	public function findSitemaps()
	{
		return $this->getDb()->createCommand('SELECT sitemap FROM sitemap GROUP BY sitemap')->queryColumn();
	}

	/**
	 * Удаляет все урлы карты сайта $sitemap из базы
	 *
	 * @param string $sitemap название карты сайта
	 *
	 * @return int
	 * @throws \yii\db\Exception
	 */
	public function deleteSitemap($sitemap)
	{
		return $this->getDb()->createCommand('DELETE FROM sitemap WHERE sitemap = :sitemap', [
			'sitemap' => $sitemap,
		])->execute();
	}

	/**
	 * Возвращает компонент для работы с БД
	 *
	 * @return mixed|\yii\db\Connection
	 */
	protected function getDb()
	{
		return Yii::$app->{$this->db};
	}
}
