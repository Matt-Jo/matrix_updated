<?php
// this is purpose built as a drop in API replacement for mountains of convoluted legacy code
class messageStack {
	const CONTEXT_ADMIN = 'admin';
	const CONTEXT_PUBLIC = 'public';

	private static $session_key = 'messageToStack';

	private $context;
	private $messages = [];

	private static $default_status = ck_message::STATUS_ERROR;

	private $log_status = TRUE;

	public function __construct($context=self::CONTEXT_PUBLIC) {
		$this->context = $context;

		if (!empty($_SESSION[self::$session_key])) {
			$this->logging(FALSE);
			foreach ($_SESSION[self::$session_key] as $message) {
				// session is stored the same way it was before, so we can still meet the old API
				if ($this->is_admin() && $message['class'] == 'admin') $this->add($message['text'], $message['type']);
				else $this->add($message['class'], $message['text'], $message['type']);
			}
			$this->logging(TRUE);

			unset($_SESSION[self::$session_key]);
		}
	}

	public function logging($status=NULL) {
		if (is_null($status)) return $this->log_status;
		else $this->log_status = (bool) $status;
	}

	private function is_admin() {
		return $this->context==self::CONTEXT_ADMIN;
	}

	public function add($message_group, $message=NULL, $status=NULL) {
		if ($this->is_admin()) {
			$status = $message;
			$message = $message_group;
			$message_group = 'admin';
		}

		if (empty($status)) $status = self::$default_status;

		if (empty($this->messages[$message_group])) $this->messages[$message_group] = [];

		$message = new ck_message($message, $status);
		if ($this->logging()) $message->log_error($message_group);

		$this->messages[$message_group][] = $message;
	}

	public function add_session($message_group, $message=NULL, $status=NULL) {
		if ($this->is_admin()) {
			$status = $message;
			$message = $message_group;
			$message_group = 'admin';
		}

		if (empty($status)) $status = self::$default_status;

		// this is virtually identical, because we need to meet the old API
		if (empty($_SESSION[self::$session_key])) $_SESSION[self::$session_key] = [];
		$_SESSION[self::$session_key][] = ['class' => $message_group, 'text' => $message, 'type' => $status];

		$message = new ck_message($message, $status);
		if ($this->logging()) $message->log_error($message_group);
	}

	public function reset() {
		$this->messages = [];
	}

	public function output($message_group=NULL) {
		// this is an ugly mess of HTML in PHP, which is mostly carried over from the legacy class.

		$output = '';

		if (empty($message_group)) {
			foreach ($this->messages as $message_group => $msgs) {
				$output .= $this->output($message_group);
			}
		}
		else {
			if (empty($this->messages[$message_group])) return $output;

			$output .= '<table class="message-stack" cellspacing="0" cellpadding="0" border="0" width="100%">';

			foreach ($this->messages[$message_group] as $message) {
				$output .= '<tr class="'.$message->get_css_class().'">';
				$output .= '<td class="'.$message->get_css_class().'" style="font-size:13px;padding:5px 8px;">'.$message->get_msg_icon().$message->get_message().'</td>';
				$output .= '</tr>';
			}

			$output .= '</table>';
		}

		return $output;
	}

	public function __get($key) {
		if ($key == 'size') return $this->size();
	}

	public function size($message_group=NULL) {
		$size = 0;

		if (empty($message_group)) {
			$size = array_reduce($this->messages, function($cnt, $msgs) {
				$cnt += count($msgs);
				return $cnt;
			}, 0);
		}
		elseif (isset($this->messages[$message_group]) && is_array($this->messages[$message_group])) $size = count($this->messages[$message_group]);

		return $size;
	}
}

class ck_message {
	const STATUS_ERROR = 'error';
	const STATUS_WARNING = 'warning';
	const STATUS_SUCCESS = 'success';
	const STATUS_OTHER = 'other';

	private $status;
	private $message;

	public function __construct($message, $status=self::STATUS_SUCCESS) {
		$this->message = $message;
		$this->status = $status;
	}

	public function get_message() {
		return $this->message;
	}

	public function get_css_class() {
		switch ($this->status) {
			case self::STATUS_WARNING:
				return 'messageStackWarning';
				break;
			case self::STATUS_SUCCESS:
				return 'messageStackSuccess';
				break;
			case self::STATUS_ERROR:
			default:
				return 'messageStackError';
				break;
		}
	}

	public function get_msg_icon() {
		switch ($this->status) {
			case self::STATUS_WARNING:
				return '<img src="/images/icons/warning.gif" alt="Warning"> ';
				break;
			case self::STATUS_SUCCESS:
				return '<img src="/images/icons/success.gif" alt="Success"> ';
				break;
			case self::STATUS_ERROR:
				return '<img src="/images/icons/error.gif" alt="Error"> ';
				break;
			default:
				return '';
				break;
		}
	}

	public function log_error($message_group) {
		if ($this->status == self::STATUS_SUCCESS) return;

		$url = $_SERVER['REQUEST_URI'];
		if (empty($message_group)) {
			$message_group = 'catalog';
			if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) $message_group = 'matrix';
		}

		$admin_id = '0';
		if ($message_group == 'matrix' && !empty($_SESSION['login_id'])) $admin_id = $_SESSION['login_id'];

		$customer_id = '0';
		if ($message_group != 'matrix' && !empty($_SESSION['customer_id'])) {
			$customer_id = $_SESSION['customer_id'];
			//we don't want to log events from the mcafee scanner
			if ($customer_id = '47122') return;
		}

		prepared_query::execute('INSERT INTO ck_error_log (class, level, message, url, admin_id, customer_id) VALUES (:class, :level, :message, :url, :admin_id, :customer_id)', [':class' => $message_group, ':level' => $this->status, ':message' => $this->message, ':url' => $url, ':admin_id' => $admin_id, ':customer_id' => $customer_id]);
	}
}
?>
