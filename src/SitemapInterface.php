<?php

namespace pendalf89\sitemap;

/**
 * Interface SitemapInterface
 * Интерфейс класса карты сайта.
 */
interface SitemapInterface
{
	/**
	 * Возвращает название карты сайта (соответствует имени файла без разрешения).
	 *
	 * Например: 'sitemap-articles', 'sitemap-news' и т.д.
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Возвращает список урлов карты сайта.
	 * Ключ 'lastmod' не обязателен.
	 *
	 * Например:
	 * ```
	 *  [
	 *      ['loc'=> 'http://site.com/1', 'lastmod' => '2016-31-08'],
	 *      ['loc'=> 'http://site.com/2', 'lastmod' => '2016-31-08'],
	 *      ['loc'=> 'http://site.com/3'],
	 *  ]
	 * ```
	 *
	 * @return array
	 */
	public function getUrls();
}
