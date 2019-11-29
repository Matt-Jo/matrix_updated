<?php
/*
 $Id: upload.php,v 1.1.1.1 2004/03/04 23:39:49 ccwjr Exp $

 osCommerce, Open Source E-Commerce Solutions
 http://www.oscommerce.com

 Copyright (c) 2003 osCommerce

 Released under the GNU General Public License
*/

 class upload {
	public $file;
	public $filename;
	public $destination;
	public $permissions;
	public $extensions;
	public $tmp_filename;
	public $message_location;

	public function __construct($file = '', $destination = '', $permissions = '777', $extensions = '') {
		$this->set_file($file);
		$this->set_destination($destination);
		$this->set_permissions($permissions);
		$this->set_extensions($extensions);

		$this->set_output_messages('direct');

		if (!empty($this->file) && !empty($this->destination)) {
			$this->set_output_messages('session');

			if (!$this->parse()) throw new \Exception('Couldn\'t parse file');
			elseif (!$this->save()) throw new \Exception('Couldn\'t save file');
		}
	}

	public function parse() {
		global $messageStack;

		if (isset($_FILES[$this->file])) {
			$file = [
				'name' => $_FILES[$this->file]['name'],
				'type' => $_FILES[$this->file]['type'],
				'size' => $_FILES[$this->file]['size'],
				'tmp_name' => $_FILES[$this->file]['tmp_name']
			];
		}

		if (!empty($file['tmp_name']) && ($file['tmp_name'] != 'none') && is_uploaded_file($file['tmp_name'])) {
			if (sizeof($this->extensions) > 0) {
				if (!in_array(strtolower(substr($file['name'], strrpos($file['name'], '.')+1)), $this->extensions)) {
					if ($this->message_location == 'direct') {
						$messageStack->add(ERROR_FILETYPE_NOT_ALLOWED, 'error');
					}
					else {
						$messageStack->add_session(ERROR_FILETYPE_NOT_ALLOWED, 'error');
					}
					return false;
				}
			}

			$this->set_file($file);
			$this->set_filename($file['name']);
			$this->set_tmp_filename($file['tmp_name']);

			return $this->check_destination();
		}
		else {
			// BOF: MaxiDVD added remove annoying no-image uploaded message
			if (defined('WYSIWYG_USE_PHP_IMAGE_MANAGER') && WYSIWYG_USE_PHP_IMAGE_MANAGER == 'Disable') {
				// EOF: MaxiDVD added remove annoying no-image uploaded message
				if ($this->message_location == 'direct') {
					$messageStack->add(WARNING_NO_FILE_UPLOADED, 'warning');
				}
				else {
					$messageStack->add_session(WARNING_NO_FILE_UPLOADED, 'warning');
				}
				return false;
			}
		}
	}

	public function save() {
		global $messageStack;

		if (substr($this->destination, -1) != '/') $this->destination .= '/';

		if (move_uploaded_file($this->file['tmp_name'], $this->destination.$this->filename)) {
			chmod($this->destination.$this->filename, $this->permissions);

			if ($this->message_location == 'direct') {
				$messageStack->add(SUCCESS_FILE_SAVED_SUCCESSFULLY, 'success');
			}
			else {
				$messageStack->add_session(SUCCESS_FILE_SAVED_SUCCESSFULLY, 'success');
			}
			return true;
		}
		else {
			if ($this->message_location == 'direct') {
				$messageStack->add(ERROR_FILE_NOT_SAVED, 'error');
			}
			else {
				$messageStack->add_session(ERROR_FILE_NOT_SAVED, 'error');
			}
			return false;
		}
	}

	public function set_file($file) {
		$this->file = $file;
	}

	public function set_destination($destination) {
		$this->destination = $destination;
	}

	public function set_permissions($permissions) {
		$this->permissions = octdec($permissions);
	}

	public function set_filename($filename) {
		$this->filename = $filename;
	}

	public function set_tmp_filename($filename) {
		$this->tmp_filename = $filename;
	}

	public function set_extensions($extensions) {
		if (tep_not_null($extensions)) {
			if (is_array($extensions)) {
				$this->extensions = $extensions;
			}
			else {
				$this->extensions = array($extensions);
			}
		}
		else {
			$this->extensions = array();
		}
	}

	public function check_destination() {
		global $messageStack;

		if (!is_writeable($this->destination)) {
			if (is_dir($this->destination)) {
				if ($this->message_location == 'direct') {
					$messageStack->add(sprintf(ERROR_DESTINATION_NOT_WRITEABLE, $this->destination), 'error');
				}
				else {
					$messageStack->add_session(sprintf(ERROR_DESTINATION_NOT_WRITEABLE, $this->destination), 'error');
				}
			}
			else {
				if ($this->message_location == 'direct') {
					$messageStack->add(sprintf(ERROR_DESTINATION_DOES_NOT_EXIST, $this->destination), 'error');
				}
				else {
					$messageStack->add_session(sprintf(ERROR_DESTINATION_DOES_NOT_EXIST, $this->destination), 'error');
				}
			}
			return false;
		}
		else {
			return true;
		}
	}

	public function set_output_messages($location) {
		switch ($location) {
			case 'session':
				$this->message_location = 'session';
				break;
			case 'direct':
			default:
				$this->message_location = 'direct';
				break;
		}
	}
}
?>
