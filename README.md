Yii2 sitemap
================

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pendalf89/yii2-sitemap "*"
```

or add

```
"pendalf89/yii2-sitemap": "*"
```

to the require section of your `composer.json` file.

Apply migration
```sh
yii migrate --migrationPath=vendor/pendalf89/yii2-sitemap/src/migrations
```

Configuration:

```php
'components' => [
    'sitemap' => [
        'class' => 'pendalf89\sitemap\Sitemap',
        'sitemaps'  => [
        		'frontend\sitemaps\ArticlesSitemap', // see example of class below
        		'frontend\sitemaps\OtherSitemap', // see example of class below
        	],
        'generator' => [
            'class'   => 'pendalf89\sitemap\SitemapGenerator',
            'path'    => '@frontend/web',
            'baseUrl' => 'https://example.com',
        ],
    ],
],
```

Usage
------------
In first, create sitemap classes, for example:

```php
namespace frontend\sitemaps;

use pendalf89\sitemap\SitemapInterface;

class OtherSitemap implements SitemapInterface
{
	/**
	 * @inheritdoc
	 */
	public function getName()
	{
		return 'sitemap-other';
	}

	/**
	 * @inheritdoc
	 */
	public function getUrls()
	{
		return [
			['loc' => '/any-url/'],
			['loc' => '/any-url-width-date/', 'lastmod' => '2016-09-02 12:23:17'],
		];
	}
}
```

So, when you create sitemap classes, you can use component for create sitemap files.
 
```php
Yii::$app->sitemap->update();
```

Also, you can update one url in DB:

```php
Yii::$app->sitemap->updateUrl('/any-url-width-date/', '2016-09-02 12:23:17');
```
