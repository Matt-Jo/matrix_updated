<?php
class ck_view_admin_team_list extends ck_view {

	protected $url = '/admin/team-list';

	protected $page_templates = [
		'team-list' => 'page-team-list.mustache.html',
	];

	protected static $queries = [];

	public function get_meta_title() {
		return 'CK Teams';
	}

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
		$response = [];

		$__FLAG = $GLOBALS['__FLAG'];

		switch ($_REQUEST['action']) {
			case 'create-new-team':
				try {
					$team = ck_team::create(['label' => $_REQUEST['label'], 'email_address' => $_REQUEST['email_address'], 'phone_number' => $_REQUEST['phone_number'], 'local_phone_number' => $_REQUEST['local_phone_number'], 'sales_team' => $__FLAG['sales_team']?1:0, 'salesforce_key' => $_REQUEST['salesforce_key']]);
					$response['success'] = 1;
				}
				catch (CKTeamException $e) {
					$response['err'] = $e->getMessage();
				}
				catch (Exception $e) {
					$response['err'] = 'There was a problem creating this team: '.$e->getMessage();
				}

				break;
			case 'edit-team':
				try {
					$team = new ck_team($_REQUEST['team_id']);

					$team->update(['label' => $_REQUEST['label'], 'email_address' => $_REQUEST['email_address'], 'phone_number' => $_REQUEST['phone_number'], 'local_phone_number' => $_REQUEST['local_phone_number'], 'sales_team' => $__FLAG['sales_team']?1:0, 'salesforce_key' => $_REQUEST['salesforce_key']]);

					$response['success'] = 1;
				}
				catch (CKTeamException $e) {
					$response['err'] = $e->getMessage();
				}
				catch (Exception $e) {
					$response['err'] = 'There was a problem editing this team: '.$e->getMessage();
				}

				break;
			case 'deactivate-team':
				try {
					$team = new ck_team($_REQUEST['team_id']);

					$team->deactivate();

					$response['success'] = 1;
				}
				catch (CKTeamException $e) {
					$response['err'] = $e->getMessage();
				}
				catch (Exception $e) {
					$response['err'] = 'There was a problem deactivating this team: '.$e->getMessage();
				}

				break;
			case 'activate-team':
				try {
					$team = new ck_team($_REQUEST['team_id']);

					$team->activate();

					$response['success'] = 1;
				}
				catch (CKTeamException $e) {
					$response['err'] = $e->getMessage();
				}
				catch (Exception $e) {
					$response['err'] = 'There was a problem activating this team: '.$e->getMessage();
				}

				break;
			case 'add-team-member':
				try {
					$team = new ck_team($_REQUEST['team_id']);

					$team->add_member($_REQUEST['admin_id']);

					$response['success'] = 1;
				}
				catch (CKTeamException $e) {
					$response['err'] = $e->getMessage();
				}
				catch (Exception $e) {
					$response['err'] = 'There was a problem adding this team member: '.$e->getMessage();
				}

				break;
			case 'remove-team-member':
				try {
					$team = new ck_team($_REQUEST['team_id']);

					$team->remove_member($_REQUEST['admin_id']);

					$response['success'] = 1;
				}
				catch (CKTeamException $e) {
					$response['err'] = $e->getMessage();
				}
				catch (Exception $e) {
					$response['err'] = 'There was a problem adding this team member: '.$e->getMessage();
				}

				break;
			default:
				$response['err'] = 'The requested action ['.$_REQUEST['action'].'] was not recognized';
				break;
		}

		echo json_encode($response);
		exit();
	}

	private function http_response() {
		$data = $this->data();

		$admins = ck_admin::get_all_active_admins(['ck_admin', 'sort_by_name']);

		if ($teams = ck_team::get_all_teams()) {
			$data['teams'] = [];
			foreach ($teams as $team) {
				$tm = $team->get_header();
				if ($tm['sales_team']) $tm['is_sales_team'] = 1;
				if ($tm['active']) $tm['is_active'] = 1;

				$admin_ids = [];

				if ($team->has_members()) {
					$members = [];

					foreach ($team->get_members() as $member) {
						if (empty($member['admin_id']) || !$member['member']->is('active')) continue;

						$admin_ids[] = $member['admin_id'];

						$members[] = [
							'member' => $member['member']->get_name(),
							'email_address' => $member['member']->get_header('email_address'),
							'assignment_date' => $member['assignment_date']->format('m/d/Y'),
							'admin_id' => $member['admin_id'],
						];
					}

					if (!empty($members)) $tm['members'] = $members;
				}

				$tm['available_admins'] = [];

				foreach ($admins as $admin) {
					if (in_array($admin->id(), $admin_ids)) continue;
					$tm['available_admins'][] = [
						'admin_id' => $admin->id(),
						'admin' => $admin->get_header('last_name').', '.$admin->get_header('first_name'),
						'email_address' => $admin->get_header('email_address')
					];
				}

				$data['teams'][] = $tm;
			}
		}

		$this->render($this->page_templates['team-list'], $data);
		$this->flush();
	}
}
?>
