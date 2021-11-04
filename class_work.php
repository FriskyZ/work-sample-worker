<?php

class Work extends Thread {
	private $row;

	public function __construct($row) {
		$this->row = $row;
		printf("A new work was submitted with the name: %s\n", $row['url']);
	}

	public function run() {
		$this->callUrl($this->row);
	}

	private function callUrl($row) {
		$this->updateStatusAndHttpCode($row['id'], 'PROCESSING');
		if ( ! isset($row['url']) || ! $row['url'] || ! is_string($row['url']) || ! preg_match('/^http(s)?:\/\/[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(\/.*)?$/i', $row['url'])) {
			$this->updateStatusAndHttpCode($row['id'], 'ERROR');
			return;
		}

		$ch = curl_init($row['url']);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT,10);
		$output = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		//If the returned http code is for example 500 should the status be ERROR?
		$this->updateStatusAndHttpCode( $row['id'], 'DONE', $httpCode);
	}

	private function updateStatusAndHttpCode($jobId, $status, $httpCode = null) {
		$db = new PDO("sqlite:".__DIR__."/mydb.db");
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$updateSql = 'UPDATE called_urls SET status = :status, http_code = :httpCode  WHERE id = :id';

		$statement = $db->prepare($updateSql);
		$statement->execute([':status' => $status, ':id' => $jobId, ':httpCode' => $httpCode]);
	}
}