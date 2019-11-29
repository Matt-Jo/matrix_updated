<?php
// we'll probably need to do some resizing. we should probably use another method to ensure this class is available, but for now this works.
require_once(DIR_FS_CATALOG.'includes/engine/tools/imagesizer.class.php');

class dow {
	// the database variable supports dependancy injection but doesn't require it, you can set it for the class or it'll fall back to the global
	private static $db = NULL;
	public static function set_db($db) { self::$db = $db; }
	private static function get_db($db=NULL) {
		return $db ?? self::$db ?? service_locator::get_db_service() ?? NULL;
	}

	public static function get_active_dow($db=NULL) {
		return prepared_query::fetch('SELECT ds.dow_schedule_id, ds.products_id, p.stock_id, ds.image, ds.image_med, ds.image_lrg, p.products_image, p.products_image_med, p.products_image_lrg, pd.products_name as title, p.products_price as reg_price, COALESCE(s.specials_new_products_price, p.products_price) as price, s.expires_date, ds.custom_description, ds.legalese FROM ck_dow_schedule ds JOIN products p ON ds.products_id = p.products_id JOIN products_description pd ON p.products_id = pd.products_id LEFT JOIN specials s ON (p.products_id = s.products_id and s.status = 1) WHERE ds.active = 1 ORDER BY ds.start_date DESC LIMIT 1', cardinality::ROW);
	}

	public static function get_dow($dow_id, $db=NULL) {
		return prepared_query::fetch('SELECT ds.dow_schedule_id, ds.products_id, ds.specials_price, p.stock_id, p.products_model, ds.image, ds.image_med, ds.image_lrg, p.products_image, p.products_image_med, p.products_image_lrg, pd.products_name as title, p.products_price as reg_price, COALESCE(s.specials_new_products_price, p.products_price) as price, s.expires_date, ds.custom_description, ds.legalese FROM ck_dow_schedule ds JOIN products p ON ds.products_id = p.products_id JOIN products_description pd ON p.products_id = pd.products_id LEFT JOIN specials s ON (p.products_id = s.products_id and s.status = 1) WHERE ds.dow_schedule_id = ? ORDER BY ds.start_date DESC LIMIT 1', cardinality::ROW, array($dow_id));
	}

	public static function get_dow_recommended($dow_id, $db=NULL) {
		return prepared_query::fetch('SELECT pr.recommend_products_id as products_id, CASE WHEN pr.custom_name IS NOT NULL THEN pr.custom_name ELSE pd.products_name END as products_name, pr.ordinal, p.products_image_med as img FROM ck_product_recommends pr JOIN products p ON pr.recommend_products_id = p.products_id JOIN products_description pd ON pr.recommend_products_id = pd.products_id WHERE pr.dow_schedule_id = ? ORDER BY pr.ordinal ASC', cardinality::SET, array($dow_id));
	}

	public static function get_new_dow($db=NULL) {
		return prepared_query::fetch('SELECT ds.dow_schedule_id, ds.products_id, p.products_model, ds.specials_price, p.stock_id, p.products_image, p.products_image_med, p.products_image_lrg FROM ck_dow_schedule ds JOIN products p ON ds.products_id = p.products_id WHERE start_date = ? AND active = false', cardinality::ROW, array(date('Y-m-d')));
	}

