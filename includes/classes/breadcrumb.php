<?php
class breadcrumb {
	private $_trail;

	public function __construct() {
		$this->reset();
	}

	public function reset() {
		$this->_trail = [];
	}

	public function add($title, $link='', $header = false) {
		$this->_trail[] = ['title' => $title, 'link' => $link, 'header_tag' => $header];
	}

	public function trail($separator=' &gt; ') {
		$buffer = '<ul itemscope itemtype="http://schema.org/BreadcrumbList">';

		$last = count($this->_trail)-1;
		$color = '';

		$crumbs = [];
		$crumbs[] = '<li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><a itemprop="item url" href="/" aria-label="Home"><meta itemprop="name" content="CablesAndKits.com"><i class="far fa-home fa-2x"></i></a><meta itemprop="position" content="1"></li>';

		foreach ($this->_trail as $i => $crumb) {
			if ($i == 0) continue;
			if ($i == $last) $color = 'color:#4d7cbc;'; //$style = 'style="color: #e62345;"';

			if ($crumb['header_tag']) {
				$crumbs[] = '<li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><h1 style="font-size:12px;padding:inherit; margin:inherit; font-weight:inherit;"><a itemprop="item url" href="'.$crumb['link'].'" class="headerNavigation" style="'.$color.'"><span itemprop="name">'.$crumb['title'].'</span></a></h1><meta itemprop="position" content="'.($i+1).'"></li>';
			}
			elseif (!empty($crumb['link'])) {
				$crumbs[] = '<li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><a itemprop="item url" href="'.$crumb['link'].'" class="headerNavigation" style="'.$color.'"><span itemprop="name">'.$crumb['title'].'</span></a><meta itemprop="position" content="'.($i+1).'"></li>';
			}
			else {
				$crumbs[] = '<li itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem" style="margin-left:5px; '.$color.'"><span itemprop="name">'.$crumb['title'].'</span><meta itemprop="position" content="'.($i+1).'"></li>';
			}
		}

		$buffer .= implode('<li>'.$separator.'</li>', $crumbs).'</ul>';

		return $buffer;
	}

	public function size() {
		return count($this->_trail);
	}
 }
?>
