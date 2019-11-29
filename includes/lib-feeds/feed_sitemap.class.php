<?php
class feed_sitemap extends data_feed {

	private $domain = 'https://www.cablesandkits.com';
	private $cdn = 'https://media.cablesandkits.com';
	private $static;

	public function __construct() {
		mb_internal_encoding('UTF-8'); // a stated requirement (the encoding, not the function call) of godatafeed
		$this->child_called = TRUE;

		$this->static = $this->cdn.'/static';

		parent::__construct(self::OUTPUT_NONE, self::DELIM_NONE, self::FILE_NONE);
	}

	public function __destruct() {
		parent::__destruct(); // write the file
	}

	public function build() {
		require_once(__DIR__.'/../engine/vendor/autoload.php');
		require_once(__DIR__.'/../engine/framework/ck_content.class.php');
		require_once(__DIR__.'/../engine/framework/ck_template.class.php');
		require_once(__DIR__.'/../engine/framework/canonical_page.class.php');

		$todays_date = new DateTime();
		$date = $todays_date->format('Y-m-d');

		/*
		//---build sitemap index
		*/

		$sitemap_index = fopen(__DIR__.'/../../sitemap-index.xml', 'w');

		$cktpl = new ck_template(__DIR__.'/../templates', ck_template::NONE);
		$tpl = __DIR__.'/../templates/page-sitemap-index.mustache.xml';
		$cktpl->buffer = TRUE;
		$content_map = new ck_content();

		$content_map->context = CONTEXT;
		$content_map->{'start?'} = 1;
		$content_map->sitemaps[] = ['url' => $this->domain.'/sitemap.xml', 'last_modified' => $date];
		$content_map->sitemaps[] = ['url' => 'https://blog.cablesandkits.com/post-sitemap.xml', 'last_modified' => $date];

		$content_map->{'end?'} = 1;
		$content = $cktpl->content($tpl, $content_map);
		fwrite($sitemap_index, $content);
		fclose($sitemap_index);

		/*
		//---build main CK sitemap----
		*/

		$fh = fopen(__DIR__.'/../../sitemap.xml', 'w');

		$cktpl = new ck_template(__DIR__.'/../templates', ck_template::NONE);
		$tpl = __DIR__.'/../templates/page-sitemap.mustache.xml';
		$cktpl->buffer = TRUE;
		$content_map = new ck_content();

		$content_map->context = CONTEXT;


		// single pages
		$content_map->{'start?'} = 1;
		$content_map->urls = [];

		// home page
		$content_map->urls[] = ['location' => $this->domain, 'priority' => '1.0', 'last_modified' => $date, 'change_frequency' => 'daily'];

		// one-off pages
		$content_map->urls[] = ['location' => $this->domain.'/dow', 'priority' => '0.9', 'last_modified' => $date, 'change_frequency' => 'daily'];
		$content_map->urls[] = ['location' => $this->domain.'/whyck.php', 'priority' => '0.5', 'last_modified' => $date, 'change_frequency' => 'daily'];
		$content_map->urls[] = ['location' => $this->domain.'/custserv.php', 'priority' => '0.5', 'last_modified' => $date, 'change_frequency' => 'daily'];
		$content_map->urls[] = ['location' => $this->domain.'/contact_us.php', 'priority' => '0.5', 'last_modified' => $date, 'change_frequency' => 'daily'];

		// page includer pages
		//$content_map->urls[] = ['location' => $this->domain.'/pi/returns', 'priority' => '0.5', 'last_modified' => $date, 'change_frequency' => 'daily'];

		// info manager pages
		if ($info_pages = prepared_query::fetch('SELECT information_id, info_title FROM information WHERE visible = 1 AND sitewide_header = 0 ORDER BY v_order ASC')) {
			foreach ($info_pages as $info_page) {
				$link = $this->domain.'/'.CK\fn::simple_seo($info_page['info_title'], '-i-'.$info_page['information_id'].'.html');
				$content_map->urls[] = ['location' => $link, 'priority' => '0.5', 'last_modified' => $date, 'change_frequency' => 'daily'];
			}
		}

		// custom pages
		if ($custom_pages = ck_custom_page::get_all('active')) {
			foreach ($custom_pages as $custom_page) {
				if ($custom_page['sitewide_header']) continue;
				$link = $this->domain.$custom_page['url_identifier'].$custom_page['url'];
				$content_map->urls[] = ['location' => $link, 'priority' => '0.5', 'last_modified' => $date, 'change_frequency' => 'daily'];
			}
		}

		$content = $cktpl->content($tpl, $content_map);
		fwrite($fh, $content);

		unset($content_map->{'start?'});
		$content_map->urls = [];

		$batch_size = 20;

		$category_ids = prepared_query::fetch('SELECT DISTINCT c.categories_id FROM categories c JOIN categories_description cd ON c.categories_id = cd.categories_id WHERE c.disabled = 0 and c.inactive = 0 ORDER BY c.parent_id ASC, c.sort_order ASC, cd.categories_name ASC', cardinality::COLUMN);
		foreach ($category_ids as $idx => $category_id) {
			$category = new ck_listing_category($category_id);

			if (!$category->found()) continue;
			if (!$category->is_viewable()) continue;
			if (!$category->url_is_canonical()) continue;

			$content_map->urls[] = ['location' => $this->domain.$category->get_url(), 'priority' => '0.6', 'last_modified' => $date, 'change_frequency' => 'daily'];

			if ($idx+1 % $batch_size == 0) {
				$content = $cktpl->content($tpl, $content_map);
				fwrite($fh, $content);
				$content_map->urls = [];
			}
		}

		/*if ($family_containers = ck_family_container::get_all_family_containers()) {
			foreach ($family_containers as $idx => $family) {
				if (!$family->is_viewable()) continue;

				$content_map->urls[] = ['location' => $this->domain.$family->get_url(), 'priority' => '0.8', 'last_modified' => $date, 'change_frequency' => 'daily'];

				if ($idx+1 % $batch_size == 0) {
					$content = $cktpl->content($tpl, $content_map);
					fwrite($fh, $content);
					$content_map->urls = [];
				}
			}
		}*/

		$products_ids = prepared_query::fetch('SELECT p.products_id FROM products p JOIN products_stock_control psc ON p.stock_id = psc.stock_id WHERE p.products_status = 1 AND psc.dlao_product = 0 ORDER BY p.products_model ASC', cardinality::COLUMN);
		foreach ($products_ids as $idx => $products_id) {
			$product = new ck_product_listing($products_id);

			if (!$product->found()) continue; // can't do anything past this point
			if (!$product->get_ipn()->found()) continue; // some stuff may or may not work, but we don't care that much
			if (!$product->is_viewable()) continue;
			if (!$product->url_is_canonical()) continue;

			$content_map->urls[] = ['location' => $this->domain.$product->get_url(), 'priority' => '0.7', 'last_modified' => $date, 'change_frequency' => 'daily'];

			if ($idx+1 % $batch_size == 0) {
				$content = $cktpl->content($tpl, $content_map);
				fwrite($fh, $content);
				$content_map->urls = [];
			}
		}

		$content_map->{'end?'} = 1;

		$content = $cktpl->content($tpl, $content_map);
		fwrite($fh, $content);
		fclose($fh);
	}
}
?>
