<?php
session_start();
session_regenerate_id();
$edit_name = "";
if (!isset($_SESSION["user"]) || $_SESSION["role"] != "admin") { //Ist die Session nicht gesetzt oder der Nutzer kein Admin wird man auf die Login Seite weitergeleitet.
  header("location:../../login");
}
require("../../lib/dbconnector.inc.php");
$site_title = "Kategorie hinzufügen.";
$alert_class_name = "alert alert-info";
$alert_message = "Hier können Sie eine neue Kategorie anlegen.";
$error = "";
$editmode = "submit"; //Wert von Submit Button, wird ein GET-Request gesendet wird zu ID geändert

function categoryExists($category, $conn)
{ //Überprüft ob eine Kategorie unter dem Namen schon existiert
  $stmt = $conn->prepare("SELECT name FROM category WHERE name = (?)");
  $stmt->bind_param("s", $category);
  $stmt->execute();
  $stmt->store_result();
  $countRows = $stmt->num_rows;
  if ($countRows >= 1) {
    return true;
  } else {
    return false;
  }
}
function existID($ID, $conn)
{ //Überprüft ob die ID einer Kategorie  existiert
  $stmt = $conn->prepare("SELECT category_id FROM category WHERE category_id = (?)");
  $stmt->bind_param("i", $ID);
  $stmt->execute();
  $stmt->store_result();
  $countRows = $stmt->num_rows;
  if ($countRows >= 1) {
    return true;
  } else {
    return false;
  }
}
function delEveryAssign($conn, $category_id)
{
  $stmt = $conn->prepare("DELETE FROM user_has_categories WHERE category_category_id = (?)");
  $stmt->bind_param("s", $category_id);
  $stmt->execute();
}

function delCategory($ID, $conn)
{ //Löscht eine Kategorie/sowie dazu gehörigen ToDos, die ID muss mitgegeben werden
  if (existID($ID, $conn)) { //Überprüft ob die ID existiert
    delEveryAssign($conn, $ID);
    $stmt = $conn->prepare("DELETE FROM category WHERE category_id = (?)");
    $stmt->bind_param("s", $ID);
    $stmt->execute();
    $stmt = $conn->prepare("DELETE FROM to_do WHERE category_category_id = (?)");
    $stmt->bind_param("s", $ID);
    $stmt->execute();
    $stmt->store_result();
    header("location: ../view_category?status=delsuccess"); //Bei Erfolg wird dies der view_category Seite mitgeteilt
  } else {
    "location: ../view_category?status=delfail";
  } //Existiert die ID nicht wird dies der view_category Seite mitgeteilt
}
function editCategory($ID, $conn)
{ //Lädt die zu bearbeitende Kategorie, ID wird mitgegeben
  if (existID($ID, $conn)) { //Überprüft ob die ID existiert.
    global $edit_name, $alert_message, $site_title;
    $sql = "SELECT name,category_id from category where category_id = '$ID'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $edit_name = $row['name'];
    $site_title = "Kategorie bearbeiten";
    $alert_message = "Sie können die Kategorie mit der ID #" . $ID . " bearbeiten";
  }
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
  require("../../lib/validate_form_server_side.inc.php");
  validateCategory();
  if (empty($error)) {
    if (isset($_POST['writemode'])) { //Dient zur unterscheidung von Kategorie erstellen und Bearbeiten Modus
      if ($_POST['writemode'] == "submit") { //Beim erstellen
        if (!(categoryExists($category, $conn))) { //Überprüft ob der Name existiert, falls ja wird dies ausgegeben.
          $stmt = $conn->prepare("INSERT INTO category (name) VALUES (?)");
          $stmt->bind_param("s", $category);
          $stmt->execute();
          $stmt->close();
          $conn->close();
          $alert_class_name = "alert alert-success";
          $alert_message = "Die Kategorie wurde erfolgreich angelegt.";
        } else $error .= "Kategorie existiert bereits.<br/>";
      } else {
        $tempID = (int)$_POST['writemode']; // Beim bearbeiten
        if (existID($tempID, $conn)) { //Überprüft ob die ID existiert.
          $stmt = $conn->prepare("UPDATE category SET name = (?) WHERE category_id = (?)");
          $stmt->bind_param("si", $category, $tempID);
          $stmt->execute();
          $stmt->close();
          $conn->close();
          $alert_class_name = "alert alert-success";
          $alert_message = "Die Kategorie wurde erfolgreich bearbeitet.";
        }
      }
    }
  } else {
    $alert_message = $error;
  }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') //Sollte eine Kategorie bearbeitet oder gelöscht werden
{
  if (isset($_GET['ID'])) {         //Sollte eine Kategorie bearbeitet werden, dann ist die Variabel ID gesetzt
    $editmode = (int)$_GET['ID']; //Wird zu INT konvertiert.
    editCategory($editmode, $conn);
  } elseif (isset($_GET['DEL'])) {  //Sollte ein Beitrag gelöscht werden, dann ist die Variabel DEL gesetzt.
    $delID = (int)$_GET['DEL']; //Um SQL Injections zu verhindern
    delCategory($delID, $conn);
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
          <form style="display: inline" action="../index.php" method="get">
            <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-home"></span> Home | Benutzerverwaltung</button>
          </form>
        </div>
      </div>

      <div class="nav navbar-nav navbar-right">
        <div class="panel-buttons">

          <form style="display: inline" action="../view_category" method="get">
            <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-inbox"></span> Kategorien ansehen</button>
          </form>

          <form style="display: inline" action="../manage_category/" method="get">
            <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-tag"> </span> Kategorie erstellen</button>
          </form>

          <form style="display: inline" action="../manage_user" method="get">
            <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-user"></span> Benutzer erstellen</button>
          </form>

          <form style="display: inline" action="../../logout" method="get">
            <button type="submit" class="btn btn-default "><span class="glyphicon glyphicon-log-out"></span> Log out</button>
          </form>

        </div>
      </div>
    </div>
  </nav>
  <div class="well well-sm">
    <h4><?php echo $site_title; ?></h4>
  </div>
  <div class="container">
    <div class="form-group">
      <div class="<?php echo $alert_class_name; ?>" role="alert"><?php echo $alert_message; ?></div>
    </div>
    <form action="index.php" method="post">
      <!-- Kategorie -->
      <div class="form-group">
        <label for="category">Kategoriename *</label>
        <input type="text" name="category" class="form-control" id="category" value="<?php echo $edit_name ?>" placeholder="Geben Sie eine Kategorie ein, maximal 25 Zeichen." maxlength="25" required>
      </div>
      <button type="submit" name="writemode" value="<?php echo $editmode; ?>" class="btn btn-info">Senden</button>
      <button type="reset" name="button" value="reset" class="btn btn-warning">Löschen</button>
    </form>

  </div>
</body>

</html>