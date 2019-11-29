<?php
class ck_access extends ck_singleton {

	private $skeleton;

	protected static $skeleton_type = 'ck_access_type';

	protected static $queries = [
		'permissions' => [
			'qry' => 'SELECT * FROM ck_access_permissions ORDER BY permission_name ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'groups' => [
			'qry' => 'SELECT * FROM ck_access_permission_groups ORDER BY group_name ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		'roles' => [
			'qry' => 'SELECT * FROM ck_access_admin_roles ORDER BY role_name ASC',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		// permissions relationships
		'permissions_roles' => [
			'qry' => 'SELECT * FROM ck_access_roles_to_permissions',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],
		'permissions_groups' => [
			'qry' => 'SELECT * FROM ck_access_groups_to_permissions',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],
		'permissions_admins' => [
			'qry' => 'SELECT * FROM ck_access_admins_to_permissions',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		// roles relationships
		'roles_groups' => [
			'qry' => 'SELECT * FROM ck_access_roles_to_groups',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],
		'roles_admins' => [
			'qry' => 'SELECT * FROM ck_access_admins_to_roles',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		],

		// groups relationships
		'groups_admins' => [
			'qry' => 'SELECT * FROM ck_access_admins_to_groups',
			'cardinality' => cardinality::SET,
			'stmt' => NULL
		]
	];

	const GRANTED = 'GRANTED';
	const REVOKED = 'REVOKED';

	protected function init($parameters=[]) {
		$this->skeleton = new self::$skeleton_type();
	}

	/*-------------------------------
	// build data on demand
	-------------------------------*/

	private function build_relationships() {
		$permissions_roles = self::fetch('permissions_roles', []);
		$permissions_groups = self::fetch('permissions_groups', []);
		$permissions_admins = self::fetch('permissions_admins', []);

		$roles_groups = self::fetch('roles_groups', []);
		$roles_admins = self::fetch('roles_admins', []);

		$groups_admins = self::fetch('groups_admins', []);

		$relationships = [
			'permissions' => [],
			'roles' => [],
			'groups' => [],
			'admins' => []
		];

		foreach ($permissions_roles as $rel) {
			if (empty($relationships['permissions'][$rel['permission_id']])) $relationships['permissions'][$rel['permission_id']] = [];
			$relationships['permissions'][$rel['permission_id']][] = ['element_type' => 'roles', 'element_id' => $rel['admin_role_id'], 'status' => $rel['status']];

			if (empty($relationships['roles'][$rel['admin_role_id']])) $relationships['roles'][$rel['admin_role_id']] = [];
			$relationships['roles'][$rel['admin_role_id']][] = ['element_type' => 'permissions', 'element_id' => $rel['permission_id'], 'status' => $rel['status']];
		}

		foreach ($permissions_groups as $rel) {
			if (empty($relationships['permissions'][$rel['permission_id']])) $relationships['permissions'][$rel['permission_id']] = [];
			$relationships['permissions'][$rel['permission_id']][] = ['element_type' => 'groups', 'element_id' => $rel['permission_group_id'], 'status' => $rel['status']];

			if (empty($relationships['groups'][$rel['permission_group_id']])) $relationships['groups'][$rel['permission_group_id']] = [];
			$relationships['groups'][$rel['permission_group_id']][] = ['element_type' => 'permissions', 'element_id' => $rel['permission_id'], 'status' => $rel['status']];
		}

		foreach ($permissions_admins as $rel) {
			if (empty($relationships['permissions'][$rel['permission_id']])) $relationships['permissions'][$rel['permission_id']] = [];
			$relationships['permissions'][$rel['permission_id']][] = ['element_type' => 'admins', 'element_id' => $rel['admin_id'], 'status' => $rel['status']];

			if (empty($relationships['admins'][$rel['admin_id']])) $relationships['admins'][$rel['admin_id']] = [];
			$relationships['admins'][$rel['admin_id']][] = ['element_type' => 'permissions', 'element_id' => $rel['permission_id'], 'status' => $rel['status']];
		}

		foreach ($roles_groups as $rel) {
			if (empty($relationships['roles'][$rel['admin_role_id']])) $relationships['roles'][$rel['admin_role_id']] = [];
			$relationships['roles'][$rel['admin_role_id']][] = ['element_type' => 'groups', 'element_id' => $rel['permission_group_id'], 'status' => $rel['status']];

			if (empty($relationships['groups'][$rel['permission_group_id']])) $relationships['groups'][$rel['permission_group_id']] = [];
			$relationships['groups'][$rel['permission_group_id']][] = ['element_type' => 'roles', 'element_id' => $rel['admin_role_id'], 'status' => $rel['status']];
		}

		foreach ($roles_admins as $rel) {
			if (empty($relationships['roles'][$rel['admin_role_id']])) $relationships['roles'][$rel['admin_role_id']] = [];
			$relationships['roles'][$rel['admin_role_id']][] = ['element_type' => 'admins', 'element_id' => $rel['admin_id'], 'status' => $rel['status']];

			if (empty($relationships['admins'][$rel['admin_id']])) $relationships['admins'][$rel['admin_id']] = [];
			$relationships['admins'][$rel['admin_id']][] = ['element_type' => 'roles', 'element_id' => $rel['admin_role_id'], 'status' => $rel['status']];
		}

		foreach ($groups_admins as $rel) {
			if (empty($relationships['groups'][$rel['permission_group_id']])) $relationships['groups'][$rel['permission_group_id']] = [];
			$relationships['groups'][$rel['permission_group_id']][] = ['element_type' => 'admins', 'element_id' => $rel['admin_id'], 'status' => $rel['status']];

			if (empty($relationships['admins'][$rel['admin_id']])) $relationships['admins'][$rel['admin_id']] = [];
			$relationships['admins'][$rel['admin_id']][] = ['element_type' => 'groups', 'element_id' => $rel['permission_group_id'], 'status' => $rel['status']];
		}

		$this->skeleton->load('relationships', $relationships);
	}

	private function build_permissions() {
		$permissions = [];

		$perm_raw = self::fetch('permissions', []);

		$perm_relationships = $this->get_relationships('permissions');

		foreach ($perm_raw as $perm) {
			$perm['date_created'] = new DateTime($perm['date_created']);
			$perm['groups'] = [];
			$perm['roles'] = [];
			$perm['admins'] = [];

			if (!empty($perm_relationships[$perm['permission_id']])) {
				foreach ($perm_relationships[$perm['permission_id']] as $rel) {
					$perm[$rel['element_type']][$rel['element_id']] = $rel['status'];
				}
			}

			$permissions[$perm['permission_id']] = $perm;
		}

		$this->skeleton->load('permissions', $permissions);
	}

	private function build_groups() {
		$groups = [];

		$group_raw = self::fetch('groups', []);

		$group_relationships = $this->get_relationships('groups');

		foreach ($group_raw as $group) {
			$group['date_created'] = new DateTime($group['date_created']);
			$group['permissions'] = [];
			$group['roles'] = [];
			$group['admins'] = [];

			if (!empty($group_relationships[$group['permission_group_id']])) {
				foreach ($group_relationships[$group['permission_group_id']] as $rel) {
					$group[$rel['element_type']][$rel['element_id']] = $rel['status'];
				}
			}

			$groups[$group['permission_group_id']] = $group;
		}

		$this->skeleton->load('groups', $groups);
	}

	private function build_roles() {
		$roles = [];

		$role_raw = self::fetch('roles', []);

		$role_relationships = $this->get_relationships('roles');

		foreach ($role_raw as $role) {
			$role['date_created'] = new DateTime($role['date_created']);
			$role['permissions'] = [];
			$role['groups'] = [];
			$role['admins'] = [];

			if (!empty($role_relationships[$role['admin_role_id']])) {
				foreach ($role_relationships[$role['admin_role_id']] as $rel) {
					$role[$rel['element_type']][$rel['element_id']] = $rel['status'];
				}
			}

			$roles[$role['admin_role_id']] = $role;
		}

		$this->skeleton->load('roles', $roles);
	}

	/*-------------------------------
	// access data
	-------------------------------*/

	private function get_relationships($key=NULL) {
		if (!$this->skeleton->built('relationships')) $this->build_relationships();
		if (empty($key)) return $this->skeleton->get('relationships');
		else return $this->skeleton->get('relationships', $key);
	}

	public function get_permissions() {
		if (!$this->skeleton->built('permissions')) $this->build_permissions();
		return $this->skeleton->get('permissions');
	}

	public function get_groups() {
		if (!$this->skeleton->built('groups')) $this->build_groups();
		return $this->skeleton->get('groups');
	}

	public function get_roles() {
		if (!$this->skeleton->built('roles')) $this->build_roles();
		return $this->skeleton->get('roles');
	}

	/*-------------------------------
	// change data
	-------------------------------*/
}
?>