	public static function reset_old_images($db=NULL) {
		// reset the images for the dow that we're turning off
		$current = self::get_active_dow($db);

		// if it was formerly an empty/placeholder image, then just leave it be, we prefer to have the DOW image to nothing
		if (!empty($current['image']) && !preg_match('/newproduct/', $current['image'])) {
			$base_image_name = $current['image']; // start by initializing it to the small image
			if (@rename(DIR_FS_CATALOG.'images/dow_holding/'.$current['image'], DIR_FS_CATALOG.'images/'.$current['image']))
				prepared_query::execute('UPDATE products SET products_image = ? WHERE stock_id = ?', array($current['image'], $current['stock_id']));
		}
		//else prepared_query::execute("UPDATE products SET products_image = 'newproduct_sm.gif' WHERE stock_id = ?", array($current['stock_id']));
		if (!empty($current['image_med']) && !preg_match('/newproduct/', $current['image_med'])) {
			$base_image_name = $current['image_med']; // override it with the medium image if we get here
			if (@rename(DIR_FS_CATALOG.'images/dow_holding/'.$current['image_med'], DIR_FS_CATALOG.'images/'.$current['image_med']))
				prepared_query::execute('UPDATE products SET products_image_med = ? WHERE stock_id = ?', array($current['image_med'], $current['stock_id']));
		}
		//else prepared_query::execute("UPDATE products SET products_image_med = 'newproduct_med.gif' WHERE stock_id = ?", array($current['stock_id']));
		if (!empty($current['image_lrg']) && !preg_match('/newproduct/', $current['image_lrg'])) {
			$base_image_name = $current['image_lrg']; // override it with the large image, which is unlikely to have a suffix
			if (@rename(DIR_FS_CATALOG.'images/dow_holding/'.$current['image_lrg'], DIR_FS_CATALOG.'images/'.$current['image_lrg']))
				prepared_query::execute('UPDATE products SET products_image_lrg = ? WHERE stock_id = ?', array($current['image_lrg'], $current['stock_id']));
		}
		//else prepared_query::execute("UPDATE products SET products_image_lrg = 'newproduct.gif' WHERE stock_id = ?", array($current['stock_id']));

		// handle the 300 size image, which isn't referenced in the database
		if (!empty($base_image_name)) {
			$imgpath = pathinfo($base_image_name);
			preg_match('/^(.+?)(_('.implode('|', array_keys(imagesizer::$map)).'))?$/', $imgpath['filename'], $filename);
			$ext = $imgpath['extension'];
			$filename = $filename[1];

			if (!empty($filename) && !preg_match('/newproduct/', $base_image_name)) {
				@rename(DIR_FS_CATALOG.'images/dow_holding/'.$imgpath['dirname'].'/'.$filename.'_300.'.$ext, DIR_FS_CATALOG.'images/'.$imgpath['dirname'].'/'.$filename.'_300.'.$ext);
			}
		}
	}

