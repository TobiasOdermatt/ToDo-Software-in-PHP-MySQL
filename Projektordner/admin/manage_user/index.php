<?php
session_start();
session_regenerate_id();
if (!isset($_SESSION["user"]) || $_SESSION["role"] != "admin") { //Ist die Session nicht gesetzt oder der Nutzer kein Admin wird man auf die Login Seite weitergeleitet.
  header("location:../../login");
}
require("../../lib/dbconnector.inc.php"); //Datenbank
$edit_firstname = ""; //Variabeln die befüllt werden, falls ein Nutzer bearbeitet wird
$edit_lastname = ""; //Variabeln die befüllt werden, falls ein Nutzer bearbeitet wird
$edit_username = ""; //Variabeln die befüllt werden, falls ein Nutzer bearbeitet wird
$error = "";
$editmode = "submit"; //Wert von Submit Button, bei einem GET-Request wird er zu der bearbeitenden ID geändert
$site_title = "Nutzer hinzufügen."; //Standart Seitentitel
$alert_class_name = "alert alert-info";
$alert_message = "Hier können Sie ein neuen Nutzer anlegen."; //Standart meldung falls Nutzer angelegt wird.

function delUserData($conn, $user_id)
{ //Löscht jede Zuweisung sowie ToDo die dem Nutzer angehören.
  $stmt = $conn->prepare("DELETE FROM user_has_categories WHERE user_ID = (?)");
  $stmt->bind_param("s", $user_id);
  $stmt->execute();
  $stmt = $conn->prepare("DELETE FROM to_do WHERE user_ID = (?)");
  $stmt->bind_param("s", $user_id);
  $stmt->execute();
}

function delUSER($user_id, $conn)
{ //Löscht einen User wenn die ID mitgegeben wurd
  if (existID($user_id, $conn)) { //Überprüft ob die ID existiert
    delUserData($conn, $user_id); //Nutzerdaten löschen
    $stmt = $conn->prepare("DELETE FROM user WHERE ID = (?)");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->store_result();
    header("location: ../index.php?status=delsuccess"); //War die Löschung Erfolgreich wird dies der Startseite mitgeteilt
  } else {
    header("location: ../index.php?status=delfail");
  } //War die Löschung nicht Erfolgreich wird dies der Startseite mitgeteilt
}
function editUSER($user_id, $conn)
{ //Lädt den Editmode | Werte des Nutzers werden geladen und der Seitentext ändert sich
  if (existID($user_id, $conn)) {
    global $edit_firstname, $edit_lastname, $edit_username, $alert_message, $site_title;
    $sql = "SELECT firstname,lastname,username from user where ID = '$user_id'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $edit_firstname = $row['firstname'];
    $edit_lastname = $row['lastname'];
    $edit_username = $row['username'];
    $site_title = "Benutzer bearbeiten";
    $alert_message = "Sie können den Nutzer mit der ID #" . $user_id . " bearbeiten";
  }
}

function existID($user_id, $conn)
{ //Überprüft ob eine User ID in der Datenbank existiert returnt true oder false
  global $error;
  if ($user_id == 1) {
    $error .= "Der Administrator kann nicht gelöscht oder bearbeitet werden.";
    return false;
  }
  $stmt = $conn->prepare("SELECT ID FROM user WHERE ID = (?)");
  $stmt->bind_param("s", $user_id);
  $stmt->execute();
  $stmt->store_result();
  $countRows = $stmt->num_rows;
  if ($countRows >= 1) {
    return true;
  } else {
    return false;
  };
}

function usernameExists($username, $conn)
{ //Überprüft ob ein Nutzername in der Datenbank existiert returnt true oder false     
  $stmt = $conn->prepare("SELECT username FROM user WHERE username = (?)");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->store_result();
  $countRows = $stmt->num_rows;
  if ($countRows >= 1) {
    return true;
  } else {
    return  false;
  }
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
  require("../../lib/validate_form_server_side.inc.php");
  validateUser();

  // wenn kein Fehler vorhanden ist, schreiben der Daten in die Datenbank
  if (empty($error)) {
    if (isset($_POST['writemode'])) { //Dient zur unterscheidung von Nutzer erstellen und Bearbeiten Modus
      if ($_POST['writemode'] == "submit") {  //Beim Erstellen
        if (!(usernameExists($username, $conn))) { //Überprüft ob der Nutzername schon existiert.
          $stmt = $conn->prepare("INSERT INTO user (firstname, lastname, password, username) VALUES (?, ?, ?, ?)");
          $stmt->bind_param("ssss", $firstname, $lastname, $password, $username);
          $stmt->execute();
          $stmt->close();
          $conn->close();
          $alert_class_name = "alert alert-success";
          $alert_message = "Der Nutzer wurde erfolgreich angelegt.";
        } else
          $error .= "Nutzername existiert bereits.<br/>";
      } else {
        $tempID = (int)$_POST['writemode'];
        if (existID($tempID, $conn)) {
          $stmt = $conn->prepare("UPDATE user SET firstname = (?), lastname = (?),username = (?), password = (?) WHERE ID = (?)");
          $stmt->bind_param("ssssi", $firstname, $lastname, $username, $password, $tempID);
          $stmt->execute();
          $stmt->close();
          $conn->close();
          $alert_class_name = "alert alert-success";
          $alert_message = "Der Nutzer wurde erfolgreich bearbeitet.";
        }
      }
    }
  }
}



