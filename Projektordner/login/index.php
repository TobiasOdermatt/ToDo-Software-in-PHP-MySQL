<?php
session_start();
if (isset($_SESSION["role"])) {
	if (($_SESSION["role"]) == "admin") {
		header("location:../admin");
	} else if (($_SESSION["role"]) == "user") {
		header("location:../user");
	}
}

require("../lib/dbconnector.inc.php");

$message = '';

function login($username, $id, $role)
{
	$_SESSION["user"] = $username;
	$_SESSION["id"] = $id;
	$_SESSION["role"] = $role;
	if (($_SESSION["role"]) == "admin") {
		header("location:../admin");
	} else if (($_SESSION["role"]) == "user") {
		header("location:../user");
	}
}


// Formular wurde gesendet 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$error = '';
	//Formfelder werden validiert, wenn es zu einem Fehler kommt dann wird die Variabel $error befüllt
	require("../lib/validate_form_server_side.inc.php");
	validatelogin();

	// kein fehler
	if (empty($error)) {
		$stmt = $conn->prepare("SELECT username, password FROM user WHERE username = (?)");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$stmt->store_result();
		$countRows = $stmt->num_rows;
		if ($countRows >= 1) { //Wurde der Nutzername gefunden, wird das Password überprüft
			$sql = "SELECT password,id,role from user where username = '$username'";
			$result = $conn->query($sql);
			$row = $result->fetch_assoc();
			$conn->close();
			if (password_verify($password, $row["password"])) {
				$message .= "Sie wurden erfolgreich eingeloggt";
				login($username, $row["id"], $row["role"]);
			} else {
				$error .= "Ungültiges Password oder Nutzername";
			}
		} else {
			$error .= "Ungültiges Password oder Nutzername";
		}
	}
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Registrierung</title>

	<!-- Bootstrap -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">

	<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
	<!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.2/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>
	<nav class="navbar navbar-default">
		<div class="container-fluid">
			<div class="nav navbar-nav navbar-left"></div>
		</div>
	</nav>
	<div class="well well-sm">
		<h4>Login Seite</h4>
	</div>
	<div class="container">
		<p>
			Bitte melden Sie sich mit Benutzernamen und Passwort an.
		</p>
		<?php
		// fehlermeldung oder nachricht ausgeben
		if (!empty($message)) {
			echo "<div class=\"alert alert-success\" role=\"alert\">" . $message . "</div>";
		} else if (!empty($error)) {
			echo "<div class=\"alert alert-danger\" role=\"alert\">" . $error . "</div>";
		}
		?>
		<form action="" method="POST">
			<div class="form-group">
				<label for="username">Benutzername *</label>
				<input type="text" name="username" class="form-control" id="username" value="" placeholder="Gross- und Keinbuchstaben, min 6 Zeichen." maxlength="30" required="true" pattern="(?=.*[a-z])(?=.*[A-Z])[a-zA-Z]{6,}" title="Gross- und Keinbuchstaben, min 6 Zeichen.">
			</div>
			<!-- password -->
			<div class="form-group">
				<label for="password">Password *</label>
				<input type="password" name="password" class="form-control" id="password" placeholder="Gross- und Kleinbuchstaben, Zahlen, Sonderzeichen, min. 8 Zeichen, keine Umlaute" pattern="(?=^.{8,}$)((?=.*\d+)(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$" title="mindestens einen Gross-, einen Kleinbuchstaben, eine Zahl und ein Sonderzeichen, mindestens 8 Zeichen lang,keine Umlaute." required="true">
			</div>
			<button type="submit" name="button" value="submit" class="btn btn-info">Senden</button>
			<button type="reset" name="button" value="reset" class="btn btn-warning">Löschen</button>
		</form>
	</div>
	</div>
</body>

</html>