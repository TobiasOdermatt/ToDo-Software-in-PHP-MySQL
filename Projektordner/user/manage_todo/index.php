<?php
session_start();
session_regenerate_id();
//Ist die Session nicht gesetzt, oder der ToDo kein User ist, wird man auf die Login Seite weitergeleitet.
if (!isset($_SESSION["user"]) || $_SESSION["role"] != "user") {
  header("location:../../login");
}
require("../../lib/dbconnector.inc.php"); //Datenbank
$edit_priority = ""; //Variabeln die befüllt werden, falls ein ToDo bearbeitet wird
$edit_title = ""; //Variabeln die befüllt werden, falls ein ToDo bearbeitet wird
$edit_text = ""; //Variabeln die befüllt werden, falls ein ToDo bearbeitet wird
$edit_finishDate = ""; //Variabeln die befüllt werden, falls ein ToDo bearbeitet wird
$edit_archievedDate = ""; //Variabeln die befüllt werden, falls ein ToDo bearbeitet wird
$edit_category_ID = ""; //Variabeln die befüllt werden, falls ein ToDo bearbeitet wird
$edit_status = "0"; //Variabeln die befüllt werden, falls ein ToDo bearbeitet wird, Standart Status wert
$edit_date = ""; //Variabeln die befüllt werden,falls ein ToDo bearbeitet wird
$error = "";
$editmode = "submit"; //Wert von Submit Button, bei einem GET-Request wird er zu der bearbeitenden ID geändert
$site_title = "ToDo hinzufügen."; //Standart Seitentitel
$alert_class_name = "alert alert-info";
$alert_message = "Hier können Sie ein neuen ToDo anlegen."; //Standart meldung falls ToDo angelegt wird.
$user_ID = $_SESSION["id"];

//Löscht eine ToDo wenn die ID mitgegeben wurd
function delToDo($ToDo_ID, $conn)
{
  $stmt = $conn->prepare("DELETE FROM to_do WHERE ID = (?)");
  $stmt->bind_param("s", $ToDo_ID);
  $stmt->execute();
  $stmt->store_result();
  header("location: ../index.php?status=delsuccess"); //War die Löschung Erfolgreich wird dies der Startseite mitgeteilt
}

//Die Funktion ändert den Archivierungsstatus von true auf false oder von false auf true
function archiveToDo($ToDo_ID, $conn)
{
  $result = mysqli_query($conn, "SELECT archieve from to_do where ID = '$ToDo_ID'");
  if ((mysqli_fetch_assoc($result)["archieve"]) == "false") {
    mysqli_query($conn, "UPDATE to_do SET archieve = (true) where ID = '$ToDo_ID'");
    header("location: ../index.php?status=archieved"); //Der Startseite wird die Archiverung bestätigen
  } else {
    mysqli_query($conn, "UPDATE to_do SET archieve = 'false' where ID = '$ToDo_ID'");
    header("location: ../index.php?status=unarchieved"); //Der Startseite wird die unarchiverung bestätigen
  }
}

//Lädt den Editmode | Werte des ToDos werden geladen und der Seitentext ändert sich
function editToDo($ToDo_ID, $conn)
{
  global $edit_title, $edit_text, $edit_priority, $edit_date, $edit_category_ID, $edit_status, $site_title, $alert_message;
  $sql = "SELECT * from To_Do where ID = '$ToDo_ID'";
  $result = $conn->query($sql);
  $row = $result->fetch_assoc();
  $edit_priority = $row["priority"]; //Variabeln werden befüllt
  $edit_title = $row["title"]; //Variabeln werden befüllt
  $edit_text = str_replace("<br>", "\n", $row["text"]); //Variabeln werden befüllt
  $edit_status = $row["status"]; //Variabeln werden befüllt
  list($edit_date_year, $edit_date_month, $edit_date_day) = explode('-', $row['finishDate']);
  $edit_date =  $edit_date_month . "/" . $edit_date_day . "/" . $edit_date_year; //Datum wird für die Anzeige richtig zusammengebaut
  $edit_category_ID = $row["category_category_id"]; //Variabeln werden befüllt
  $site_title = "ToDo bearbeiten";
  $alert_message = "Sie können das ToDo mit der ID #" . $ToDo_ID . " bearbeiten";
}
//Überprüft ob der Benutzer das ToDo erstellt hat
function permissionToAction($ToDo_ID, $conn)
{
  $user_id = $_SESSION["id"];
  $result = mysqli_query($conn, "SELECT ID FROM to_do WHERE ID = '$ToDo_ID' AND user_ID = '$user_id'");
  if (empty(mysqli_fetch_assoc($result)["ID"])) {
    return false;
  } else {
    return true;
  }
}
//Überprüft ob ein ToDo Name in der schon Datenbank existiert returnt true oder false     
function existsToDoName($ToDo_Title, $conn)
{
  $stmt = $conn->prepare("SELECT title FROM to_do WHERE title = (?)");
  $stmt->bind_param("s", $ToDo_Title);
  $stmt->execute();
  $stmt->store_result();
  $countRows = $stmt->num_rows;
  if ($countRows >= 1) {
    return true;
  } else {
    return  false;
  }
}

