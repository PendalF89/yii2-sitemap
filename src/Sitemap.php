<?php

namespace pendalf89\sitemap;

use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;

/**
 * Class Sitemap
 * Класс предназначен для создания карты сайта
 *
 * @package common\components
 */
class Sitemap extends Component
{
	/**
	 * @var array список классов карты сайта
	 */
	public $sitemaps = [];

	/**
	 * @var array опции генератора карты сайта
	 */
	public $generatorOptions = [];

	/**
	 * @var string id компонента для работы с БД
	 */
	public $db = 'db';

	/**
	 * Последовательно запускает метод updateSitemap() для обновления всех карт сайта.
	 * Список карт сайта находится в поле $this->sitemaps.
	 */
	public function updateAll()
	{
		/** @var SitemapInterface $sitemap */
		$actualSitemaps = [];
		foreach ($this->sitemaps as $sitemapClass) {
			$sitemap = new $sitemapClass;
			if ($this->updateSitemap($sitemap)) {
				$actualSitemaps[] = $sitemap->getName();
			}
		}

		$nonActualSitemaps = array_diff($this->findSitemaps(), $actualSitemaps);
		foreach ($nonActualSitemaps as $sitemap) {
			$this->deleteSitemap($sitemap);
		}
	}

	/**
	 * Обновляет информацию в базе о карте сайта.
	 * Обратите внимание, что этот метод обновляет только информацию в БД, а не физический xml-файл.
	 *
	 * @param SitemapInterface $sitemap
	 *
	 * @return bool
	 */
	public function updateSitemap(SitemapInterface $sitemap)
	{
		if (!$urls = $sitemap->getUrls()) {
			return false;
		}

		foreach ($urls as $url) {
			// Если урл уже в базе, то обновляем его...
			if ($this->findUrl($url['loc'])) {
				$this->updateUrl($url['loc'], $url['lastmod']);
			} else { // ... иначе, добавляем новый урл в базу
				$this->insertUrl($url['loc'], $sitemap->getName(), $url['lastmod']);
			}
		}

		$nonActualUrls = array_diff(
			ArrayHelper::getColumn($this->findUrls($sitemap->getName()), 'loc'),
			ArrayHelper::getColumn($urls, 'loc')
		);

		foreach ($nonActualUrls as $url) {
			$this->deleteUrl($url);
		}

		return true;
	}

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
			'UPDATE sitemap SET lastmod = :lastmod WHERE loc = :loc AND lastmod < :lastmod', [
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
	protected function findUrl($loc)
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
	protected function insertUrl($loc, $sitemap, $lastmod)
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
	protected function deleteUrl($loc)
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
	protected function findUrls($sitemap)
	{
		return $this->getDb()->createCommand('SELECT * FROM sitemap WHERE sitemap = :sitemap', [
			'sitemap' => $sitemap,
		])->queryAll();
	}

	/**
	 * Ищет все карты сайта
	 *
	 * @return array
	 */
	protected function findSitemaps()
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
	protected function deleteSitemap($sitemap)
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
