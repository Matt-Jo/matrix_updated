<?php
require(__DIR__.'/../includes/application_top.php');

$output = shell_exec('git status');
$lines = explode("\n", $output);

$notices = [];
$state = 0;

var_dump($lines);

$linecount = count($lines);

for ($i=0; $i<$linecount; $i++) {
	$line = $lines[$i]; // just easier

	if ($line == '') continue;

	if (preg_match('#On branch ([a-zA-Z0-9/_-]+)#i', $line, $matches)) {
		$state = 1;
		if ($matches[1] != 'master') $notices['branch'] = 'Branch is '.$matches[1].', not master.';
		continue;
	}
	elseif (preg_match('/Changes to be committed:/i', $line)) {
		$state = 2;
		$i += 2;
		continue;
	}
	elseif (preg_match('/Changes not staged for commit:/i', $line)) {
		$state = 3;
		$i += 3;
		continue;
	}
	elseif (preg_match('/Untracked files:/i', $line)) {
		$state = 4;
		$i += 2;
		continue;
	}
	elseif (preg_match("/It took [0-9.]+ seconds to enumerate untracked files\. 'status -uno'/i", $line)) {
		$i += 2;
		continue;
	}
	elseif (preg_match('/nothing added to commit but untracked files present \(use "git add" to track\)/i', $line)) {
		continue;
	}
	elseif (preg_match('#no changes added to commit \(use "git add" and/or "git commit -a"\)#', $line)) {
		continue;
	}

	if ($state == 2) {
		if (empty($notices['staged'])) $notices['staged'] = [];
		$notices['staged'][] = trim($line);
	}
	elseif ($state == 3) {
		if (empty($notices['changed'])) $notices['changed'] = [];
		$notices['changed'][] = trim($line);
	}
	elseif ($state == 4) {
		if (in_array(trim($line), ['config/', 'images/.htaccess', 'includes/.htaccess'])) continue;
		if (empty($notices['new'])) $notices['new'] = [];
		$notices['new'][] = trim($line);
	}
}

if (!empty($notices)) {
    $mailer = service_locator::get_mail_service();
    $mail = $mail->create_mail()
        ->set_subject('Unexpected file changes in production')
        ->set_from('webmaster@cablesandkits.com')
        ->add_to('webmaster@cablesandkits.com')
        ->add_to('jason.shinn@cablesandkits.com');

    $htmlBody = 'Git found the following unexpected changes to the production repository:<br><br>';

	foreach ($notices as $type => $notice_group) {
		switch ($type) {
			case 'branch':
                $htmlBody .= '<hr>'.$notice_group.'<br>';
				break;
			case 'staged':
                $htmlBody .= '<hr>The following files appear to be improperly staged to commit:<br>';
                $htmlBody .= implode('<br>', $notice_group);
                $htmlBody .= '<br><br>';
				break;
			case 'changed':
                $htmlBody .= '<hr>The following files appear to be improperly changed:<br>';
                $htmlBody .= implode('<br>', $notice_group);
                $htmlBody .= '<br><br>';
				break;
			case 'new':
                $htmlBody .= '<hr>The following files appear to have been improperly created:<br>';
                $htmlBody .= implode('<br>', $notice_group);
                $htmlBody .= '<br><br>';
				break;
            default:
                $htmlBody .= '<hr>Unknown change type: '.var_export($notice_group, TRUE).'<br>';
				break;
		}
    }
    
    $mailer->send($mail);
	echo 'Email Sent';
}

