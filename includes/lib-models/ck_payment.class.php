<?php
class ck_payment extends ck_model_archetype {
	protected static $queries = [
		'payment_header_proto' => [
			'proto_qry' => [
				'data_operation' => 'SELECT p.payment_id, p.customer_id as customers_id, p.customers_extra_logins_id, p.payment_amount as amount, p.payment_method_id, pm.code as payment_method_code, pm.label as payment_method_label, p.payment_ref as reference_number, p.payment_date',

				'from' => 'FROM acc_payments p JOIN payment_method pm ON p.payment_method_id = pm.id',

				'where' => 'WHERE', // will fail if we don't provide our own
			],
			'proto_opts' => [
				':payment_id' => 'p.payment_id = :payment_id',
			],
			'proto_defaults' => [
				'where' => 'p.payment_id = :payment_id',
			],
			'proto_count_clause' => 'COUNT(p.payment_id)',
			'cardinality' => cardinality::ROW,
		],

		'order_applications' => [
			'qry' => 'SELECT id as payment_application_id, order_id as orders_id, amount as applied_amount FROM acc_payments_to_orders WHERE payment_id = :payment_id',
			'cardinality' => cardinality::SET,
		],

		'invoice_applications' => [
			'qry' => 'SELECT payment_to_invoice_id as payment_application_id, invoice_id, credit_amount as applied_amount, credit_date as application_date FROM acc_payments_to_invoices WHERE payment_id = :payment_id',
			'cardinality' => cardinality::SET,
		],
	];

	protected function init() {
	}

	protected static function get_instance($payment_id=NULL) {
		return ck_payment_type::instance($payment_id);
	}