//Macht aus der Kategorie ID ein Kategorie Namen
function getCategoryname($conn, $category_ID)
{
  $result = mysqli_query($conn, "SELECT Name from Category WHERE category_id = '$category_ID'");
  return mysqli_fetch_assoc($result)["Name"];
}

//Generiet eine Kategorie ID liste mit den Kategorien die der Benutzer erstellt hat.
function generateCategorylist($conn, $user_ID, $edit_category_ID)
{
  $query = $conn->query("SELECT category_category_id from user_has_categories where user_ID = '$user_ID'");
  while ($row = $query->fetch_array()) {
    $categoryID = $row['category_category_id'];
    $categoryname = getCategoryname($conn, $categoryID);
    if ($edit_category_ID == $categoryID) {
      echo "<option value=" . $categoryID . " selected >" . $categoryname . "</option>";
    } else {
      echo "<option value=" . $categoryID . ">" . $categoryname . "</option>";
    }
  }
}
//Überprüft ob überhaupt Kategorien für diesen Nutzer existieren.
function haveCategories($conn, $user_ID)
{
  $stmt = $conn->prepare("SELECT * FROM user_has_categories WHERE user_ID = (?)");
  $stmt->bind_param("s", $user_ID);
  $stmt->execute();
  $stmt->store_result();
  $countRows = $stmt->num_rows;
  if ($countRows >= 1) {
    return true;
  } else {
    return false;
  }
}
//Wird ein POST-Request gesendet
if ($_SERVER['REQUEST_METHOD'] == "POST") {
  require("../../lib/validate_form_server_side.inc.php");
  validateToDo($conn);
}

// wenn kein Fehler vorhanden ist, schreiben der Daten in die Datenbank
$ToDo_archieve = "false"; // Standartmässig ist ein ToDo nicht archiviert

if (empty($error)) {
  if (isset($_POST['writemode'])) { //Dient zur unterscheidung von ToDo erstellen und Bearbeiten Modus
    $DateNow = date("y.m.d");
    if ($_POST['writemode'] == "submit") {  //Beim Erstellen
      if (!(existsToDoName($ToDo_title, $conn))) { //Überprüft ob der ToDo Name schon existiert.
        $stmt = $conn->prepare("INSERT INTO to_do VALUES (NULL,(?),(?),(?),(?),(?),(?),(?),(?),(?))");
        $stmt->bind_param("sssssssss", $ToDo_priority, $ToDo_title, $ToDo_text, $DateNow, $ToDo_finishDate, $ToDo_archieve, $ToDo_category_ID, $status, $user_ID);
        $stmt->execute();
        $alert_class_name = "alert alert-success";
        $alert_message = "Der ToDo wurde erfolgreich angelegt.";
      } else {
        $error .= "ToDo Name existiert bereits.<br/>";
      }
    } else {
      $tempID = (int)$_POST['writemode']; //Beim bearbeiten, das TODO wird aktuallisiert
      if (permissionToAction($tempID, $conn, $_SESSION["id"])) { //Hat der Nutzer die berechtigung dafür?
        $stmt = $conn->prepare("UPDATE to_do SET priority = (?),title = (?),text = (?),finishDate = (?),category_category_id = (?),status = (?) WHERE ID = (?)");
        $stmt->bind_param("ssssisi", $ToDo_priority, $ToDo_title, $ToDo_text, $ToDo_finishDate, $ToDo_category_ID, $status, $tempID);
        $stmt->execute();
        header("location: index.php?ID=" . $tempID . "&save=success");
      } else {
        $alert_class_name = "alert alert-danger";
        $alert_message = "Sie haben keine Berechtigung das ToDo zu, bearbeiten oder es existiert nicht.";
      }
    }
  }
}

function checkSelected($edit_priority, $currentValue)
{ //Lädt die Prioritätenanzeige für HTML
  if ($edit_priority == $currentValue) {
    return "selected";
  } else {
    return "";
  }
}


