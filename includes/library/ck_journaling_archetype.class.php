<?php
abstract class ck_journaling_archetype extends ck_model_archetype {
	const JOURNAL_CREDIT = 'JOURNAL_CREDIT'; // to credit - give
	const JOURNAL_DEBIT = 'JOURNAL_DEBIT'; // to debit - take

	private static $calendar_date;
	private static $accounting_date;

	private static $default_format = 'c'; // ISO 8601 formatted date

	public static function is_valid_entry_type($journal_entry_type, $halt=TRUE) {
		if (in_array($journal_entry_type, [self::JOURNAL_CREDIT, self::JOURNAL_DEBIT])) return TRUE;

		if ($halt) throw new CKJournalingArchetypeException('Journal Entry Type ['.$journal_entry_type.'] is not valid.');

		return FALSE;
	}

	public static function set_accounting_date(DateTime $accounting_date) {
		self::$accounting_date = $accounting_date;
	}

	public static function get_accounting_date($format=NULL) {
		// for now, if it's not set, set it to today.  We may change this at some point in the future once we explicitly allow accounting to set the accounting date
		if (empty(self::$accounting_date)) self::set_accounting_date(self::get_calendar_date());
		if (!empty($format)) {
			if ($format === TRUE) return self::$accounting_date->format(self::$default_format);
			else return self::$accounting_date->format($format);
		}
		else return self::$accounting_date;
	}

	public static function get_calendar_date($format=NULL) {
		if (empty(self::$calendar_date)) self::$calendar_date = new DateTime;
		if (!empty($format)) {
			if ($format === TRUE) return self::$calendar_date->format(self::$default_format);
			else return self::$calendar_date->format($format);
		}
		else return self::$calendar_date;
	}

	public static function get_month_dates(DateTime $date) {
		$result = ['start' => NULL, 'end' => NULL];

		$date->modify('first day of this month');
		$result['start'] = clone $date;

		$date->modify('last day of this month');
		$result['end'] = clone $date;

		return $result;
	}

	public static function archive_journals(DateTime $date_1, DateTime $date_2) {
		$begin_date = min($date_1, $date_2);
		$end_date = max($date_1, $date_2);

		$savepoint_id = self::transaction_begin();

		try {
			foreach (static::$archive_query_queue as $qry) {
				self::execute($qry, [':begin_date' => $begin_date->format('Y-m-d'), ':end_date' => $end_date->format('Y-m-d'), ':ledger_date' => self::get_accounting_date(TRUE)]);
			}

			self::transaction_commit($savepoint_id);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint_id);
			throw new CKJournalingArchetypeException('Failed archiving journals for ['.get_called_class().']: '.$e->getMessage());
		}		
	}

	public static function record_daily_ledger(DateTime $action_date) {
		$savepoint = self::transaction_begin();

		try {
			$one_day = new DateInterval('P1D');

			$date = self::fetch(static::$daily_ledger['start_date'], []);
			$date = self::DateTime($start_date);
			$date->add($one_day);

			self::execute(static::$daily_ledger['record_ledger'], [':action_date' => $action_date->format('c'), ':debit' => self::JOURNAL_DEBIT, ':credit' => self::JOURNAL_CREDIT]);

			while ($action_date > $date) {
				$previous = clone $date;
				$previous->sub($one_day);
				self::execute(static::$daily_ledger['catch_up_ledger'], [':previous_date' => $previous->format('c'), ':action_date' => $date->format('c'), ':debit' => self::JOURNAL_DEBIT, ':credit' => self::JOURNAL_CREDIT]);
				$date->add($one_day);
			}

			self::transaction_commit($savepoint_id);
		}
		catch (Exception $e) {
			self::transaction_rollback($savepoint_id);
			throw new CKJournalingArchetypeException('Failed creating daily ledger for ['.get_called_class().']: '.$e->getMessage());
		}
	}
}

class CKJournalingArchetypeException extends CKMasterArchetypeException {
}
?>
