<?php

namespace pendalf89\sitemap;

use Yii;
use yii\base\Component;

/**
 * Class Sitemap
 * Класс предназначен для создания карты сайта
 *
 * @package common\components
 */
class SitemapGenerator extends Component
{
	/**
	 * @var string путь для записи файлов карты сайта. Допускается использование алиасов.
	 */
	public $path = '';

	/**
	 * @var string базовый url (адрес сайта с протоколом, например: http://www.site.com)
	 */
	public $baseUrl = '';

	/**
	 * @var string формат записи последнего изменеия страницы
	 */
	public $lastmodFormat = 'Y-m-d';

	/**
	 * @var string название индексного файла карты сайта
	 */
	public $indexFilename = 'sitemap.xml';

	/**
	 * @var int максимальное количество адресов в одной карте.
	 * Если в карте сайта количество адресов больше чем заданное значение,
	 * то карта сайта разобьётся на несколько карт сайта таким образом,
	 * чтобы в каждой было не больше адресов, чем заданное значение.
	 * Если стоит "0", то карты не будут разбиваться на несколько и в одной карте может быть
	 * неограниченное количество адресов.
	 */
	public $maxUrlsCount = 45000;

	/**
	 * @var array хранит информацию о созданных карт сайта
	 */
	protected $createdSitemaps = [];

	/**
	 * Создаёт индексную карту сайта
	 *
	 * @return bool
	 */
	public function createIndexSitemap()
	{
		self::sortByLastmod($this->createdSitemaps);

		$sitemapIndex =
			'<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		foreach ($this->createdSitemaps as $sitemap) {
			$sitemapIndex .= "<sitemap><loc>$this->baseUrl/$sitemap[loc]</loc>";
			if (!empty($sitemap['lastmod'])) {
				$lastmod = $this->formatLastmod($sitemap['lastmod']);
				$sitemapIndex .= "<lastmod>$lastmod</lastmod>";
			}
			$sitemapIndex .= '</sitemap>';
		}
		$sitemapIndex .= '</sitemapindex>';

		return (bool) $this->createSitemapFile($this->indexFilename, $sitemapIndex);
	}

	/**
	 * Создаёт файл/файлы карты сайта.
	 *
	 * @param string $sitemap название карты сайта
	 * @param array $urls массив урлов
	 *
	 * @return boolean
	 */
	public function createSitemap($sitemap, $urls)
	{
		$chunkUrls           = $this->chunkUrls($urls);
		$multipleSitemapFlag = count($chunkUrls) > 1;
		$i                   = 1;

		foreach ($chunkUrls as $urlsData) {
			$urlset = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
			foreach ($urlsData as $url) {
				$loc = substr_count($url['loc'], '://') ? $url['loc'] : $this->baseUrl . $url['loc'];
				$urlset .= "<url><loc>$loc</loc>";
				if (!empty($url['lastmod'])) {
					$lastmod = $this->formatLastmod($url['lastmod']);
					$urlset .= "<lastmod>$lastmod</lastmod>";
				}
				$urlset .= '</url>';
			}
			$urlset .= '</urlset>';

			$currentSitemapFilename = $multipleSitemapFlag ? "{$sitemap}-{$i}.xml" : "{$sitemap}.xml";
			if (!$this->createSitemapFile($currentSitemapFilename, $urlset)) {
				return false;
			}

			$this->createdSitemaps[] = [
				'loc'              => $currentSitemapFilename,
				'lastmod'          => !empty($urlsData[0]['lastmod']) ? $urlsData[0]['lastmod'] : null,
				'lastmodTimestamp' => !empty($urlsData[0]['lastmod']) ? strtotime($urlsData[0]['lastmod']) : null,
			];
			$i++;
		}

		return true;
	}

	/**
	 * Форматирует $lastmod в формат $this->lastmodFormat
	 *
	 * @param string $lastmod дата в формате "Y-m-d H:i:s"
	 *
	 * @return bool|string
	 */
	protected function formatLastmod($lastmod)
	{
		return date($this->lastmodFormat, strtotime($lastmod));
	}

	/**
	 * Разбивает массив урлов в соответствии с $this->maxUrlsCount.
	 * Обёртка для функции array_chunk().
	 *
	 * @param array $urls
	 *
	 * @return array
	 */
	protected function chunkUrls(array $urls)
	{
		if (!$this->maxUrlsCount) {
			$result[] = $urls;

			return $result;
		}

		return array_chunk($urls, $this->maxUrlsCount);
	}

	/**
	 * Сортирует урлы по lastmodTimestamp в убывающем порядке
	 *
	 * @param array $items
	 */
	protected static function sortByLastmod(array &$items)
	{
		$lastmodTimestamps = [];

		foreach ($items as $key => $row) {
			$lastmodTimestamps[$key] = !empty($row['lastmodTimestamp']) ? $row['lastmodTimestamp'] : 0;
		}

		array_multisort($lastmodTimestamps, SORT_DESC, $items);
	}

	/**
	 * Создаёт файл карты сайта
	 *
	 * @param $filename
	 * @param $data
	 *
	 * @return int
	 */
	protected function createSitemapFile($filename, $data)
	{
		$fullFilename = Yii::getAlias($this->path) . '/' . $filename;

		return file_put_contents($fullFilename, $data);
	}
}
