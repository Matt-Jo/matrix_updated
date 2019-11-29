<?php
require('includes/application_top.php');

$error = false;
if (isset($_GET['action']) && ($_GET['action'] == 'send')) {
	$name = $_POST['name'];
	$email_address = $_POST['email'];
	$enquiry = $_POST['enquiry'];

    $mailer = service_locator::get_mail_service();

	try {
        $mail = $mailer->create_mail()
            ->set_subject('Inquiry from CablesAndKits.com')
            ->add_to($_SESSION['cart']->get_contact_email(), 'CablesAndKits.com Sales Team')
            ->set_from($email_address, $name)
            ->set_body_text($enquiry);
        $mailer->send($mail);
		CK\fn::redirect_and_exit('/custserv.php?action=success');
    } catch(mail_service_exception $e) {
		$error = true;
		$messageStack->add('contact', 'Your E-Mail Address does not appear to be valid - please make any necessary corrections.');
	}
}

$faqdesk_entries = prepared_query::fetch('SELECT COUNT(fc.categories_id) FROM faqdesk_categories fc JOIN faqdesk_categories_description fcd ON fc.categories_id = fcd.categories_id AND fcd.language_id = :languages_id WHERE fc.catagory_status = 1 AND fc.parent_id = 0 ORDER BY sort_order, fcd.categories_name', cardinality::SINGLE, [':languages_id' => $_SESSION['languages_id']]);

if ($faqdesk_entries > 0) {
	function FAQDesk_box_has_category_subcategories($category_id) {
		$count = prepared_query::fetch('SELECT COUNT(categories_id) FROM faqdesk_categories WHERE parent_id = :category_id', cardinality::SINGLE, [':category_id' => $category_id]);
		if ($count > 0) return TRUE;
		else return FALSE;
	}

	function FAQDesk_show_category($counter) {
		// -------------------------------------------------------------------------------------------------------------------------------------------------------------
		global $foo_faqdesk, $categories_faqdesk_string, $id_faq;

		if ( ($id_faq) && (in_array($counter, $id_faq)) ) $categories_faqdesk_string .= '<b>';

		// display category name
		$categories_faqdesk_string .= '<b>'.$foo_faqdesk[$counter]['name'].'</b>';

		if ( ($id_faq) && (in_array($counter, $id_faq)) ) $categories_faqdesk_string .= '</b>';

		if (FAQDesk_box_has_category_subcategories($counter)) $categories_faqdesk_string .= '-&gt;';

		if ($sub_faqs = prepared_query::fetch('SELECT fd.faqdesk_id, fd.faqdesk_question FROM faqdesk_to_categories ftc JOIN faqdesk_description fd ON ftc.faqdesk_id = fd.faqdesk_id JOIN faqdesk f ON fd.faqdesk_id = f.faqdesk_id WHERE ftc.categories_id = :category_id AND f.faqdesk_status = 1 ORDER BY fd.faqdesk_extra_viewed DESC', cardinality::SET, [':category_id' => $counter])) {
			$categories_faqdesk_string .= '<br>';
			foreach ($sub_faqs as $sub_faq) {
				$categories_faqdesk_string .= '- <a href="/faqdesk_info.php?faqPath='.$counter.'&faqdesk_id='.$sub_faq['faqdesk_id'].'">'.$sub_faq['faqdesk_question'].'</a><br>';
			}
		}

		$categories_faqdesk_string .= '<br>';

		if (!empty($foo_faqdesk[$counter]['next_id'])) FAQDesk_show_category($foo_faqdesk[$counter]['next_id']);
	}

	$categories_faqdesk_string = '';

	$categories_faqdesks = prepared_query::fetch('SELECT fc.categories_id, fcd.categories_name, fc.parent_id FROM faqdesk_categories fc JOIN faqdesk_categories_description fcd ON fc.categories_id = fcd.categories_id AND fcd.language_id = :language_id WHERE fc.catagory_status = 1 AND fc.parent_id = 0 ORDER BY sort_order, fcd.categories_name', cardinality::SET, [':language_id' => $_SESSION['languages_id']]);

	foreach ($categories_faqdesks as $categories_faqdesk) {
		$foo_faqdesk[$categories_faqdesk['categories_id']] = array(
			'name' => $categories_faqdesk['categories_name'],
			'parent' => $categories_faqdesk['parent_id'],
			'level' => 0,
			'path' => $categories_faqdesk['categories_id'],
			'next_id' => false
		);

		if (isset($prev_id)) {
			$foo_faqdesk[$prev_id]['next_id'] = $categories_faqdesk['categories_id'];
		}

		$prev_id = $categories_faqdesk['categories_id'];

		if (!isset($counter)) {
			$counter = $categories_faqdesk['categories_id'];
		}
	}

	$new_path = '';
	$id_faq = !empty($faqPath)?explode('_', $faqPath):array();
	foreach($id_faq as $key => $value) {
		unset($prev_id);
		unset($first_id);

		if ($categories_faqdesks = prepared_query::fetch('SELECT fc.categories_id, fcd.categories_name, fc.parent_id FROM faqdesk_categories fc JOIN faqdesk_categories_description fcd ON fc.categories_id = fcd.categories_id AND fcd.language_id = :language_id WHERE fc.catagory_status = 1 AND fc.parent_id = :category_id ORDER BY sort_order, fcd.categories_name', cardinality::SET, [':category_id' => $value, ':language_id' => $_SESSION['languages_id']])) {
			$new_path .= $value;
			foreach ($categories_faqdesks as $row) {
				$foo_faqdesk[$row['categories_id']] = array(
					'name' => $row['categories_name'],
					'parent' => $row['parent_id'],
					'level' => $key+1,
					'path' => $new_path.'_'.$row['categories_id'],
					'next_id' => false
				);

				if (isset($prev_id)) $foo_faqdesk[$prev_id]['next_id'] = $row['categories_id'];

				$prev_id = $row['categories_id'];

				if (!isset($first_id)) $first_id = $row['categories_id'];

				$last_id = $row['categories_id'];
			}
			$foo_faqdesk[$last_id]['next_id'] = $foo_faqdesk[$value]['next_id'];
			$foo_faqdesk[$value]['next_id'] = $first_id;
			$new_path .= '_';
		}
		else break;
	}
}

FAQDesk_show_category($counter);

$breadcrumb->add('Customer Service', '/custserv.php');

$content = 'custserv';

require('templates/Pixame_v1/main_page.tpl.php');
?>