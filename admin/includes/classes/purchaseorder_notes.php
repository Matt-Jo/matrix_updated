<?php
class PurchaseOrderNotes {
	var $oID; //order id
	var $currentUser; //id of the current user
	var $notes = array(); // array of adminnote objects
	var $editMode = false;
	var $editNoteID;

	function __construct($orderID) {
		global $login_id;
		$this->oID = $orderID;
		$this->currentUser = $login_id;
		$action = (isset($_GET['subaction']) ? $_GET['subaction'] : '');

		if ($_GET['action']=='update_order') $action='update_order';

		if (tep_not_null($action)) {
			switch ($action) {
				case 'order_note_update':
					$note = $this->getNote($_POST['id']);
					if (!$note->error) $note->updateNote();
					break;
				case 'update_order':
				case 'order_note_insert':
					if (tep_not_null($_POST['purchase_order_note_text'])) {
						$note = new PurchaseOrderNote();
						$note->saveNote();
						unset($_POST['purchase_order_note_text']);
					}
					break;
				case 'order_note_delete':
					$note = $this->getNote($_REQUEST['id']);
					if (!$note->error) $note->deleteNote();
					break;
				case 'order_note_edit':
					$note = $this->getNote($_REQUEST['id']);
					if (!$note->error) {
						$this->editMode = true;
						$this->editNoteID = $note->noteID;
					}
					break;
			}
		}
	}

	function displayAll($direct = true) {
		$this->getNotes();
		$output = '';
		$output .= '<table border="1" cellpadding="5" cellspacing="0"><tr><td class="smallText" align="center"><b>Date Added</b></td><td class="smallText" align="center"><b>Username</b></td><td class="smallText" align="center"><b>Comments</b></td><td class="smallText" align="center">&nbsp;</td></tr>';

		if (count($this->notes) > 0) {
			foreach ($this->notes as $nt) {
				if ($this->editMode && $this->editNoteID == $nt->noteID) $output .= $nt->displayEdit();
				else $output .= $nt->display();
			}

			$output .= "</table>";

			if (!$this->editMode) $output .= $this->displayInsertForm(false);
		}
		else {
			$output = '<table><tr>';
			$output .= '<td>No notes found</td>';
			$output .= '<td>&nbsp;</td>';
			$output .= '</table>';
			$output .= $this->displayInsertForm(false);
		}

		if (!empty($direct)) echo $output;
		else return $output;
	}

	function display($noteID) {
	}

	function getNotes() {
		$notes = prepared_query::fetch("select id from purchase_order_notes where purchase_order_id=:purchase_order_id and purchase_order_note_deleted=0 order by purchase_order_note_created asc", cardinality::SET, [':purchase_order_id' => $this->oID]);

		foreach ($notes as $row) {
			$this->notes[] = new PurchaseOrderNote($row['id']);
		}
	}

	function getNote($noteID) {
		return new PurchaseOrderNote($noteID);
	}

	function displayInsertForm($direct = true) {
		$output = '';
		if ($direct == true) echo $output;
		else return $output;
	}
}

class PurchaseOrderNote {
	var $noteID; //id of the note
	var $ordersID;
	var $noteText; //content of the note
	var $noteCreatorID; //id of creator
	var $noteCreateName; //name of the creator
	var $addTime; //time the note was added
	var $editTime; // time the note ws edited
	var $error = false;
	var $errorText = '';
	var $currentUser; //user id of current user;
	var $adminUser = 1;

	function __construct($noteID = '') {
		global $login_id;
		$this->currentUser = $login_id;

		if (!empty($noteID)) $this->loadNote($noteID);
	}

	function loadNote($noteID) {
		$note_data = prepared_query::fetch("Select n.*, concat(admin_firstname,' ',admin_lastname) as name from purchase_order_notes n left join admin a on n.purchase_order_note_user=a.admin_id where id=:note_id", cardinality::ROW, [':note_id' => $noteID]);

		if (is_array($note_data) and $note_data['id'] == $noteID) {
			$this->noteID = $note_data['id'];
			$this->ordersID = $note_data['purchase_order_id'];
			$this->noteText = $note_data['purchase_order_note_text'];
			$this->noteCreatorID = $note_data['purchase_order_note_user'];
			$this->noteCreatorName = $note_data['name']; // do another query or join in main query
			$this->addTime = $note_data['purchase_order_note_created'];
			$this->editTime = $note_data['purchase_order_note_modified'];
		}
		else {
			// note not found
			$this->error = true;
			$this->errorText = "Note not Found";
		}
	}

