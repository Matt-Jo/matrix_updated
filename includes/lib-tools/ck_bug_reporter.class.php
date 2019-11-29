<?php
class ck_bug_reporter extends ck_master_archetype {

	private static $template = 'partial-report-bug.mustache.html';

	public static function render() {
		$domain = $_SERVER['HTTP_HOST'];
		//$cdn = '//media.cablesandkits.com';
		$cdn = '/images';
		$static = $cdn.'/static';

		$data = ['static_files' => $static, 'postvars' => json_encode($_POST), 'sessvars' => json_encode($_SESSION)];

		if (!empty($_SESSION['bug_reported']) && $_SESSION['bug_reported'] == TRUE) {
			$data['bug_reported'] = true;
			unset($_SESSION['bug_reported']);
		}

		$tpl = new ck_template(DIR_FS_CATALOG.'includes/templates', ck_template::NONE);
		$tpl->content(DIR_FS_CATALOG.'includes/templates/'.self::$template, $data);
	}

	public static function report() {
		$youtrack = new api_youtrack();

		$__FLAG = request_flags::instance();

		$user = self::query_fetch('SELECT admin_email_address AS email_address, admin_firstname AS firstname, admin_lastname AS lastname FROM admin WHERE admin_id = :admin_id', cardinality::ROW, [':admin_id' => $_SESSION['login_id']]);

		$issue = [
			'summary' => $_REQUEST['summary'],
			'url' => $_REQUEST['url'],
			'stuck' => $__FLAG['stuck'],
			'workaround' => $__FLAG['workaround'],
			'description' => $_REQUEST['description'],
			'querystring' => $_REQUEST['querystring'],
			'postvars' => $_REQUEST['postvars'],
			'sessvars' => $_REQUEST['sessvars'],
			'user' => $user['email_address'],
			'page_source' => NULL,
			'page_source_size' => 0,
			'screenshot' => NULL,
			'screenshot_size' => 0
		];

		file_put_contents('/tmp/page-source-'.$user['email_address'].'.html', $_REQUEST['page-source']);
		$issue['page_source'] = new CurlFile('/tmp/page-source-'.$user['email_address'].'.html', 'text/html', 'page-source.html');
		//$issue['page_source_size'] = strlen($_REQUEST['page-source']);

		if ($_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
			$file_details = pathinfo($_FILES['screenshot']['name']);
			$file_path = $_FILES['screenshot']['tmp_name'];
			$file_type = strtolower($file_details['extension']);

			if (file_exists($file_path) && is_readable($file_path)) {
				$issue['screenshot'] = new CurlFile($file_path, 'image/'.$file_type, $file_details['basename']);
			}
		}
		elseif (!empty($_REQUEST['auto-screenshot'])) {
			list($header, $img) = explode(',', $_REQUEST['auto-screenshot']);
			file_put_contents('/tmp/auto-screenshot-'.$user['email_address'].'.png', base64_decode($img));
			$issue['screenshot'] = new CurlFile('/tmp/auto-screenshot-'.$user['email_address'].'.png', 'image/png', 'screenshot.png');
			//$issue['screenshot_size'] = strlen(base64_decode($img));
		}

		$issue_link = $youtrack->submit($issue);

		$body = 'Hello '.$user['firstname'].', <br><br>'.'You submitted the following bug:<br><br>'.'<b>Description</b>: '.$issue['description'].'<br>'.'<b>Summary</b>: '.$issue['summary'].'<br><br><br>Once this bug has been exterminated, or if we have any questions for you, we will let you know!<br><br>Thanks!<br>CK Dev :)<br><br><br>Dev Issue Link: <a href="'.$issue_link.'">'.$issue_link.'</a>';
        $mailer = service_locator::get_mail_service();
        $mail = $mailer->create_mail();
		$mail->set_body($body);
		$mail->set_from('webdevelopment@cablesandkits.com', 'CK Web Development');
		$mail->add_to($user['email_address'], $user['firstname'].' '.$user['lastname']);
		$mail->add_to('webdevelopment@cablesandkits.com', 'CK Web Development');
		$mail->set_subject('Matrix Bug Confirmation: '.$issue['summary']);
		$mailer->send($mail);

		$_SESSION['bug_reported'] = TRUE;

		CK\fn::redirect_and_exit($_REQUEST['return-to']);
	}
}

