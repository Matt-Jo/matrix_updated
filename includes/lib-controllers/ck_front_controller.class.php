<?php
class ck_front_controller extends ck_controller {
	const CONTEXT_FRONTEND = 'front-end';
	const CONTEXT_ADMIN = 'admin';

	private $request_handler = NULL;
	private $context;

	public function __construct($context=self::CONTEXT_FRONTEND) {
		$this->context = $context;

		$this->get_routes();
		$this->request_handler = ck_router::instance()->route();
	}

	private function get_routes() {
		if ($this->context == self::CONTEXT_FRONTEND) require(__DIR__.'/../routes.php');
		elseif ($this->context == self::CONTEXT_ADMIN) require(__DIR__.'/../../admin/includes/routes.php');
	}

	public function run() {
		$view = NULL;
		if (!empty($this->request_handler) && class_exists($this->request_handler)) {
			$view = new $this->request_handler;
			$view->process_response();
		}
		elseif (!empty($this->request_handler) && is_callable($this->request_handler)) {
			$rh = $this->request_handler;
			$rh();
		}

		return $view;
	}
}
?>
