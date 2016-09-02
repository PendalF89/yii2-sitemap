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
	 * @var SitemapGenerator|array массив опций класса SitemapGenerator. После инициализцаии становится объектом.
	 */
	public $generator;

	/**
	 * @var SitemapModel|array массив опций класса SitemapModel. После инициализцаии становится объектом.
	 */
	public $model = ['class' => 'pendalf89\sitemap\SitemapModel'];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		$this->model     = Yii::createObject($this->model);
		$this->generator = Yii::createObject($this->generator);
	}

	/**
	 * Обновляет информацию о карте сайта в БД и создаёт файлы карты сайта.
	 */
	public function update()
	{
		$this->updateSitemapsInfo();
		$this->createFiles();
	}

	/**
	 * @inheritdoc
	 */
	public function updateUrl($loc, $lastmod)
	{
		return $this->model->updateUrl($loc, $lastmod);
	}

	/**
	 * Создаёт файлы карты сайта
	 */
	protected function createFiles()
	{
		/** @var SitemapInterface $sitemap */
		foreach ($this->sitemaps as $sitemapClass) {
			$sitemap = new $sitemapClass;
			if ($urls = $this->model->findUrls($sitemap->getName())) {
				$this->generator->createSitemap($sitemap->getName(), $urls);
			}
		}
		$this->generator->createIndexSitemap();
	}

	/**
	 * Последовательно запускает метод updateSitemap() для обновления информации о всех карт сайта.
	 * Список классов карт сайта находится в поле $this->sitemaps.
	 * После обновления, из БД удаляются неактуальные карты сайта.
	 */
	protected function updateSitemapsInfo()
	{
		/** @var SitemapInterface $sitemap */
		$actualSitemaps = [];
		foreach ($this->sitemaps as $sitemapClass) {
			$sitemap = new $sitemapClass;
			if ($this->updateSitemapInfo($sitemap)) {
				$actualSitemaps[] = $sitemap->getName();
			}
		}

		$nonActualSitemaps = array_diff($this->model->findSitemaps(), $actualSitemaps);
		foreach ($nonActualSitemaps as $sitemap) {
			$this->model->deleteSitemap($sitemap);
		}
	}

	/**
	 * Обновляет информацию о карте сайта в БД.
	 * Обратите внимание, что этот метод обновляет только информацию в БД, а не физический xml-файл.
	 *
	 * @param SitemapInterface $sitemap
	 *
	 * @return bool
	 */
	protected function updateSitemapInfo(SitemapInterface $sitemap)
	{
		if (!$urls = $sitemap->getUrls()) {
			return false;
		}

		foreach ($urls as $url) {
			$lastmod = isset($url['lastmod']) ? $url['lastmod'] : null;
			// Если урла нет в базе, то добавляем его...
			if (!$this->model->findUrl($url['loc'])) {
				$this->model->insertUrl($url['loc'], $sitemap->getName(), $lastmod);
			} elseif ($lastmod) { // ... иначе, если есть $lastmod, то обновляем его.
				$this->updateUrl($url['loc'], $lastmod);
			}
		}

		$nonActualUrls = array_diff(
			ArrayHelper::getColumn($this->model->findUrls($sitemap->getName()), 'loc'),
			ArrayHelper::getColumn($urls, 'loc')
		);

		foreach ($nonActualUrls as $url) {
			$this->model->deleteUrl($url);
		}

		return true;
	}
}
