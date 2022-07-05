<?php
if (isset($_SESSION["role"])) {
	$_SESSION["role"] == "Admin" ? $conn = admin_connect() : $conn = user_connect();
} else {
	$conn = user_connect();
}

function admin_connect()
{
	$host     = 'localhost';
	$username = 'root';
	$password = '';
	$dbname = 'm151_db_tobias_odermatt';
	$conn = mysqli_connect($host, $username, $password, $dbname);
	if (!$conn) {
		die("Verbindung mit der Datenbank nicht möglich, kontaktieren Sie den Seitenadministrator.");
	}
	return $conn;
}

function user_connect()
{
	$host     = 'localhost';
	$username = 'root';
	$password = '';
	$dbname = 'm151_db_tobias_odermatt';
	$conn = mysqli_connect($host, $username, $password, $dbname);
	if (!$conn) {
		die("Verbindung mit der Datenbank nicht möglich, kontaktieren Sie den Seitenadministrator.");
	}
	return $conn;
}
?>