if ($_SERVER['REQUEST_METHOD'] == 'GET') //Sollte ein Benutzer bearbeitet oder gelöscht werden
{
  if (isset($_GET['ID'])) {         //Sollte ein Benutzer bearbeitet werden, dann ist die Variabel ID gesetzt
    $editmode = (int)$_GET['ID']; //Wird zu INT konvertiert damit SQL-Injections verhindert werden können.
    editUSER($editmode, $conn);
  } elseif (isset($_GET['DEL'])) {  //Sollte ein Beitrag gelöscht werden, dann ist die Variabel DEL gesetzt.
    delUSER($_GET['DEL'], $conn);
  }
}  //Funktion für die Löschung wird gestartet, die Variabel DEL wird mitgegeben.
if (!(empty($error))) {
  $alert_class_name = "alert alert-danger";
  $alert_message = $error;
} //Fehler werden ausgeben

?>
<!DOCTYPE html>
<html lang="de">

<head>
  <title>Startseite</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="../../CSS/stylesheet.css">
  <link rel="icon" type="image/x-icon" href="../../img/favicon.png">
</head>

<body>
  <nav class="navbar navbar-default">
    <div class="container-fluid">
      <div class="nav navbar-nav navbar-left">
        <div class="panel-buttons">
          <!-- Home -->
          <form style="display: inline" action="../index.php" method="get">
            <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-home"></span> Home | Benutzerverwaltung</button>
          </form>
        </div>
      </div>

      <div class="nav navbar-nav navbar-right">
        <div class="panel-buttons">
          <!-- View User -->
          <form style="display: inline" action="../view_category" method="get">
            <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-inbox"></span> Kategorien ansehen</button>
          </form>
          <!-- Manage Category -->
          <form style="display: inline" action="../manage_category" method="get">
            <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-tag"> </span> Kategorie erstellen</button>
          </form>
          <!-- Manage User -->
          <form style="display: inline" action="../manage_user/" method="get">
            <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-user"></span> Benutzer erstellen</button>
          </form>

          <!-- Logout Button -->
          <form style="display: inline" action="../../logout" method="get">
            <button type="submit" class="btn btn-default "><span class="glyphicon glyphicon-log-out"></span> Log out</button>
          </form>

        </div>
      </div>
    </div>
  </nav>
  <!-- Seitentitel -->
  <div class="well well-sm">
    <h4><?php echo $site_title; ?></h4>
  </div>

  <div class="container">
    <!-- Meldungen / Fehlermeldungen -->
    <div class="form-group">
      <div class="<?php echo $alert_class_name; ?>" role="alert"><?php echo $alert_message; ?></div>
    </div>
    <form action="index.php" method="post">
      <!-- vorname -->
      <div class="form-group">
        <label for="firstname">Vorname *</label>
        <input type="text" name="firstname" class="form-control" id="firstname" value="<?php echo $edit_firstname ?>" placeholder="Geben Sie Ihren Vornamen an." maxlength="100" required>
      </div>
      <!-- nachname -->
      <div class="form-group">
        <label for="lastname">Nachname *</label>
        <input type="text" name="lastname" class="form-control" id="lastname" value="<?php echo $edit_lastname ?>" placeholder="Geben Sie Ihren Nachnamen an" maxlength="100" required>
      </div>
      <!-- benutzername -->
      <div class="form-group">
        <label for="username">Benutzername *</label>
        <input type="text" name="username" class="form-control" id="username" value="<?php echo $edit_username ?>" placeholder="Gross- und Keinbuchstaben, min 6 Zeichen." maxlength="30" required pattern="(?=.*[a-z])(?=.*[A-Z])[a-zA-Z]{6,}" title="Gross- und Keinbuchstaben, min 6 Zeichen.">
      </div>
      <!-- password -->
      <div class="form-group">
        <label for="password">Password *</label>
        <input type="password" name="password" class="form-control" id="password" placeholder="Gross- und Kleinbuchstaben, Zahlen, Sonderzeichen, min. 8 Zeichen, keine Umlaute" maxlength="255" pattern="(?=^.{8,}$)((?=.*\d+)(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$" title="mindestens einen Gross-, einen Kleinbuchstaben, eine Zahl und ein Sonderzeichen, mindestens 8 Zeichen lang,keine Umlaute." required>
      </div>

      <!-- Senden und Löschen buttons -->
      <button type="submit" name="writemode" value="<?php echo $editmode; ?>" class="btn btn-info">Senden</button>
      <button type="reset" name="button" value="reset" class="btn btn-warning">Löschen</button>
    </form>

  </div>
</body>

</html>