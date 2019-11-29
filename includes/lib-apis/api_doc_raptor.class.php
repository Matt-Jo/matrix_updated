<?php
class api_doc_raptor extends ck_master_api {

	private static $key = 'afTHUGmrBR52EdUuqTxs';
	private $docraptor;

	public function __construct() {
		$configuration = DocRaptor\Configuration::getDefaultConfiguration();
		$configuration->setUsername(self::$key);
		$this->docraptor = new DocRaptor\DocApi();
		$this->doc = new DocRaptor\Doc();
		$this->doc->setTest(false);
	}

	public function create_pdf ($content, $file_name) {

		try {
			$this->doc->setDocumentContent($content);

			$this->doc->setName($file_name);
			$this->doc->setDocumentType("pdf");

			return $this->docraptor->createDoc($this->doc);
		}
		catch (DocRaptor\ApiException $exception) {
			echo $exception . "\n";
			echo $exception->getMessage() . "\n";
			echo $exception->getCode() . "\n";
			echo $exception->getResponseBody() . "\n";
		}

	}

	public function createAsyncPdf($content, $file_name) {
		try {
			$this->doc->setTest(false);
			$this->doc->setDocumentContent($content);
			$this->doc->setName($file_name);
			$this->doc->setDocumentType("pdf");
			$create_response = $this->docraptor->createAsyncDoc($this->doc);
			$done = false;

			while (!$done) {
				$status_response = $this->docraptor->getAsyncDocStatus($create_response->getStatusId());
				switch ($status_response->getStatus()) {
					case "completed":
						$doc_response = $this->docraptor->getAsyncDoc($status_response->getDownloadId());
						$done = true;
						break;
					case "failed":
						echo "FALIED\n";
						echo $status_response;
						$done = true;
						break;
					default:
						sleep(1);
				}
			}

			return $doc_response;
		}
		catch (DocRaptor\ApiException $exception) {
			echo $exception . "\n";
			echo $exception->getMessage() . "\n";
			echo $exception->getCode() . "\n";
			echo $exception->getResponseBody() . "\n";
		}
	}


}

class CKDocRaptorApiException extends CKApiException {
}
?>