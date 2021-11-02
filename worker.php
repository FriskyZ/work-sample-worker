<?php
require 'class_work.php';

$db = new PDO("sqlite:".__DIR__."/mydb.db");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = 'SELECT * FROM called_urls WHERE status = "NEW"';

$rows = $db->query($sql)->fetchAll();

$pool = new \Pool(1, Worker::class);
foreach ($rows as $row) {
	$pool->submit(new Work($row));
}
$pool->shutdown();

