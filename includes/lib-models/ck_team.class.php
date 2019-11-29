<?php
class ck_team extends ck_archetype {

	protected static $skeleton_type = 'ck_team_type';

	protected static $queries = [
		'team_header' => [
			'qry' => 'SELECT team_id, label, email_address, phone_number, local_phone_number, sales_team, salesforce_key, active FROM ck_teams WHERE team_id = :team_id',
			'cardinality' => cardinality::ROW
		],

		'members' => [
			'qry' => 'SELECT team_assignment_id, admin_id, assignment_date FROM ck_team_assignments WHERE team_id = :team_id',
			'cardinality' => cardinality::SET
		],
	];

	// using the generic ck_type for type hinting allows for some limited use of duck typing
	public function __construct($team_id, ck_team_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($team_id);

		if (!$this->skeleton->built('team_id')) $this->skeleton->load('team_id', $team_id);
		if ($this->skeleton->built('header')) $this->normalize_header();

		self::register($team_id, $this->skeleton);
	}

	public function id() {
		return $this->skeleton->get('team_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$header = self::fetch('team_header', [':team_id' => $this->id()]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		$header['sales_team'] = CK\fn::check_flag($header['sales_team']);
		$header['active'] = CK\fn::check_flag($header['active']);

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$header = self::fetch('team_header', [':team_id' => $this->id()]);
		$this->skeleton->load('header', $header);
		$this->normalize_header();
	}

	private function build_members() {
		$members = self::fetch('members', [':team_id' => $this->id()]);

		foreach ($members as &$member) {
			$member['member'] = new ck_admin($member['admin_id']);
			$member['assignment_date'] = self::DateTime($member['assignment_date']);
		}

		$this->skeleton->load('members', $members);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	public function has_members() {
		if (!$this->skeleton->built('members')) $this->build_members();
		return $this->skeleton->has('members');
	}

	public function get_members() {
		if (!$this->has_members()) return [];
		return $this->skeleton->get('members');
	}

	public static function get_all_teams() {
		if ($team_ids = prepared_query::fetch('SELECT team_id FROM ck_teams ORDER BY label ASC', cardinality::COLUMN, [])) {
			$teams = [];

			foreach ($team_ids as $team_id) {
				$teams[] = new self($team_id);
			}

			return $teams;
		}
		else return [];
	}

	public static function get_sales_teams() {
		if ($team_ids = prepared_query::fetch('SELECT team_id FROM ck_teams WHERE sales_team = 1 ORDER BY label ASC', cardinality::COLUMN, [])) {
			$teams = [];

			foreach ($team_ids as $team_id) {
				$teams[] = new self($team_id);
			}

			return $teams;
		}
		else return [];
	}

	/*-------------------------------
	// change data
	-------------------------------*/

	public static function create($data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$params = new ezparams($data);
			$team_id = prepared_query::insert('INSERT INTO ck_teams ('.$params->insert_cols().') VALUES ('.$params->insert_params(TRUE).')', $params->query_vals([], TRUE));

			$team = new self($team_id);

			prepared_query::transaction_commit($savepoint_id);
			return $team;
		}
		catch (CKTeamException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKTeamException('Failed to create new team: '.$e->getMessage());
		}
	}

	public function update($data) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$params = new ezparams($data);
			prepared_query::execute('UPDATE ck_teams SET '.$params->update_cols(TRUE).' WHERE team_id = :team_id', $params->query_vals(['team_id' => $this->id()], TRUE));

			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKTeamException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKTeamException('Failed to update team: '.$e->getMessage());
		}
	}

	public function remove() {
	}

	public function deactivate() {
		$this->update(['active' => 0]);
	}

	public function activate() {
		$this->update(['active' => 1]);
	}

	public function add_member($admin_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('INSERT INTO ck_team_assignments (team_id, admin_id) VALUES (:team_id, :admin_id)', [':team_id' => $this->id(), ':admin_id' => $admin_id]);
			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKTeamException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKTeamException('Failed to add team member: '.$e->getMessage());
		}
	}

	public function remove_member($admin_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			prepared_query::execute('DELETE FROM ck_team_assignments WHERE team_id = :team_id AND admin_id = :admin_id', [':team_id' => $this->id(), ':admin_id' => $admin_id]);
			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKTeamException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKTeamException('Failed to remove team member: '.$e->getMessage());
		}
	}

	public static function auto_assign_sales_team(ck_customer2 $customer) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			$user = ck_admin::login_instance(ck_admin::CONTEXT_ADMIN);

			$email_address = explode('@', $customer->get_header('email_address'));
			$email_domain = strtolower(trim(end($email_address)));

			$skip_domain = prepared_query::fetch('SELECT domain_exclude_id FROM ck_team_segment_round_robin_domain_excludes WHERE email_domain = :email_domain', cardinality::SINGLE, [':email_domain' => $email_domain]);

			if (!empty($skip_domain)) $previous_assignment = prepared_query::fetch('SELECT sales_team_id FROM customers WHERE email_domain = :email_domain ORDER BY customers_id DESC', cardinality::SINGLE, [':email_domain' => $email_domain]);

			if (!empty($previous_assignment)) $customer->change_sales_team($previous_assignment);
			elseif (!empty($user) && $user->has_sales_team()) $customer->change_sales_team($user->get_sales_team()['team']->id());
			else {
				// for student segment we will assign the individual customer segment id so that it will round robin the same way as individual
				$customer_segment_id = $customer->get_header('customer_segment_id');
				if ($customer_segment_id == ck_customer2::$customer_segment_map['ST']) $customer_segment_id = ck_customer2::$customer_segment_map['IN'];

				if ($sales_team_id = self::round_robin($customer_segment_id)) $customer->change_sales_team($sales_team_id);
			}

			prepared_query::transaction_commit($savepoint_id);
		}
		catch (CKTeamException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKTeamException('Failed to assign sales team: '.$e->getMessage());
		}
	}

	public static function round_robin($customer_segment_id) {
		$savepoint_id = prepared_query::transaction_begin();

		try {
			if ($round_robin = prepared_query::fetch('SELECT * FROM ck_team_segment_round_robin_groups WHERE customer_segment_id = :customer_segment_id ORDER BY position ASC, team_segment_round_robin_group_id ASC', cardinality::SET, [':customer_segment_id' => $customer_segment_id])) {

				$group_size = count($round_robin);

				for ($idx=0,$next=FALSE; $idx<$group_size&&!$next; $idx++) {
					if (CK\fn::check_flag($round_robin[$idx]['last_assigned'])) $next = TRUE;
				}

				$idx %= $group_size;

				prepared_query::execute('UPDATE ck_team_segment_round_robin_groups SET last_assigned = CASE WHEN team_id = :team_id THEN 1 ELSE 0 END WHERE customer_segment_id = :customer_segment_id', [':team_id' => $round_robin[$idx]['team_id'], ':customer_segment_id' => $customer_segment_id]);

				prepared_query::transaction_commit($savepoint_id);
				return $round_robin[$idx]['team_id'];
			}
			else return NULL;
		}
		catch (CKTeamException $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw $e;
		}
		catch (Exception $e) {
			prepared_query::transaction_rollback($savepoint_id);
			throw new CKTeamException('Failed to update round robin assignment: '.$e->getMessage());
		}
	}

	/*-------------------------------
	// other
	-------------------------------*/
}

class CKTeamException extends Exception {
}
?>
