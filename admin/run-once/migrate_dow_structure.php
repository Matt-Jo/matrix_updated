<?php
require_once(__DIR__.'/../../includes/application_top.php');

$cli = PHP_SAPI==='cli'?TRUE:FALSE;

@ini_set("memory_limit","2048M");
set_time_limit(0);

$start = time();

prepared_query::execute('INSERT INTO ck_dow_campaigns (name, first_date, last_date, simultaneous_products, deal_length_days, deal_length_hours, deal_length_minutes, active, created_date, legacy_dow_schedule_id) SELECT p.products_model, ds.start_date, DATE_ADD(ds.start_date, INTERVAL 7 DAY), 0, 7, 0, 0, ds.active, ds.entered, ds.dow_schedule_id FROM ck_dow_schedule ds JOIN products p ON ds.products_id = p.products_id ORDER BY ds.dow_schedule_id ASC');

prepared_query::execute('INSERT INTO ck_dow_deals (dow_campaign_id, products_id, deal_start, deal_end, custom_description, custom_legalese, create_specials_price, active, created_date, legacy_dow_schedule_id) SELECT dc.dow_campaign_id, ds.products_id, ds.start_date, DATE_ADD(ds.start_date, INTERVAL 7 DAY), ds.custom_description, ds.legalese, ds.specials_price, ds.active, ds.entered, ds.dow_schedule_id FROM ck_dow_schedule ds JOIN ck_dow_campaigns dc ON ds.dow_schedule_id = dc.legacy_dow_schedule_id ORDER BY ds.dow_schedule_id ASC');

prepared_query::execute('INSERT INTO ck_dow_deal_recommendations (dow_deal_id, products_id, custom_name, sort_order, created_date, legacy_product_recommend_id) SELECT dd.dow_deal_id, pr.recommend_products_id, pr.custom_name, pr.ordinal, pr.entered, pr.product_recommend_id FROM ck_product_recommends pr JOIN ck_dow_deals dd ON pr.dow_schedule_id = dd.legacy_dow_schedule_id ORDER BY pr.product_recommend_id ASC');

echo 'Update took '.(time()-$start).' seconds';
?>
