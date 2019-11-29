<?php
class ck_admin extends ck_archetype implements ck_site_user_interface {
	use ck_site_user_trait;

	protected static $skeleton_type = 'ck_admin_type';

	protected static $queries = [
		'admin_header' => [
			'qry' => 'SELECT a.admin_id, a.admin_firstname as first_name, a.admin_lastname as last_name, a.admin_email_address as email_address, a.admin_created as date_created, a.admin_modified as last_modified_date, a.admin_logdate as last_login_date, a.admin_lognum as login_counter, a.rfq_signature, a.rfq_greeting, a.admin_groups_id as legacy_group_id, ag.admin_groups_name as legacy_group, a.status as active, a.broker, a.account_manager, a.phone_number FROM admin a LEFT JOIN admin_groups ag ON a.admin_groups_id = ag.admin_groups_id WHERE a.admin_id = :admin_id',
			'cardinality' => cardinality::ROW,
			'stmt' => NULL
		],

		// this is only used by the ck_site_user trait
		'login_attempt' => [
			'qry' => 'SELECT admin_id as account_id, admin_firstname, admin_lastname, admin_password as password, password_info, legacy_salt, admin_email_address FROM admin WHERE admin_email_address LIKE :email AND status = 1',
			'cardinality' => cardinality::ROW
		],

		'update_password' => [
			'qry' => 'UPDATE admin SET admin_password = :password, password_info = 0, legacy_salt = NULL WHERE admin_id = :admin_id',
			'cardinality' => cardinality::NONE
		],

		'update_phone_number' => [
			'qry' => 'UPDATE admin SET phone_number = :phone_number WHERE admin_id = :admin_id',
			'cardinality' => cardinality::NONE
		],

		'teams' => [
			'qry' => 'SELECT team_assignment_id, team_id, assignment_date FROM ck_team_assignments WHERE admin_id = :admin_id ORDER BY team_assignment_id ASC',
			'cardinality' => cardinality::SET
		],

		'roles' => [
			'qry' => 'SELECT ar.admin_role_id, ar.role_name as role, atr.status FROM ck_access_admins_to_roles atr JOIN ck_access_admin_roles ar ON atr.admin_role_id = ar.admin_role_id WHERE atr.admin_id = :admin_id AND ar.active = 1',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'groups' => [
			'qry' => 'SELECT pg.permission_group_id, pg.group_name as `group`, atg.status FROM ck_access_admins_to_groups atg JOIN ck_access_permission_groups pg ON atg.permission_group_id = pg.permission_group_id WHERE atg.admin_id = :admin_id AND pg.active = 1',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'legacy_permissions' => [
			'qry' => 'SELECT use_master_password, update_ipn_quantity, update_ipn_weight, rename_ipn, update_ipn_average_cost, update_serial, update_pay_tax, update_net_terms, upload_images, update_target_min_qty, update_target_max_qty, mark_as_reviewed, change_ipn_category, change_warranties, change_dealer_warranties, ipn_reviewer FROM admin WHERE admin_id = :admin_id',
			'cardinality' => cardinality::ROW,
		],

		'permissions' => [
			'qry' => 'SELECT p.permission_id, p.permission_name as permission, atp.status FROM ck_access_admins_to_permissions atp JOIN ck_access_permissions p ON atp.permission_id = p.permission_id WHERE atp.admin_id = :admin_id AND p.active = 1',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],
	];

	public static $solutionsteam_id = 137;
	public static $ckcorporate_id = 118;

	// these probably don't belong here long term, but currently these values are hard coded in several places
	public static $cksales_id = 174;
	public static $local_sales_phone = '678-597-5000';
	public static $toll_free_sales_phone = '888-622-0223';
	public static $sales_email = 'sales@cablesandkits.com';
	public static $sales_name = 'CK Sales';

	public function __construct($admin_id, ck_admin_type $skeleton=NULL) {
		$this->skeleton = !empty($skeleton)?$skeleton:self::get_record($admin_id);

		if (!$this->skeleton->built('admin_id')) $this->skeleton->load('admin_id', $admin_id);
		if ($this->skeleton->built('header')) $this->normalize_header();

		self::register($admin_id, $this->skeleton);
	}

	public static function current_id() {
		return $_SESSION['login_id'];
	}

	public function id() {
		return $this->skeleton->get('admin_id');
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function normalize_header() {
		if (!$this->skeleton->built('header')) {
			$header = self::fetch('admin_header', [$this->id()]);
		}
		else {
			$header = $this->skeleton->get('header');
			$this->skeleton->rebuild('header');
		}

		if (!($header['date_created'] instanceof DateTime)) $header['date_created'] = new DateTime($header['date_created']);
		if (!($header['last_modified_date'] instanceof DateTime)) $header['last_modified_date'] = new DateTime($header['last_modified_date']);
		if (!($header['last_login_date'] instanceof DateTime)) $header['last_login_date'] = new DateTime($header['last_login_date']);

		$header['active'] = CK\fn::check_flag($header['active']);
		$header['broker'] = CK\fn::check_flag($header['broker']);
		$header['account_manager'] = CK\fn::check_flag($header['account_manager']);

		$this->skeleton->load('header', $header);
	}

	private function build_header() {
		$this->skeleton->load('header', self::fetch('admin_header', [':admin_id' => $this->id()]));
		$this->normalize_header();
	}

	private function build_teams() {
		$teams = self::fetch('teams', [':admin_id' => $this->id()]);

		foreach ($teams as &$team) {
			$team['team'] = new ck_team($team['team_id']);
			$team['assignment_date'] = self::DateTime($team['assignment_date']);
		}

		$this->skeleton->load('teams', $teams);
	}

	private function build_roles() {
		$this->skeleton->load('roles', self::fetch('roles', [':admin_id' => $this->id()]));
	}

	private function build_groups() {
		$this->skeleton->load('groups', self::fetch('groups', [':admin_id' => $this->id()]));
	}

	private function build_direct_permissions() {
		$this->skeleton->load('direct_permissions', self::fetch('permissions', [':admin_id' => $this->id()]));
	}

	private function build_permissions() {
		$direct_permissions = $this->get_direct_permissions();
		$groups = $this->get_groups();
		$roles = $this->get_roles();

		$access = self::get_access();

		$all_roles = $access->get_roles();
		$all_groups = $access->get_groups();
		$all_permissions = $access->get_permissions();

		$permissions = [];

		// we go from least specific (roles) to most specific (permissions) - a more specific status will override a less specific status
		// so if a role revokes access but a group or permission grants it, it's granted

		if (!empty($roles)) {
			foreach ($roles as $role) {
				if (!empty($all_roles[$role['admin_role_id']]['groups'])) {
					foreach ($all_roles[$role['admin_role_id']]['groups'] as $permission_group_id => $group_status) {
						foreach ($all_groups[$permission_group_id]['permissions'] as $permission_id => $perm_status) {
							$status = ck_access::GRANTED;
							if ($group_status == ck_access::REVOKED || $perm_status == ck_access::REVOKED) $status = ck_status::REVOKED;

							$perm = [
								'permission_id' => $permission_id,
								'permission' => $all_permissions[$permission_id]['permission_name'],
								'status' => $status
							];

							$permissions[$permission_id] = $perm;
							$permissions[$all_permissions[$permission_id]['permission_name']] = $perm;
						}
					}
				}

				foreach ($all_roles[$role['admin_role_id']]['permissions'] as $permission_id => $status) {
					$perm = [
						'permission_id' => $permission_id,
						'permission' => $all_permissions[$permission_id]['permission_name'],
						'status' => $status
					];

					$permissions[$permission_id] = $perm;
					$permissions[$all_permissions[$permission_id]['permission_name']] = $perm;
				}
			}
		}

		if (!empty($groups)) {
			foreach ($groups as $group) {
				foreach ($all_groups[$group['permission_group_id']]['permissions'] as $permission_id => $status) {
					$perm = [
						'permission_id' => $permission_id,
						'permission' => $all_permissions[$permission_id]['permission_name'],
						'status' => $status
					];

					$permissions[$permission_id] = $perm;
					$permissions[$all_permissions[$permission_id]['permission_name']] = $perm;
				}
			}
		}

		if (!empty($direct_permissions)) {
			foreach ($direct_permissions as $perm) {
				$permissions[$perm['permission_id']] = $perm;
				$permissions[$perm['permission']] = $perm;
			}
		}

		$this->skeleton->load('permissions', $permissions);
	}

	private function build_legacy_permissions() {
		$perms = self::fetch('legacy_permissions', [':admin_id' => $this->id()]);

		foreach (array_keys($perms) as $perm) {
			$perms[$perm] = CK\fn::check_flag($perms[$perm]);
		}

		$this->skeleton->load('legacy_permissions', $perms);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	public function get_header($key=NULL) {
		if (!$this->skeleton->built('header')) $this->build_header();
		if (empty($key)) return $this->skeleton->get('header');
		else return $this->skeleton->get('header', $key);
	}

	public function is_top_admin() {
		return $this->get_header('legacy_group_id') == 1;
	}

	public function get_name() {
		return $this->get_header('first_name').' '.$this->get_header('last_name');
	}

	public function get_normalized_name() {
		return $this->get_header('last_name').', '.$this->get_header('first_name');
	}

	public function has_teams() {
		if (!$this->skeleton->built('teams')) $this->build_teams();
		return $this->skeleton->has('teams');
	}

	public function get_teams() {
		if (!$this->has_teams()) return [];
		return $this->skeleton->get('teams');
	}

	public function has_sales_team() {
		foreach ($this->get_teams() as $team) {
			if ($team['team']->is('sales_team')) return TRUE;
		}

		return FALSE;
	}

	public function get_sales_team() {
		foreach ($this->get_teams() as $team) {
			if ($team['team']->is('sales_team')) return $team;
		}

		return FALSE;
	}

	public function has_roles() {
		if (!$this->skeleton->built('roles')) $this->build_roles();
		return $this->skeleton->has('roles');
	}

	public function get_roles() {
		if (!$this->has_roles()) return NULL;
		return $this->skeleton->get('roles');
	}

	public function has_groups() {
		if (!$this->skeleton->built('groups')) $this->build_groups();
		return $this->skeleton->has('groups');
	}

	public function get_groups() {
		if (!$this->has_groups()) return NULL;
		return $this->skeleton->get('groups');
	}

	public function has_direct_permissions() {
		if (!$this->skeleton->built('direct_permissions')) $this->build_direct_permissions();
		return $this->skeleton->has('direct_permissions');
	}

	public function get_direct_permissions() {
		if (!$this->has_direct_permissions()) return NULL;
		return $this->skeleton->get('direct_permissions');
	}

	public function has_permissions() {
		if (!$this->skeleton->built('permissions')) $this->build_permissions();
		return $this->skeleton->has('permissions');
	}

	public function get_permissions() {
		if (!$this->has_permissions()) return NULL;
		return $this->skeleton->get('permissions');
	}

	public function has_permission_for($permission) {
		$permissions = $this->get_permissions();

		// if this user has *everything* granted, we can skip the rest
		if (!empty($permissions['everything']) && $permissions['everything']['status'] == ck_access::GRANTED) return TRUE;

		if (empty($permissions[$permission])) return FALSE;

		// do a check to match GRANTED rather than a check to match REVOKED - if it's not granted, it's revoked 
		if ($permissions[$permission]['status'] != ck_access::GRANTED) return FALSE;

		return TRUE;
	}

	public function has_legacy_permissions() {
		if (!$this->skeleton->built('legacy_permissions')) $this->build_legacy_permissions();
		return $this->skeleton->has('legacy_permissions');
	}

	public function get_legacy_permissions() {
		if (!$this->has_legacy_permissions()) return NULL;
		return $this->skeleton->get('legacy_permissions');
	}

	public function has_legacy_permission_for($permission) {
		$permissions = $this->get_legacy_permissions();

		if (isset($permissions[$permission]) && $permissions[$permission]) return TRUE;
		else return FALSE;
	}

	public static function get_admin_by_email($email) {
		if ($admin_id = self::query_fetch('SELECT admin_id FROM admin WHERE admin_email_address = :email', cardinality::SINGLE, [':email' => $email])) {
			return new self($admin_id);
		}
		else return NULL;
	}

	public static function get_all_admins(Callable $sort=NULL) {
		if ($admin_ids = self::query_fetch('SELECT admin_id FROM admin ORDER BY admin_id ASC', cardinality::COLUMN, [])) {
			$admins = [];

			foreach ($admin_ids as $admin_id) {
				$admins[] = new self($admin_id);
			}

			if (!empty($sort)) usort($admins, $sort);

			return $admins;
		}
		else return [];
	}

	public static function get_all_active_admins(Callable $sort=NULL) {
		if ($admin_ids = self::query_fetch('SELECT admin_id FROM admin WHERE status = 1 ORDER BY admin_id ASC', cardinality::COLUMN, [])) {
			$admins = [];

			foreach ($admin_ids as $admin_id) {
				$admins[] = new self($admin_id);
			}

			if (!empty($sort)) usort($admins, $sort);

			return $admins;
		}
		else return [];
	}

	public static function get_account_managers(Callable $sort=NULL) {
		if ($admin_ids = self::query_fetch('SELECT admin_id FROM admin WHERE status = 1 AND account_manager = 1 ORDER BY admin_id ASC', cardinality::COLUMN, [])) {
			$admins = [];

			foreach ($admin_ids as $admin_id) {
				$admins[] = new self($admin_id);
			}

			if (!empty($sort)) usort($admins, $sort);

			return $admins;
		}
		else return [];
	}

	public static function get_legacy_groups() {
		return prepared_query::fetch('SELECT admin_groups_id, admin_groups_name FROM admin_groups ORDER BY admin_groups_name');
	}

	/*-------------------------------
	// sorting list results
	-------------------------------*/

	public static function sort_by_name($a, $b) {
		$res = strcasecmp($a->get_header('last_name'), $b->get_header('last_name'));
		if (!empty($res)) return $res;
		else return strcasecmp($a->get_header('first_name'), $b->get_header('first_name'));
	}

	/*-------------------------------
	// update data
	-------------------------------*/

	public function update_password($password, $account_id=NULL) {
		$savepoint = self::transaction_begin();

		try {
			$pinfo = password_get_info($password);
			if ($pinfo['algo'] == 0) $password = self::encrypt_password($password);

			// $account_id has no use in admin

			self::execute('update_password', [':password' => $password, ':admin_id' => $this->id()]);

			self::transaction_commit();
			return TRUE;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKAdminException('Failed to handle password');
		}
	}

	public function revalidate_login($password) {
		$login = self::attempt_login($this->get_header('email_address'), $password);

		if ($login['status'] == self::LOGIN_STATUS_PASS) return TRUE;
		else return FALSE;
	}

	public function soft_disable() {
		prepared_query::execute('UPDATE admin SET admin_password = :direct_password WHERE admin_id = :admin_id', [':direct_password' => 'DELETED', ':admin_id' => $this->id()]);
	}

	public function deactivate() {
		$this->soft_disable();
		prepared_query::execute('UPDATE admin SET status = 0 WHERE admin_id = :admin_id', [':admin_id' => $this->id()]);
	}

	public function reactivate() {
		prepared_query::execute('UPDATE admin SET status = 1 WHERE admin_id = :admin_id', [':admin_id' => $this->id()]);
		$this->legacy_reset_password();
	}

	public function set_legacy_permission($permission, $status, $strict=TRUE) {
		$format = $this->skeleton->format('legacy_permissions');

		if (!array_key_exists($permission, $format)) {
			if ($strict) throw new CKAdminException('['.$permission.'] is not a valid admin permission');
			else return;
		}

		$update = new prepared_fields([$permission => CK\fn::check_flag($status)?1:0], prepared_fields::UPDATE_QUERY);
		$id = new prepared_fields(['admin_id' => $this->id()]);

		prepared_query::execute('UPDATE admin SET '.$update->update_sets().' WHERE '.$id->where_clause(), prepared_fields::consolidate_parameters($update, $id));
	}

	public function legacy_reset_password($new_admin=FALSE) {
		/*$new_password = '';

		$salt = "ABCDEFGHIJKLMNOPQRSTUVWXWZabchefghjkmnpqrstuvwxyz0123456789";
		srand((double)microtime()*1000000);

		for ($i=0; $i<=7; $i++) {
			$num = rand() % 33;
			$tmp = substr($salt, $num, 1);
			$new_password = $new_password.$tmp;
		}*/

		$new_password = self::generate_code(['charset_weights' => [.3, .3, .3, .1], 'common_denom' => 10]);

		$this->update_password($new_password);

		if ($new_admin) $subject = 'New CK Admin Account';
		else $subject = 'New CK Admin Account Password';

		$body = 'Hi '.$this->get_header('first_name').',<br><br>You can acccess the admin panel with the following password.  Once you have accessed the admin, please change your password immediately!<br><br>Website: <a href="'.PRIVATE_FQDN.'">'.PRIVATE_FQDN.'</a><br>Username: '.$this->get_header('email_address').'<br>Password: '.$new_password.'<br><br>Thanks!<br>CablesAndKits.com<br><br>This is an automated system message, please do not reply';
		$mailer = service_locator::get_mail_service();
		$mail = $mailer->create_mail()
			->set_subject($subject)
			->add_to($this->get_header('email_address'), $this->get_name())
			->set_from('webmaster@cablesandkits.com')
			->set_body($body);
		$mailer->send($mail);
	}

	/*-------------------------------
	// email
	-------------------------------*/

	public function send_password_email() {
	}

	/*-------------------------------
	// singleton access
	-------------------------------*/

	// wrangle the database, if we want to set it explicitly for the call or the class (otherwise, fall back to the global instance)
	protected static $access = NULL;
	public static function set_access($access) {
		static::$access = $access;
	}
	// this allows us to use dependancy injection without requiring it
	protected static function get_access($access=NULL) {
		!$access?(!empty(self::$access)?$access=self::$access:$access=@$GLOBALS['access']):NULL;
		return $access;
	}

	public function update_phone_number($new_phone_number) {
		$savepoint = self::transaction_begin();

		try {
			self::execute('update_phone_number', [':admin_id' => $this->id(), ':phone_number' => $new_phone_number]);

			self::transaction_commit();
			return TRUE;
		}
		catch (CKAdminException $e) {
			self::transaction_rollback($savepoint);
			throw $e;
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint);
			throw new CKAdminException('Failed to update phone number');
		}
	}
}

class CKAdminException extends CKMasterArchetypeException {
}
?>