	function updateNote() {
		if (!$this->error) {
			$notez = $_POST['purchase_order_note_text'];
			$notez = nl2br($notez);
			prepared_query::execute('UPDATE purchase_order_notes SET purchase_order_note_text = :note, purchase_order_note_modified = NOW() WHERE id = :note_id', [':note' => $notez, ':note_id' => $this->noteID]);
		}
	}

	function saveNote() {
		if (!empty($_POST['purchase_order_note_text'])) {
			$note_id = prepared_query::insert('INSERT INTO purchase_order_notes (purchase_order_note_user, purchase_order_note_text, purchase_order_id, purchase_order_note_created) VALUES (:user, :text, :po_id, NOW())', [':user' => $this->currentUser, ':text' => $_POST['purchase_order_note_text'], ':po_id' => $_GET['oID']]);
			$this->loadNote($note_id);
		}
	}

	function deleteNote() {
		if (!$this->error) {
			prepared_query::execute('UPDATE purchase_order_notes SET purchase_order_note_deleted = 1, purchase_order_note_modified = NOW() WHERE id = :note_id', [':note_id' => $this->noteID]);
		}
	}

	function display() {
		$add_time = new DateTime($this->addTime);

		$output = '		<tr>'."\n" ;
		$output .= '				<td class="smallText" align="center">'.$add_time->format('m/d/Y h:i:s a').'</td>'."\n" ;
		$output .= '				<td class="smallText" >'.$this->noteCreatorName.'</td>'."\n" ;
		$output .= '				<td class="smallText" width="300">'.nl2br(stripslashes($this->noteText)).'</td>'."\n" ;

		if (($this->noteCreatorID == $this->currentUser or $this->currentUser == $this->adminUser) and isset($this->noteID)) {
			$output .= '				<td class="smallText"><a href="'.'/admin/po_editor.php?'.tep_get_all_get_params(array("action","subaction","order_note_edit","id")) .'action=edit&subaction=order_note_edit&id='.$this->noteID.'">Edit </a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="'.'/admin/po_editor.php?'.tep_get_all_get_params(array("action","subaction","order_note_edit","order_note_delete","id")) .'action=edit&subaction=order_note_delete&id='.$this->noteID.'">Delete </a></td>'."\n" ;
		}
		else {
			$output .= '				<td>&nbsp;</td>'."\n" ;
		}

		$output .= '		</tr>'."\n";

		return $output;
	}

	function displayEdit() {
		if (($this->noteCreatorID == $this->currentUser or $this->currentUser == $this->adminUser) and isset($this->noteID)) {
			$add_time = new DateTime($this->addTime);

			$output = '<tr>' ;
			$output .= '				<td class="smallText" align="center">'.tep_draw_hidden_field('id', $this->noteID).$add_time->format('m/d/Y h:i:s a').'</td>'."\n" ;
			$output .= '				<td class="smallText" >'.$this->noteCreatorName.'</td>'."\n" ;
			$output .= '				<td class="smallText" >'.tep_draw_textarea_field('purchase_order_note_text', 'soft', '60', '5', stripslashes($this->noteText)).'</td>'."\n" ;

			$output .= '				<td class="smallText">'.tep_image_submit('button_update.gif', IMAGE_UPDATE,'onclick=document.po_editor.action=\''.'/admin/po_editor.php?'.tep_get_all_get_params(array("action","subaction","order_note_edit","id")) .'action=edit&subaction=order_note_update'.'\';document.po_editor.submit()').' &nbsp;&nbsp;|&nbsp;&nbsp;'.tep_image_submit('button_cancel.gif', IMAGE_CANCEL,'onclick=document.po_editor.action=\''.'/admin/po_editor.php?'.tep_get_all_get_params(array("action","subaction","order_note_edit","id")) .'action=edit'.'\';document.po_editor.submit()').'</td>'."\n" ;

			$output .= '		</tr>'."\n";

			return $output;
		}
		else return $this->display();
	}
}

?>