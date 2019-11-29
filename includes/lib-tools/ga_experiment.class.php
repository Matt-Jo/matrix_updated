<?php
class ga_experiment {

public static $ga_key = 'gae-';

public $experiment_key;
public $variation_key;
public $context;
public $context_key;

// for now this is hard-coded in class, but it would be fairly easy to replace this with an accessible database structure, or a separate config file
public $experiments = array(
	'csb1' => array(
		'description' => 'catalog subcategory block', // this basically describes what we're testing, not used for any functional purpose
		'variations' => array(
			'a' => array('location' => 'inline', 'key' => 'csb1-a'), // where can we find the variation code (either in an inline conditional, or in a totally separate file)
			'b' => array('location' => 'inline', 'key' => 'csb1-b'),
			'c' => array('location' => 'inline', 'key' => 'csb1-c'),
		),
	),
	'csb2' => array(
		'description' => 'catalog add to cart button',
		'variations' => array(
			'a' => array('location' => 'inline', 'key' => 'csb2-a'),
		),
	),
	'csb3' => array(
		'description' => 'remove continue shopping in cart',
		'variations' => array(
			'a' => array('location' => 'inline', 'key' => 'csb3-a'),
		),
	),
	'csb4' => array(
		'description' => 'catalog direct-add colors',
		'variations' => array(
			'a' => array('location' => 'inline', 'key' => 'csb4-a'),
			'b' => array('location' => 'inline', 'key' => 'csb4-b'),
		),
	),
);

public static $pages = array(
	/*'/index.php' => array(
		'js' => "<script>_udn = 'cablesandkits.com'; _uhash = 'off';</script>
<script>function utmx_section() {}function utmx() {}(function() {var
k='8750333-0',d=document,l=d.location,c=d.cookie;
if (l.search.indexOf('utm_expid='+k)>0)return;
function f(n) {if(c) {var i=c.indexOf(n+'=');if(i>-1) {var j=c.
indexOf(';',i);return escape(c.substring(i+n.length+1,j<0?c.
length:j))}}}var x=f('__utmx'),xx=f('__utmxx'),h=l.hash;d.write(
'<sc'+'ript src=\"'+'http'+(l.protocol=='https:'?'s://ssl':
'://www')+'.google-analytics.com/ga_exp.js?'+'utmxkey='+k+
'&utmx='+(x?x:'')+'&utmxx='+(xx?xx:'')+'&utmxtime='+new Date().
valueOf()+(h?'&utmxhash='+escape(h.substr(1)):'')+
'\" type=\"text/javascript\" charset=\"utf-8\"><\/sc'+'ript>')})();
</script><script>utmx('url','A/B');</script>",
		'variables' => array(
			'cPath' => TRUE
		)
	)*/
	'/index.php' => array(
		'ga_experiment_key' => '8750333-3',
	),
	'/advanced_search_result.php' => array(
		'ga_experiment_key' => '8750333-3',
	),
	'/outlet.php' => array(
		'ga_experiment_key' => '8750333-3',
	),
	'/shopping_cart.php' => array(
		'ga_experiment_key' => '8750333-6',
	),
	//'/product_info.php' => array(
	//	'js' => '<script type="text/javascript">/*rvidheader#1.5.0*/var REED_host = (("https:" == document.location.protocol) ? "https://s"+"3.ama"+"zonaw"+"s.com/statics"+".reedge.com" : "http://statics"+".reedge.com");var REED_s = \'REED_1001109_10011079\';var REED_f;if((typeof(jQuery) != "undefined")) REED_f="REED_main_no_jquery.js";else REED_f="REED_main.js" ;document.write(unescape("%3Cscript src=\'" + REED_host + "/js/"+REED_f+"\' type=\'text/javascript\'%3E%3C/script%3E"));</script>',
	//),
);

public static $all_pages = array(
	//'<script type="text/javascript">/*rvidheader#1.5.0*/var REED_host = (("https:" == document.location.protocol) ? "https://s"+"3.ama"+"zonaw"+"s.com/statics"+".reedge.com" : "http://statics"+".reedge.com");var REED_s = \'REED_1001109_10011079\';var REED_f;if((typeof(jQuery) != "undefined")) REED_f="REED_main_no_jquery.js";else REED_f="REED_main.js" ;document.write(unescape("%3Cscript src=\'" + REED_host + "/js/"+REED_f+"\' type=\'text/javascript\'%3E%3C/script%3E"));</script>'
	//'<script type="text/javascript">/*rvidheader#1.5.0*/var REED_host = (("https:" == document.location.protocol) ? "https://s"+"3.ama"+"zonaw"+"s.com/statics"+".reedge.com" : "http://statics"+".reedge.com");var REED_s = \'REED_10001127_1000971\';var REED_f;if((typeof(jQuery) != "undefined")) REED_f="REED_main_no_jquery.js";else REED_f="REED_main.js" ;document.write(unescape("%3Cscript src=\'" + REED_host + "/js/"+REED_f+"\' type=\'text/javascript\'%3E%3C/script%3E"));</script>'
);

public function __construct($experiment_key=NULL, $variation_key=NULL) {
	$this->set_experiment($experiment_key, $variation_key);
}

public function set_experiment($experiment_key=NULL, $variation_key=NULL) {
	$this->experiment_key = $experiment_key;
	$this->variation_key = $variation_key;
	if (isset($this->experiments[$this->experiment_key]) && isset($this->experiments[$this->experiment_key]['variations'][$this->variation_key])) {
		$this->context = $this->experiments[$this->experiment_key]['variations'][$this->variation_key]['location'];
		$this->context_key = $this->experiments[$this->experiment_key]['variations'][$this->variation_key]['key'];
	}
}

public function run_experiment($page) {
	if (isset(self::$pages[$page]['js']) && self::$pages[$page]['js']) {
		echo self::$pages[$page]['js'];
	}
	elseif (isset(self::$pages[$page]['ga_experiment_key']) && self::$pages[$page]['ga_experiment_key']) {
		echo '<!-- Google Analytics Content Experiment code -->';
		echo "<script>_udn = 'cablesandkits.com'; _uhash = 'off';</script>
<script>function utmx_section() {}function utmx() {}(function() {var
k='";
		echo self::$pages[$page]['ga_experiment_key'];
		echo "',d=document,l=d.location,c=d.cookie;
if (l.search.indexOf('utm_expid='+k)>0)return;
function f(n) {if(c) {var i=c.indexOf(n+'=');if(i>-1) {var j=c.
indexOf(';',i);return escape(c.substring(i+n.length+1,j<0?c.
length:j))}}}var x=f('__utmx'),xx=f('__utmxx'),h=l.hash;d.write(
'<sc'+'ript src=\"'+'http'+(l.protocol=='https:'?'s://ssl':
'://www')+'.google-analytics.com/ga_exp.js?'+'utmxkey='+k+
'&utmx='+(x?x:'')+'&utmxx='+(xx?xx:'')+'&utmxtime='+new Date().
valueOf()+(h?'&utmxhash='+escape(h.substr(1)):'')+
'\" type=\"text/javascript\" charset=\"utf-8\"><\/sc'+'ript>')})();
</script><script>utmx('url','A/B');</script>";
		echo '<!-- End of Google Analytics Content Experiment code -->';
		echo "\n";
	}
}

public static function start($page, $context, $experiment_key=NULL, $variation_key=NULL) { // context will almost always be $_GET, but we may want to use $_SESSION or $_COOKIE. If we require a combination, we can combine them before passing
	// if there are no experiments set up for this page, skip it
	if (!in_array($page, array_keys(self::$pages))) return FALSE;

	// if a particular variable is required to be set,
	if (isset(self::$pages[$page]['variables'])) {
		foreach (self::$pages[$page]['variables'] as $var => $vals) {
			// if the required variable isn't set in the proper context, skip it
			if (!isset($context[$var])) return FALSE;
			// if all that's required is that the variable exists, this test passed, go on to the next variable
			if ($vals === TRUE) continue;
			// if the required value is set, go on to the next variable
			if (!is_array($vals) && $vals == $context[$var]) continue;
			// if it's an array of possible values, see if the value is among them. If it is, go on to the next variable.
			if (in_array($context[$var], $vals)) continue;

			// if we didn't find a passing condition, skip it
			return FALSE;
		}
	}

	return new ga_experiment($experiment_key, $variation_key);
}

public static function parse_keys($input) { // input will almost always be $_GET, but we want to allow other sources if desired
	if (is_array($input)) {
		foreach ($input as $key => $val) {
			if (preg_match('/^'.self::$ga_key.'(.+)$/', $key, $matches)) {
				return array($matches[1], $val);
			}
		}
	}
	return array(NULL, NULL);
}

}
?>