<?php
/**
 *
 * @version $Id$
 * @copyright 2007
 */

/**
 * Collection of notes
 */
class OrderNotes {
	public $oID; //order id
	public $currentUser; //id of the current user
	public $notes = array(); // array of adminnote objects
	public $editMode = false;
	public $editNoteID;

	/**
	* Constructor
	*
	* @access protected
	*/
	public function __construct($orderID) {
		$this->oID = $orderID;
		$this->currentUser = $_SESSION['login_id'];
		$action = isset($_GET['subaction'])?$_GET['subaction']:'';

		if ($_GET['action'] == 'update_order') $action = 'update_order';

		switch ($action) {
			case 'order_note_update':
				$note = $this->getNote($_POST['orders_note_id']);
				if (!$note->error) $note->updateNote();
				break;
			case 'update_order':
			case 'order_note_insert':
				if (!empty($_POST['orders_note_text'])) {
					$note = new OrderNote();
					$note->saveNote();
					unset($_POST['orders_note_text']);
				}
				break;
			case 'order_note_delete':
				$note = $this->getNote($_REQUEST['orders_note_id']);
				if (!$note->error) $note->deleteNote();
				break;
			case 'order_note_edit':
				$note = $this->getNote($_REQUEST['orders_note_id']);
				if (!$note->error) {
					$this->editMode = true;
					$this->editNoteID = $note->noteID;
				}
			default:
				break;
		} // switch
	}

	/**
	* *prints the admin notes
	* calls display for all notes in array
	*/
	public function displayAll($direct=true) {
		$this->getNotes();

		$output = '<table border="1" cellpadding="5" cellspacing="0"><tr><td class="smallText" align="center"><b>Date Added</b></td><td class="smallText" align="center"><b>Username</b></td><td class="smallText" align="center"><b>Comments</b></td><td class="smallText"><strong>Picking</strong></td><td class="smallText" align="center">&nbsp;</td></tr>';

		if (count($this->notes) > 0) {
			foreach ($this->notes as $nt) {
				if ($this->editMode && $this->editNoteID == $nt->noteID) {
					$output .= $nt->displayEdit();
				}
				else {
					$output .= $nt->display();
				}
			}
			$output .= "</table>";

			if (!$this->editMode) $output .= $this->displayInsertForm(false);
		}
		else {
			$output = '<table><tr><td>No notes found</td><td>&nbsp;</td></table>';
			$output .= $this->displayInsertForm(false);
		}

		if (!empty($direct)) echo $output;
		else return $output;
	}

	public function display($noteID) {}

	// populates an array of adminnote objects
	public function getNotes() {
		$order_note = prepared_query::fetch('SELECT order_notes FROM orders WHERE orders_id = ?', cardinality::SINGLE, [$this->oID]);

		if (!empty($order_note)) {
			$tempnote = new orderNote();
			$tempnote->noteText = $order_note;
			$this->notes[] = $tempnote;
		}

		$notes = prepared_query::fetch('SELECT orders_note_id FROM orders_notes WHERE orders_id = ? AND orders_note_deleted = 0 ORDER BY orders_note_created ASC', cardinality::SET, [$this->oID]);

		foreach ($notes as $note) {
			$this->notes[] = new OrderNote($note['orders_note_id']);
		}
	}

	/**
	* returns a single note object by id
	*/
	public function getNote($noteID) {
		return new OrderNote($noteID);
	}

	public function displayInsertForm($direct = true) {
		$output = '<br><span class="smallText">Add Comment</span><br>'.tep_draw_textarea_field('orders_note_text', 'soft', '60', '5','','',false);
		$output .= '<br><input type="checkbox" name="shipping_notice"> Alert Shipping on Pick List';
		if ($direct == true) echo $output;
		else return $output;
	}
}

/**
 * single note
 */
class OrderNote {
	public $noteID; //id of the note
	public $ordersID;
	public $noteText; //content of the note
	public $noteCreatorID; //id of creator
	public $noteCreateName; //name of the creator
	public $addTime; //time the note was added
	public $editTime; // time the note ws edited
	public $shipping_notice; // whether or not this is a shipping note
	public $error = false;
	public $errorText = '';
	public $currentUser; //user id of current user;
	public $adminUser = 1;

	/**
	 * Constructor
	 *
	 * @access protected
	 */
	public function __construct($noteID='') {
		$this->currentUser = $_SESSION['login_id'];
		if (!empty($noteID)) $this->loadNote($noteID);
	}