if ($_SERVER['REQUEST_METHOD'] == 'GET') //Sollte ein ToDo bearbeitet oder gelöscht werden
{
  if (isset($_GET['ID'])) {         //Sollte ein ToDo bearbeitet werden, dann ist die Variabel ID gesetzt
    $editmode = (int)$_GET['ID']; //Wird zu INT konvertiert damit SQL-Injections verhindert werden können.
    if (permissionToAction($editmode, $conn, $_SESSION["id"])) {
      editToDo($editmode, $conn);
    } else {
      header("location: ../index.php?status=permissionfail");
    }
    if (isset($_GET['save'])) { //Falls das ToDo schon enmal bearbeitet wurde.
      $alert_class_name = "alert alert-success";
      $alert_message = "Der ToDo wurde erfolgreich bearbeitet.";
    }
  } elseif (isset($_GET['DEL'])) {  //Sollte ein ToDo gelöscht werden, dann ist die Variabel DEL gesetzt.
    $delID = (int)$_GET['DEL'];
    if (permissionToAction($delID, $conn, $_SESSION["id"])) { //Überprüft ob der Nutzer die Rechte dafür hat (Ob er ersteller dieser ToDo war.)
      delToDo($_GET['DEL'], $conn);
    }  //Funktion für die Löschung wird gestartet, die Variabel DEL wird mitgegeben.
    else {
      header("location: ../index.php?status=permissionfail");
    }
  } elseif (isset($_GET['archive'])) {  //Sollte ein ToDo gelöscht werden, dann ist die Variabel DEL gesetzt.
    $archiveTo_Do_ID = (int)$_GET['archive'];
    if (permissionToAction($archiveTo_Do_ID, $conn, $_SESSION["id"])) { //Überprüft ob der Nutzer die Rechte dafür hat (Ob er ersteller dieser ToDo war.)
      archiveToDo($archiveTo_Do_ID, $conn);
    }  //Funktion das Archivieren, wenn False dann wird es auf true gesetzt wenn true dann wird es auf false gesetzt.
    else {
      header("location: ../index.php?status=permissionfail");
    }
  }
}


if (!(empty($error))) {
  $alert_class_name = "alert alert-danger";
  $alert_message = $error;
} //Fehler werden ausgeben

if (!haveCategories($conn, $user_ID)) {
  $alert_class_name = "alert alert-danger";
  $alert_message = "Diesem Nutzer wurden keine Kategorien zugewiesen, er kann keine ToDos erstellen";
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
  <title>Startseite</title>
  <!-- jQuery -->
  <script src="../../js/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
  <link rel="stylesheet" href="../../CSS/stylesheet.css">
  <link rel="icon" type="image/x-icon" href="../../img/favicon.png">
</head>

<body>
  <nav class="navbar navbar-default">
    <div class="container-fluid">
      <div class="nav navbar-nav navbar-left">
        <div class="panel-buttons">
          <form style="display: inline" action="../" method="get">
            <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-home"></span> Home | ToDoVerwaltung</button>
          </form>
        </div>
      </div>

      <div class="nav navbar-nav navbar-right">
        <div class="panel-buttons">

          <form style="display: inline" action="../manage_todo" method="get">
            <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-inbox"></span> ToDo erstellen</button>
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
      <!-- ToDo Title -->
      <div class="form-group">
        <label for="title">Titel *</label>
        <input type="text" name="title" class="form-control" id="title" value="<?php echo $edit_title ?>" placeholder="Geben Sie einen Titel ein maximal 45 Zeichen." maxlength="45" required>
      </div>
      <!-- ToDo text -->
      <div class="form-group">
        <label for="text">Beschreibung *</label>
        <textarea class="form-control" id="text" name="text" rows="3" placeholder="Geben Sie eine Beschreibung an maximal 300 Zeichen." maxlength="300" required><?php echo $edit_text ?></textarea>
      </div>


      <!-- Datumeingabe -->
      <div class="form-group">
        <input data-provide="datepicker" name="date" value="<?php echo $edit_date ?>" required> <span class="glyphicon glyphicon-calendar"></span>
        </span>
      </div>


      <!-- Priorität Dropdown menü checkSelected wird geladen falls ToDo bearbeitet wird -->
      <div class="form-inline form-group">
        <label>Priorität:</label>
        <select class="form-control input-sm" name="priority">
          <option value="1" <?php echo checkSelected($edit_priority, "1") ?>>1</option>
          <option value="2" <?php echo checkSelected($edit_priority, "2") ?>>2</option>
          <option value="3" <?php echo checkSelected($edit_priority, "3") ?>>3</option>
          <option value="4" <?php echo checkSelected($edit_priority, "4") ?>>4</option>
          <option value="5" <?php echo checkSelected($edit_priority, "5") ?>>5</option>
        </select>
      </div>
      <!-- Kategorien -->
      <div class="form-inline form-group">
        <label>Kategorien:</label>
        <select class="form-control input-sm" name="category_ID">
          <?php generateCategorylist($conn, $_SESSION["id"], $edit_category_ID) ?>
        </select>
      </div>

      <!-- Status Slider -->
      <div class="form-group">
        <div class="slider">
          <label for="range" class="form-label">Status des ToDos:</label>
          <input type="range" name="status" step="1" min="0" max="100" value="<?php echo $edit_status ?>" id="range" onchange="rangePrimary.value=value">
          <input type="hidden" class="form-label" id="rangePrimary" />
        </div>
      </div> <br>

      <button type="submit" name="writemode" value="<?php echo $editmode; ?>" class="btn btn-info">Senden</button>
      <button type="reset" name="button" value="reset" class="btn btn-warning">zurücksetzen</button>
    </form>

  </div>
</body>

</html>