<?php
class ck_view_admin_customer_quote_dashboard extends ck_view {

	protected $url = '/customer-quote-dashboard.php';

	protected $page_templates = [
		'customer-quote-dashboard' => 'page-customer-quote-dashboard.mustache.html',
	];

	public function process_response() {
		$this->init([__DIR__.'/../../admin/includes/templates']);

		$__FLAG = request_flags::instance();

		// if we're responding to an ajax request, we can ignore any other application processing outside of this class
		if ($this->response_context == self::CONTEXT_AJAX) $this->respond();
		elseif (!empty($_REQUEST['action'])) $this->psuedo_controller();
	}

	private function psuedo_controller() {
		$page = NULL;

		switch ($_REQUEST['action']) {
			case 'delete':
				if (!empty($_REQUEST['customer_quote_id'])) {
					self::query_execute('DELETE FROM customer_quote_products WHERE customer_quote_id = :customer_quote_id', cardinality::NONE, [':customer_quote_id' => $_REQUEST['customer_quote_id']]);
					self::query_execute('DELETE FROM customer_quote WHERE customer_quote_id = :customer_quote_id', cardinality::NONE, [':customer_quote_id' => $_REQUEST['customer_quote_id']]);
				}
				$page = '/admin/customer-quote-dashboard.php';
				break;
			case 'copy':
				$quote = ck_quote::create_quote_copy($_REQUEST['customer_quote_id']);
				$page = '/admin/customer-quote.php?customer_quote_id='.$quote->id();
				break;
			default:
				break;
		}

		if (!empty($page)) CK\fn::redirect_and_exit($page);
	}

	public function respond() {
		if ($this->response_context == self::CONTEXT_AJAX) $this->ajax_response();
		else $this->http_response();
	}

	private function ajax_response() {
		// there's nothing to do here
		switch ($_REQUEST['action']) {
			case 'quote-search':
				$response['results'] = [];
				$response['displayed_result_count'] = 0;

				$parameters = [];
				$parameters_for_count = [];

				$where_clause = 'AND cq.expiration_date > DATE(NOW())';
				if ($_REQUEST['include_expired'] == 1) $where_clause = '';

				// using a different where clause for our result count - we don't want to limit it like the displayed results, we want to know how many total matches
				$count_clause = $where_clause;

				if (!empty($_REQUEST['last_id'])) { // this is for the lazy load
					$parameters += [':last_id' => $_REQUEST['last_id']];
					$where_clause .= ' AND cq.customer_quote_id < :last_id';
				}

				if (is_numeric($_REQUEST['quote_search'])) { // this is if a user searches a quote id
					$parameters += [':quote_id' => trim($_REQUEST['quote_search']).'%'];
					$where_clause .= ' AND cq.customer_quote_id LIKE :quote_id';

					$parameters_for_count += [':quote_id' => trim($_REQUEST['quote_search']).'%'];
					$count_clause .= ' AND cq.customer_quote_id LIKE :quote_id';
				}
				elseif (!is_numeric($_REQUEST['quote_search']) && !empty($_REQUEST['quote_search'])) { // this is when searching an email or a name

					$params = [':email' => '%'.trim($_REQUEST['quote_search']).'%'];
					$clause = ' AND (cq.customer_email LIKE :email OR a.admin_email_address LIKE :email)';

					$parameters += $params;
					$where_clause .= $clause;

					$count_clause .= $clause;
					$parameters_for_count += $params;
				}

				$response['result_count'] = self::query_fetch("SELECT COUNT(cq.customer_quote_id) FROM customer_quote cq LEFT JOIN admin a ON cq.admin_id = a.admin_id WHERE cq.active = 1 $count_clause", cardinality::SINGLE, $parameters_for_count);

				$response['results'] = self::query_fetch("SELECT DISTINCT cq.customer_quote_id, cq.expiration_date, cq.customer_email, cq.order_id, cq.status, cq.url_hash, cq.customer_contacted, a.admin_email_address, DATE(cq.created) AS created, cqp.price, cqp.quantity, SUM(cqp.price * cqp.quantity) AS total, (SELECT COUNT(*) FROM orders WHERE customers_id = cq.customers_id) AS number_of_orders, c.account_manager_id, c.customers_telephone FROM customer_quote cq LEFT JOIN customers c ON cq.customers_id = c.customers_id LEFT JOIN admin a ON cq.admin_id = a.admin_id LEFT JOIN customer_quote_products cqp ON cq.customer_quote_id = cqp.customer_quote_id WHERE cq.active = 1 $where_clause GROUP BY cq.customer_quote_id ORDER BY cq.customer_quote_id DESC LIMIT 100", cardinality::SET, $parameters);

				// this is currently a static 100, but it could change later, so I'll go ahead and include it
				$response['displayed_result_count'] = count($response['results']);

				//$response['paginate'] = FALSE;
				//if ($response['result_count'] > $response['displayed_result_count']) $response['paginate'] = TRUE;

				echo json_encode($response);

				exit();
				break;
			case 'update-customer-contacted-data':
				$result = self::query_execute('UPDATE customer_quote SET customer_contacted = :customer_contacted WHERE customer_quote_id = :customer_quote_id', cardinality::NONE, [':customer_quote_id' => $_REQUEST['customer_quote_id'], ':customer_contacted' => $_REQUEST['customer_contacted']]);
				if ($result) echo json_encode(['success' => true]);
				exit();
				break;
			default:
				break;
		}

		echo json_encode(['errors' => []]);
		exit();
	}

	private function http_response() {
		// current this page is relying purely on ajax to populate data, after the page is render an ajax called is made
		$this->render($this->page_templates['customer-quote-dashboard']);
		$this->flush();
	}
}

?>