	public static function get_with_instance($payment_id=NULL) {
		return self::init_with_model(self::get_instance($payment_id));
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function build_header() {
		$qry = self::modify_query('payment_header_proto');
		$header = self::fetch($qry, [':payment_id' => $this->id()]);
		$this->load('header', $header?:NULL);
	}

	private function build_customer() {
		$this->load('customer', new ck_customer2($this->get_header('customers_id')));
	}

	private function build_order_applications() {
		$applications = array_map(function($application) {
			$application['order'] = new ck_sales_order($application['orders_id']);
			return $application;
		}, self::fetch('order_applications', [':payment_id' => $this->id()]));

		$this->load('order_applications', $applications);
	}

	private function build_invoice_applications() {
		$applications = array_map(function($application) {
			$application['invoice'] = new ck_invoice($application['invoice_id']);
			return $application;
		}, self::fetch('invoice_applications', [':payment_id' => $this->id()]));

		$this->load('invoice_applications', $applications);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function has_header($key=NULL) {
		if (!$this->is_loaded('header')) $this->build_header();
		return $this->has($key, 'header');
	}

	public function get_header($key=NULL) {
		if (!$this->has_header()) return NULL;
		return $this->get($key, 'header');
	}

	public function get_customer() {
		if (!$this->is_loaded('customer')) $this->build_customer();
		return $this->get(NULL, 'customer');
	}

	public function has_order_applications($orders_id=NULL) {
		if (!$this->is_loaded('order_applications')) $this->build_order_applications();
		if (empty($orders_id)) return $this->has(NULL, 'order_applications');
		else return !empty(array_filter($this->get(NULL, 'order_applications'), function ($application) use ($orders_id) { return $application['orders_id']==$orders_id; }));
	}

	public function get_order_applications($orders_id=NULL) {
		if (!$this->has_order_applications($orders_id)) return [];
		if (empty($orders_id)) return $this->get(NULL, 'order_applications');
		else return array_filter($this->get(NULL, 'order_applications'), function ($application) use ($orders_id) { return $application['orders_id']==$orders_id; });
	}

	public function has_invoice_applications($invoice_id=NULL) {
		if (!$this->is_loaded('invoice_applications')) $this->build_invoice_applications();
		if (empty($invoice_id)) return $this->has(NULL, 'invoice_applications');
		else return !empty(array_filter($this->get(NULL, 'invoice_applications'), function ($application) use ($invoice_id) { return $application['invoice_id']==$invoice_id; }));
	}

	public function get_invoice_applications($invoice_id=NULL) {
		if (!$this->has_invoice_applications($invoice_id)) return [];
		if (empty($invoice_id)) return $this->get(NULL, 'invoice_applications');
		else return array_filter($this->get(NULL, 'invoice_applications'), function ($application) use ($invoice_id) { return $application['invoice_id']==$invoice_id; });
	}

	public function get_order_applied_amount() {
		return array_reduce($this->get_order_applications(), function($applied_amount, $application) { return $applied_amount + $application['applied_amount']; }, 0);
	}

	public function get_invoice_applied_amount() {
		return array_reduce($this->get_invoice_applications(), function($applied_amount, $application) { return $applied_amount + $application['applied_amount']; }, 0);
	}

	public function get_applied_amount() {
		return $this->get_order_applied_amount() + $this->get_invoice_applied_amount();
	}

	public function get_hard_unapplied_amount() {
		return $this->get_header('amount') - $this->get_invoice_applied_amount();
	}

	public function get_unapplied_amount() {
		return $this->get_header('amount') - $this->get_applied_amount();
	}

	public static function get_payments_by_applied_orders_id($orders_id) {
		$pmts = [];

		if ($pmt_ids = prepared_query::fetch('SELECT DISTINCT payment_id FROM acc_payments_to_orders WHERE order_id = :orders_id', cardinality::COLUMN, [':orders_id' => $orders_id])) {
			foreach ($pmt_ids as $pmt_id) {
				$pmts[$pmt_id] = self::get_instance($pmt_id);
			}
		}

		return $pmts;
	}

	public static function get_payments_by_applied_invoice_id($invoice_id) {
		$pmts = [];

		if ($pmt_ids = prepared_query::fetch('SELECT DISTINCT payment_id FROM acc_payments_to_invoices WHERE invoice_id = :invoice_id', cardinality::COLUMN, [':invoice_id' => $invoice_id])) {
			foreach ($pmt_ids as $pmt_id) {
				$pmts[$pmt_id] = self::get_instance($pmt_id);
			}
		}

		return $pmts;
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public function enter_payment(Array $data) {
		try {
			$payment = self::get_instance();
			$payment->change('header', $data);
			$payment->create(TRUE);

			$this->set_active_model($payment);
			$this->write();
		}
		catch (CKPaymentException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKPaymentException('Failed to enter payment.', $e->getCode(), $e);
		}
	}

	public function remove() {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('DELETE pto, pti FROM acc_payments p LEFT JOIN acc_payments_to_orders pto ON p.payment_id = pto.payment_id LEFT JOIN acc_payments_to_invoices pti ON p.payment_id = pti.payment_id WHERE p.payment_id = :payment_id', [':payment_id' => $this->id()]);
			prepared_query::execute('DELETE FROM acc_payments WHERE payment_id = :payment_id', [':payment_id' => $this->id()]);

			$this->unload();
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPaymentException('Failed to remove payment.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function apply_to_order($orders_id, $amount) {
		if ($amount <= 0) return;

		try {
			if ($amount > $this->get_unapplied_amount()) throw new CKPaymentException('Cannot apply more than is remaining on payment.');

			if ($this->has_order_applications($orders_id)) {
				$applications = $this->get_order_applications($orders_id);
			}
			else {
				$applications = [['orders_id' => $orders_id, 'applied_amount' => 0]];
			}

			foreach ($applications as $payment_application_id => $application) {
				$application['applied_amount'] += $amount;
				$this->change('order_applications', [$payment_application_id => $application]);
				break; // we only want to deal with the first one
			}

			$this->write();
		}
		catch (CKPaymentException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKPaymentException('Failed to create application.', $e->getCode(), $e);
		}
	}

	public function unapply_from_order($orders_id, $amount) {
		try {
			if (!$this->has_order_applications($orders_id)) return; // we can just accept the job as done

			foreach ($this->get_order_applications($orders_id) as $payment_application_id => $application) {
				if ($amount <= 0) break;

				if ($amount >= $application['applied_amount']) {
					$amount -= $application['applied_amount'];
					$this->change('order_applications', [$payment_application_id => NULL]);
				}
				else {
					$application['applied_amount'] -= $amount;
					$this->change('order_applications', [$payment_application_id => $application]);
					$amount = 0;
				}
			}

			$this->write();
		}
		catch (CKPaymentException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKPaymentException('Failed to remove applications.', $e->getCode(), $e);
		}
	}

	public function apply_to_invoice($invoice_id, $amount) {
		try {
			if ($amount > $this->get_unapplied_amount()) throw new CKPaymentException('Cannot apply more than is remaining on payment.');

			if ($this->has_invoice_applications($invoice_id)) {
				$applications = $this->get_invoice_applications($invoice_id);
			}
			else {
				$applications = [['invoice_id' => $invoice_id, 'applied_amount' => 0]];
			}

			foreach ($applications as $payment_application_id => $application) {
				$application['applied_amount'] += $amount;
				$this->change('invoice_applications', [$payment_application_id => $application]);
				break; // we only want to deal with the first one
			}

			$this->write();
		}
		catch (CKPaymentException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKPaymentException('Failed to create application.', $e->getCode(), $e);
		}
	}

	public function unapply_from_invoice($invoice_id, $amount) {
		try {
			if (!$this->has_invoice_applications($invoice_id)) return; // we can just accept the job as done

			foreach ($this->get_invoice_applications($invoice_id) as $payment_application_id => $application) {
				if ($amount <= 0) break;

				if ($amount >= $application['applied_amount']) {
					$amount -= $application['applied_amount'];
					$this->change('invoice_applications', [$payment_application_id => NULL]);
				}
				else {
					$application['applied_amount'] -= $amount;
					$this->change('invoice_applications', [$payment_application_id => $application]);
					$amount = 0;
				}
			}

			$this->write();
		}
		catch (CKPaymentException $e) {
			throw $e;
		}
		catch (Exception $e) {
			throw new CKPaymentException('Failed to remove applications.', $e->getCode(), $e);
		}
	}

	public function apply_from_order_to_invoice($orders_id, $invoice_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			// simpler and, ultimately, safer to just do it directly in the DB
			prepared_query::execute('INSERT INTO acc_payments_to_invoices (payment_id, invoice_id, credit_amount, credit_date) SELECT payment_id, :invoice_id, amount, NOW() FROM acc_payments_to_orders WHERE order_id = :orders_id', [':invoice_id' => $invoice_id, ':orders_id' => $orders_id]);
			prepared_query::execute('DELETE FROM acc_payments_to_orders WHERE order_id = :orders_id', [':orders_id' => $orders_id]);

			$this->unload('order_applications');
			$this->unload('invoice_applications');
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPaymentException('Failed to move applications.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public function apply_from_invoice_to_order($invoice_id, $orders_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			// simpler and, ultimately, safer to just do it directly in the DB
			prepared_query::execute('INSERT INTO acc_payments_to_orders (payment_id, order_id, amount) SELECT payment_id, :orders_id, credit_amount FROM acc_payments_to_invoices WHERE invoice_id = :invoice_id', [':orders_id' => $orders_id, ':invoice_id' => $invoice_id]);
			prepared_query::execute('DELETE FROM acc_payments_to_invoices WHERE invoice_id = :invoice_id', [':invoice_id' => $invoice_id]);

			$this->unload('order_applications');
			$this->unload('invoice_applications');
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPaymentException('Failed to move applications.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	protected function commit_changes($changes) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if ($this->create()) {
				$this->create_header($this->get_header());
				if (!empty($changes['order_applications'])) $this->create_order_applications($this->get_order_applications());
				if (!empty($changes['invoice_applications'])) $this->create_invoice_applications($this->get_invoice_applications());
			}
			else {
				if (!empty($changes['header'])) {
					$header = [];
					foreach ($changes['header'] as $change_key) $header[$change_key] = $this->get_header($change_key);
					$this->update_header($header); // will throw an exception, we don't want to edit the header
				}
				if (!empty($changes['order_applications'])) {
					$order_applications = [];
					foreach ($changes['order_applications'] as $idx => $change_set) {
						$iapp = $this->get_order_applications();
						$order_applications[$idx] = [];
						foreach ($change_set as $change_key) $order_applications[$idx][$change_key] = $iapp[$idx][$change_key];
					}
					$this->update_order_applications($order_applications);
				}
				if (!empty($changes['invoice_applications'])) {
					$invoice_applications = [];
					foreach ($changes['invoice_applications'] as $idx => $change_set) {
						$iapp = $this->get_invoice_applications();
						$invoice_applications[$idx] = [];
						foreach ($change_set as $change_key) $invoice_applications[$idx][$change_key] = $iapp[$idx][$change_key];
					}
					$this->update_invoice_applications($invoice_applications);
				}
			}
		}
		catch (CKPaymentException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPaymentException('Failed to handle payment changes', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	private function create_header($header) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$header = ck_payment_type::remap('header', $header);

			if (empty($header['payment_date'])) $header['payment_date'] = prepared_expression::NOW();
			elseif ($header['payment_date'] instanceof DateTime) $header['payment_date'] = $header['payment_date']->format('Y-m-d H:i:s');

			$pmt = new prepared_fields($header, prepared_fields::INSERT_QUERY);
			$pmt->whitelist(['customer_id', 'payment_amount', 'payment_method_id', 'payment_ref', 'payment_date', 'customers_extra_logins_id']);

			$payment_id = prepared_query::insert('INSERT INTO acc_payments ('.$pmt->insert_fields().') VALUES ('.$pmt->insert_values().')', $pmt->insert_parameters());
			$this->set_id($payment_id);
			$header['payment_id'] = $payment_id;

			$this->unload('header');
			$this->unload('customer');
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPaymentException('Failed to create payment.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	private function update_header($header) {
		throw new CKPaymentException('Cannot edit payment - you must remove and recreate the payment if you want to change it.');
		/*
		// if the amount equals zero we'll just delete it
		if (isset($header['amount']) && $header['amount'] <= 0) return $this->remove();

		$savepoint_id = prepared_query::transaction_begin();

		try {
			// if amount has been lowered below the amount of applications, throw an exception
			if ($this->get_unapplied_amount() < 0) throw new CKPaymentException('You cannot lower the payment amount below the amount that has been applied to orders and invoices - release those applications first.');

			$header = ck_payment_type::remap('header', $header);

			$update = new prepared_fields($header, prepared_fields::UPDATE_QUERY);
			$update->whitelist(['payment_amount', 'payment_ref']);
			$id = new prepared_fields(['payment_id' => $this->id()]);

			prepared_query::execute('UPDATE acc_payments SET '.$update->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($update, $id));
		}
		catch (CKPaymentException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPaymentException('Failed to update payment.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
		*/
	}

	private function create_order_applications($applications) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			foreach ($applications as $payment_application_id => $application) {
				if (!empty($application['payment_application_id'])) continue;

				$application = ck_payment_type::remap('order_applications', $application);

				$application['payment_id'] = $this->id();

				$app = new prepared_fields($application, prepared_fields::INSERT_QUERY);
				$app->whitelist(['payment_id', 'order_id', 'amount']);

				prepared_query::execute('INSERT INTO acc_payments_to_orders ('.$app->insert_fields().') VALUES ('.$app->insert_values().')', $app->insert_parameters());
			}

			$this->unload('order_applications');
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPaymentException('Failed to create payment order applications.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	private function update_order_applications($applications) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$new_applications = [];
			foreach ($applications as $payment_application_id => $application) {
				if (empty($application['payment_application_id'])) $new_applications[] = $application;
				else {
					$application = ck_payment_type::remap('order_applications', $application);

					$update = new prepared_fields($application, prepared_fields::UPDATE_QUERY);
					$update->whitelist('amount');
					$id = new prepared_fields(['id' => $payment_application_id]);

					prepared_query::execute('UPDATE acc_payments_to_orders SET '.$update->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($update, $id));
				}
			}

			$this->unload('order_applications');

			if (!empty($new_applications)) $this->create_order_applications($new_applications);
		}
		catch (CKPaymentException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPaymentException('Failed to update payment order applications.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	private function create_invoice_applications($applications) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			foreach ($applications as $payment_application_id => $application) {
				if (!empty($application['payment_application_id'])) continue;

				$application = ck_payment_type::remap('invoice_applications', $application);

				$application['payment_id'] = $this->id();
				if (empty($application['credit_date'])) $application['credit_date'] = prepared_expression::NOW();

				$app = new prepared_fields($application, prepared_fields::INSERT_QUERY);
				$app->whitelist(['payment_id', 'invoice_id', 'credit_amount', 'credit_date']);

				prepared_query::execute('INSERT INTO acc_payments_to_invoices ('.$app->insert_fields().') VALUES ('.$app->insert_values().')', $app->insert_parameters());
			}

			$this->unload('invoice_applications');
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPaymentException('Failed to create payment invoice applications.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	private function update_invoice_applications($applications) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$new_applications = [];
			foreach ($applications as $payment_application_id => $application) {
				if (empty($application['payment_application_id'])) $new_applications[] = $application;
				else {
					$application = ck_payment_type::remap('invoice_applications', $application);

					$update = new prepared_fields($application, prepared_fields::UPDATE_QUERY);
					$update->whitelist('amount');
					$id = new prepared_fields(['id' => $payment_application_id]);

					prepared_query::execute('UPDATE acc_payments_to_invoices SET '.$update->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($update, $id));
				}
			}

			$this->unload('invoice_applications');

			if (!empty($new_applications)) $this->create_invoice_applications($new_applications);
		}
		catch (CKPaymentException $e) {
			prepared_query::fail_transaction();
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::fail_transaction();
			throw new CKPaymentException('Failed to update payment invoice applications.', $e->getCode(), $e);
		}
		finally {
			prepared_query::transaction_end(NULL, $savepoint_id);
		}
	}

	public static function legacy_insert_credit($customers_id, $orders_id, $payment_type_code, $payment_ref, $amount, $date=NULL) {
		$payment_type_id = prepared_query::fetch('SELECT id FROM payment_method WHERE code = :payment_type_code', cardinality::SINGLE, [':payment_type_code' => $payment_type_code]);
		if (empty($payment_type_id)) throw new CKPaymentException('Payment Type Code '.$payment_type_code.' is not valid');

		$date = ck_datetime::datify($date, TRUE);

		$payment = [
			'customers_id' => $customers_id,
			'amount' => $amount,
			'payment_method_id' => $payment_type_id,
			'reference_number' => $payment_ref,
			'payment_date' => $date->timestamp(),
		];

		$pmt = new self;
		$pmt->enter_payment($payment);

		if (!empty($orders_id)) {
			if ($payment_type_code == 'credit_memo') prepared_query::execute('DELETE FROM acc_payments_to_orders WHERE order_id = :orders_id', [':orders_id' => $orders_id]);
			else $pmt->apply_to_order($orders_id, $amount);
		}

		$admin_id = !empty($_SESSION['login_id'])?$_SESSION['login_id']:0;

		prepared_query::execute('INSERT INTO acc_transaction_history (transaction_type, transaction_date, admin_id, order_id, payment_id, customer_id) VALUES (:transaction_type, NOW(), :admin_id, :order_id, :payment_id, :customer_id)', [':transaction_type' => ($amount>0?'Insert Credit':'Insert Reverse Credit'), ':admin_id' => $admin_id, ':order_id' => $orders_id, ':payment_id' => $pmt->id(), ':customer_id' => $customers_id]);

		return $pmt->id();
	}

	public static function legacy_apply_credit($payment_id, $invoice_id, $amount) {
		$pmt = self::get_with_instance($payment_id);
		$invoice = ck_invoice::strict_load($invoice_id);

		if (empty($invoice)) throw new CKPaymentException('Invalid Invoice ID: '.$invoice_id);

		$pmt->apply_to_invoice($invoice->id(), $amount);

		$app = current($pmt->get_invoice_applications($invoice->id()));

		prepared_query::execute('INSERT INTO acc_transaction_history (transaction_type, transaction_date, admin_id, invoice_id, order_id, payment_id, customer_id) VALUES (:transaction_type, NOW(), :admin_id, :invoice_id, :order_id, :payment_id, :customer_id)', [':transaction_type' => 'Applied Credit to Invoice', ':admin_id' => $_SESSION['login_id'], ':invoice_id' => $invoice->id(), ':order_id' => $invoice->get_header('orders_id'), ':payment_id' => $pmt->id(), ':customer_id' => $invoice->get_header('customers_id')]);
	}

	public static function legacy_insert_note($text, $type, $id) {
		return prepared_query::insert('INSERT INTO acc_notes (note_type, note_type_id, note_text) VALUES (:note_type, :note_type_id, :note_text)', [':note_type' => $type, ':note_type_id' => $id, ':note_text' => $text]);
	}

	/*-------------------------------
	// other
	-------------------------------*/
}

class CKPaymentException extends CKMasterArchetypeException {
}

class CKRecoverablePaymentException extends CKMasterArchetypeException {
	// this represents a non-fatal exception that does not keep the intended process from completing
}
?>