	public static function set_dow_images($new, $db=NULL) {

		// this is now included in the base query for $new
		//$images = prepared_query::fetch('SELECT products_image as image, products_image_med as image_med, products_image_lrg as image_lrg FROM products WHERE products_id = ?', cardinality::ROW, array($new['products_id']));

		// set the dow image records so we can back up later
		prepared_query::execute('UPDATE ck_dow_schedule SET image = ?, image_med = ?, image_lrg = ? WHERE dow_schedule_id = ?', array($new['products_image'], $new['products_image_med'], $new['products_image_lrg'], $new['dow_schedule_id']));

		// first move all of the product images to the holding directory, and update all of the products to the holding reference
		// if it's the newproduct.gif image, we should not move the image or update the reference
		$base_image_name = NULL;
		if (!empty($new['products_image']) && !preg_match('/newproduct/', $new['products_image'])) {
			$base_image_name = $new['products_image']; // start by initializing it to the small image
			if (@rename(DIR_FS_CATALOG.'images/'.$new['products_image'], DIR_FS_CATALOG.'images/dow_holding/'.$new['products_image']))
				prepared_query::execute('UPDATE products SET products_image = ? WHERE stock_id = ?', array('dow_holding/'.$new['products_image'], $new['stock_id']));
		}
		if (!empty($new['products_image_med']) && !preg_match('/newproduct/', $new['products_image_med'])) {
			$base_image_name = $new['products_image_med']; // override it with the medium image if we get here
			if (@rename(DIR_FS_CATALOG.'images/'.$new['products_image_med'], DIR_FS_CATALOG.'images/dow_holding/'.$new['products_image_med']))
				prepared_query::execute('UPDATE products SET products_image_med = ? WHERE stock_id = ?', array('dow_holding/'.$new['products_image_med'], $new['stock_id']));
		}
		if (!empty($new['products_image_lrg']) && !preg_match('/newproduct/', $new['products_image_lrg'])) {
			$base_image_name = $new['products_image_lrg']; // override it with the large image, which is unlikely to have a suffix
			self::create_homepage_image(DIR_FS_CATALOG.'images/'.$new['products_image_lrg']);
			if (@rename(DIR_FS_CATALOG.'images/'.$new['products_image_lrg'], DIR_FS_CATALOG.'images/dow_holding/'.$new['products_image_lrg']))
				prepared_query::execute('UPDATE products SET products_image_lrg = ? WHERE stock_id = ?', array('dow_holding/'.$new['products_image_lrg'], $new['stock_id']));
		}
		// handle the 300 size image, which isn't referenced in the database
		if (!empty($base_image_name)) {
			$imgpath = pathinfo($base_image_name);
			preg_match('/^(.+?)(_('.implode('|', array_keys(imagesizer::$map)).'))?$/', $imgpath['filename'], $filename);
			$ext = $imgpath['extension'];
			$filename = $filename[1];

			if (!empty($filename) && !preg_match('/newproduct/', $base_image_name)) {
				@rename(DIR_FS_CATALOG.'images/'.$imgpath['dirname'].'/'.$filename.'_300.'.$ext, DIR_FS_CATALOG.'images/dow_holding/'.$imgpath['dirname'].'/'.$filename.'_300.'.$ext);
			}
		}

		// then move the dow images from their staging area to the images folder
		if ($dow_images = scandir(DIR_FS_CATALOG.'images/dow_staging/')) {
			$sizes = array('sm' => FALSE, 'med' => FALSE, '300' => FALSE, 'lrg' => FALSE);
			foreach ($dow_images as $dow_image) {
				if (!preg_match('/\.jpg$/i', $dow_image)) continue; // we only want the images, skip the current and parent directories and anything else that may have made its way in here
				if (@rename(DIR_FS_CATALOG.'images/dow_staging/'.$dow_image, DIR_FS_CATALOG.'images/p/'.$dow_image)) {
					if (preg_match('/_sm\.jpg$/i', $dow_image)) {
						prepared_query::execute('UPDATE products SET products_image = ? WHERE products_id = ?', array('p/'.$dow_image, $new['products_id']));
						$sizes['sm'] = DIR_FS_CATALOG.'images/p/'.$dow_image;
					}
					elseif (preg_match('/_med\.jpg$/i', $dow_image)) {
						prepared_query::execute('UPDATE products SET products_image_med = ? WHERE products_id = ?', array('p/'.$dow_image, $new['products_id']));
						$sizes['med'] = DIR_FS_CATALOG.'images/p/'.$dow_image;
					}
					elseif (preg_match('/_300\.jpg$/i', $dow_image)) {
						// do nothing, this one is not set in the database, the naming is handled in the code
						$sizes['300'] = DIR_FS_CATALOG.'images/p/'.$dow_image;
					}
					else {
						// generally, this will mean no size suffix, but there's not a particularly good positive case to test for so just anything else that's an image would be assigned here.
						prepared_query::execute('UPDATE products SET products_image_lrg = ? WHERE products_id = ?', array('p/'.$dow_image, $new['products_id']));
						$sizes['lrg'] = DIR_FS_CATALOG.'images/p/'.$dow_image;
					}
				}
			}
			foreach ($sizes as $size => $found) {
				if ($found) continue;
				if ($size == 'lrg') continue; // nothings bigger, so we can't resize to it

				if (empty($largest_image)) {
					if (!empty($sizes['lrg'])) $largest_image = $sizes['lrg'];
					elseif (!empty($sizes['300'])) $largest_image = $sizes['300'];
					elseif (!empty($sizes['med'])) $largest_image = $sizes['med'];

					if (!empty($largest_image)) {
						$imgpath = pathinfo($largest_image);
						preg_match('/^(.+?)(_('.implode('|', array_keys(imagesizer::$map)).'))?$/', $imgpath['filename'], $filename);
						$ext = $imgpath['extension'];
						$filename = $filename[1];
					}
				}

				if ($size == 'sm') {
					// any largest image is larger than sm
					if (!empty($largest_image)) {
						imagesizer::resize($largest_image, imagesizer::$map['sm'], DIR_FS_CATALOG.'images', 'p/'.$filename.'_sm.'.$ext);
						prepared_query::execute('UPDATE products SET products_image = ? WHERE products_id = ?', array('p/'.$filename.'_sm.'.$ext, $new['products_id']));
					}
				}
				elseif ($size == 'med') {
					// if we have lrg or 300, it can be resized to med
					if (!empty($sizes['lrg']) || !empty($sizes['300'])) {
						imagesizer::resize($largest_image, imagesizer::$map['med'], DIR_FS_CATALOG.'images', 'p/'.$filename.'_med.'.$ext);
						prepared_query::execute('UPDATE products SET products_image_med = ? WHERE products_id = ?', array('p/'.$filename.'_med.'.$ext, $new['products_id']));
					}
				}
				elseif ($size == '300') {
					// if we don't have a large, we can't resize to 300
					if (!empty($sizes['lrg'])) {
						imagesizer::resize($largest_image, imagesizer::$map['300'], DIR_FS_CATALOG.'images', 'p/'.$filename.'_300.'.$ext);
						// this one doesn't happen in the database
					}
				}
			}
		}
	}

