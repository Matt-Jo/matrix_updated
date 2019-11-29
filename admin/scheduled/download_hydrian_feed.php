<?php
require_once(__DIR__.'/../../includes/application_top.php');

//if (!($ftp = ftp_connect('hydrian.sharefileftp.com', 21))) throw new Exception('FTP connection to hydrian.sharefileftp.com failed.');
//if (!ftp_login($ftp, 'hydrian/jason.shinn@cablesandkits.com', '6N$mYZEFFUMuT9ad')) throw new Exception('FTP login to hydrian.sharefileftp.com with provided username/pass failed.');
//if (!ftp_chdir($ftp, 'CK Shared Folder/To CK')) throw new Exception("Changing to 'CK Shared Folder/To CK' on FTP server failed.");

//ftp_pasv($ftp, TRUE);

if (!($sftp = new phpseclib\Net\SFTP('files.hydrian.com'))) throw new Exception("SFTP connection to files.hydrian.com failed.");
if (!$sftp->login('cablesandkits', 'qBT!ZmmVC!7kc%e7FMjz')) throw new Exception("SFTP login to files.hydrian.com with provided username/pass failed.");

$filename = 'Purchases'.date('Y-m-d').'.csv';
$feed = realpath(__DIR__.'/../../feeds').'/hydrian-buy.csv';

if (is_file($feed)) unlink($feed);

//if (!ftp_get($ftp, $feed, $filename, FTP_ASCII, 0)) throw new Exception('Failed to download feed from Hydrian');

if (!$sftp->get('Daily_Data_Files/Cables_and_Kits/From_Hydrian/'.$filename, $feed)) throw new Exception('Failed to download feed from Hydrian');

$file = file_get_contents($feed);

if (empty($file)) {
	echo 'purchase feed is empty';
	exit();
}

file_put_contents($feed, preg_replace('/\000/', '', $file));

//fclose($feed);

$column_names = [
	'vendors_id',
	'stock_id',
	'distribution_center', // ignored
	'ipn', // ignored
	'qty',
	'min_buy_qty', // ignored
	'max_buy_qty', // ignored
];

$spreadsheet = new spreadsheet_import(['name' => $feed, 'tmp_name' => $feed]);

$columns = 0;
$data = [];

foreach ($spreadsheet as $idx => $row) {
	if ($idx == 0) continue; // header row

	if (empty($columns)) $columns = count($row);
	$data[$idx-1] = [];

	for ($i=0; $i<$columns; $i++) {
		$data[$idx-1][$column_names[$i]] = @$row[$i];
	}
}

$suggestion = array_filter(array_map(function($row) {
	if ($row['qty'] <= 0) return NULL;
	return $row;
}, $data));

ck_suggested_buy::create_suggestion($suggestion);

if (!is_cli() && $__FLAG['then_close']) { ?>
<script>
	window.close();
</script>
<?php } ?>
