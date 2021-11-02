<?php

class Work extends Thread {
	private $row;
	private $db;

	public function __construct($row) {
		$this->row = $row;
		printf("A new work was submitted with the name: %s\n", $row['url']);
		$this->db = $this->getDb();
	}

	public function run() {
		$this->callUrl($this->row);
	}

	private function getDb() {
		$db = new PDO("sqlite:".__DIR__."/mydb.db");
		return $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	private function callUrl($row) {
		$this->updateStatusAndHttpCode($row['id'], 'PROCESSING');
		if ( ! isset($row['url']) || ! $row['url'] || ! is_string($row['url']) || ! preg_match('/^http(s)?:\/\/[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(\/.*)?$/i', $row['url'])) {
			$this->updateStatusAndHttpCode($row['id'], 'ERROR');
		}

		$ch = curl_init($row['url']);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_TIMEOUT,10);
		$output = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		$this->updateStatusAndHttpCode( $row['id'], 'DONE', $httpCode);
	}

	private function updateStatusAndHttpCode($jobId, $status, $httpCode = null) {
		$sqlError = 'UPDATE called_urls SET status = :status, http_code = :httpCode  WHERE id = :id';

		$statement = $this->db->prepare($sqlError);
		$statement->execute([':status' => $status, ':id' => $jobId, ':httpCode' => $httpCode]);
	}
}