	public static function create_homepage_image($image) {
		$imgpath = pathinfo($image);
		preg_match('/^(.+?)(_('.implode('|', array_keys(imagesizer::$map)).'))?$/', $imgpath['filename'], $filename);
		imagesizer::resize($image, imagesizer::$map['dow'], DIR_FS_CATALOG.'images', 'product/'.$filename[1].'_dow.'.$imgpath['extension']);
	}

	public static function switch_active($new, $db=NULL) {
		require_once(__DIR__.'/../functions/inventory_functions.php');
		if (!empty($new['specials_price'])) {
			if (prepared_query::fetch('SELECT specials_id FROM specials WHERE products_id = ?', cardinality::SINGLE, $new['products_id'])) {
				insert_psc_change_history($new['stock_id'], 'Special Delete ['.$new['products_model'].']', 'Status Off', '');
				prepared_query::execute('DELETE FROM specials WHERE products_id = ?', $new['products_id']);
			}

			$expiration_date = new DateTime(prepared_query::fetch('SELECT DATE_SUB(start_date, INTERVAL 1 DAY) FROM ck_dow_schedule WHERE start_date > ? ORDER BY start_date ASC', cardinality::SINGLE, date('Y-m-d')));
			
			$special = [
				'status' => 1,
				'specials_qty' => NULL,
				'specials_new_products_price' => $new['specials_price'],
				'expires_date' => !empty($expiration_date)?$expiration_date->format('Y-m-d 23:59:59'):'',
				'active_criteria' => 1,
			];

			$listing = new ck_product_listing($new['products_id']);
			$listing->set_special($special);
		}
		else {
			insert_psc_change_history($new['stock_id'], 'Special Update ['.$new['products_model'].']', 'Previous Status', 'Auto DOW On');
			prepared_query::execute('UPDATE specials SET status = 1 WHERE products_id = ?', $new['products_id']);
		}
		prepared_query::execute('UPDATE ck_dow_schedule SET active = CASE WHEN dow_schedule_id = ? THEN true ELSE false END', array($new['dow_schedule_id']));
	}

	private static $table = "CREATE TABLE IF NOT EXISTS ck_dow_schedule (
	dow_schedule_id int(11) NOT NULL auto_increment,
	products_id int(11) NOT NULL,
	start_date date NOT NULL,
	active tinyint(4) NOT NULL default '0',
	image varchar(64) collate utf8_bin default NULL,
	image_med varchar(64) collate utf8_bin default NULL,
	image_lrg varchar(64) collate utf8_bin default NULL,
	entered timestamp NOT NULL default CURRENT_TIMESTAMP,
	PRIMARY KEY (dow_schedule_id)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='DOWs activated by cron 12AM Mon. morning';";
}
?>