	public function loadNote($noteID) {
		$note_data = prepared_query::fetch('SELECT n.*, CONCAT(admin_firstname, \' \', admin_lastname) as name FROM orders_notes n LEFT JOIN admin a ON n.orders_note_user = a.admin_id WHERE orders_note_id = ?', cardinality::ROW, [$noteID]);
		if (!empty($note_data)) {
			$this->noteID = $note_data['orders_note_id'];
			$this->ordersID = $note_data['orders_id'];
			$this->noteText = $note_data['orders_note_text'];
			$this->noteCreatorID = $note_data['orders_note_user'];
			$this->noteCreatorName = $note_data['name']; // do another query or join in main query
			$this->addTime = $note_data['orders_note_created'];
			$this->editTime = $note_data['orders_note_modified'];
			$this->shipping_notice = CK\fn::check_flag($note_data['shipping_notice']);
		}
		else {
			// note not found
			$this->error = true;
			$this->errorText = "Note not Found";
		}
	}

	public function updateNote() {
		if (!$this->error) {
			$notez = $_POST['orders_note_text'];
			$notez = nl2br($notez);
			prepared_query::execute('UPDATE orders_notes SET orders_note_text = :orders_note_text, orders_note_modified = NOW(), shipping_notice = :shipping_notice WHERE orders_note_id = :orders_note_id', [':orders_note_text' => $notez, ':shipping_notice' => CK\fn::check_flag(@$_POST['shipping_notice'])?1:0, ':orders_note_id' => $this->noteID]);
		}
	}

	public function saveNote() {
		if (!empty($_POST['orders_note_text'])) {
			$id = prepared_query::insert('INSERT INTO orders_notes (orders_note_user, orders_note_text, orders_id, orders_note_created, shipping_notice) VALUES (:orders_note_user, :orders_note_text, :orders_id, NOW(), :shipping_notice)', [':orders_note_user' => $this->currentUser, ':orders_note_text' => $_POST['orders_note_text'], ':orders_id' => $_GET['oID'], ':shipping_notice' => CK\fn::check_flag(@$_POST['shipping_notice'])?1:0]);
			$this->loadNote($id);
		}
	}

	public function deleteNote() {
		if (!$this->error) {
			prepared_query::execute('UPDATE orders_notes SET orders_note_deleted = 1, orders_note_modified = NOW() WHERE orders_note_id = :orders_note_id', [':orders_note_id' => $this->noteID]);
		}
	}

	public function display() {
		$add_time = new DateTime($this->addTime);

		$output = '<tr><td class="smallText" align="center">'.$add_time->format('m/d/Y h:i:s a').'</td><td class="smallText" >'.$this->noteCreatorName.'</td><td class="smallText" width="300">'.nl2br(stripslashes($this->noteText)).'</td><td class="smallText" style="text-align:center;">'.($this->shipping_notice?'ALERT':'').'</td>';
		if (($this->noteCreatorID == $this->currentUser || $this->currentUser == $this->adminUser) && isset($this->noteID)) {

			$output .= '<td class="smallText"><a href="/admin/orders_new.php?'.tep_get_all_get_params(array("action","subaction","order_note_edit","orders_note_id")).'action=edit&subaction=order_note_edit&orders_note_id='.$this->noteID.'">Edit </a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="/admin/orders_new.php?'.tep_get_all_get_params(array("action","subaction","order_note_edit","order_note_delete","orders_note_id")).'action=edit&subaction=order_note_delete&orders_note_id='.$this->noteID.'">Delete </a></td>';

		}
		else {
			$output .= '<td>&nbsp;</td>';
		}
		$output .= '</tr>';

		return $output;
	}

	public function displayEdit() {
		if (($this->noteCreatorID == $this->currentUser || $this->currentUser == $this->adminUser) && isset($this->noteID)) {
			$add_time = new DateTime($this->addTime);

			$output = '<tr><td class="smallText" align="center">'.tep_draw_hidden_field('orders_note_id', $this->noteID).$add_time->format('m/d/Y h:i:s a').'</td><td class="smallText" >'.$this->noteCreatorName.'</td><td class="smallText" >'.tep_draw_textarea_field('orders_note_text', 'soft', '60', '5', stripslashes($this->noteText)).'</td><td class="smallText" style="text-align:center;"><input type="checkbox" name="shipping_notice" '.($this->shipping_notice?'checked':'').'></td><td class="smallText">'.tep_image_submit('button_update.gif', IMAGE_UPDATE,'onclick=document.order_status.action=\''. '/admin/orders_new.php?'.tep_get_all_get_params(array("action","subaction","order_note_edit","orders_note_id")).'action=edit&subaction=order_note_update'.'\';document.order_status.submit()').' &nbsp;&nbsp;|&nbsp;&nbsp;'.tep_image_submit('button_cancel.gif', IMAGE_CANCEL,'onclick=document.order_status.action=\''.'/admin/orders_new.php?'.tep_get_all_get_params(array("action","subaction","order_note_edit","orders_note_id")).'action=edit'.'\';document.order_status.submit()').'</td></tr>';

			return $output;
		}
		else {
			return $this->display();
		}
	}
}
?>
