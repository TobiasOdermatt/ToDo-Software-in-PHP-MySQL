<?php
session_start();
session_regenerate_id();
$category_id = "";
$category_id = "";
if (!isset($_SESSION["user"]) || $_SESSION["role"] == "user") {
  header("location:../../login");
}
require("../../lib/dbconnector.inc.php");
$site_title = "Eine Kategorie ein oder mehrere Nutzer zuweisen. ";
$alert_class_name = "alert alert-info";
$alert_message = "Hier können Nutzer Kategorien zugewiesen werden. <br />";
$error = "";

function readCheckBox($ArrayCheckbox, $conn, $category_id)
{
  global $alert_message; //Ruft die Zuweisungsfunktion auf
  foreach ($ArrayCheckbox as $key => $user_id) {
    if(!existingAssign($user_id, $category_id, $conn))
    assignUserCategory($category_id, $user_id, $conn);
    $alert_message = "Änderungen wurden gespeichert";
  }

  $query = $conn->query("SELECT ID FROM user where role = 'user'");
  if (!(($query->num_rows  == 0))) {
    while ($row = $query->fetch_array()) {
      if (!(in_array($row["ID"], $ArrayCheckbox)))  // Wurde ein Feld nicht angewählt wird die Zuweisung gelöscht
        delAssign($conn, $row["ID"], $category_id);
    }
  }
}


function delAssign($conn, $user_id, $category_id)
{ //Löscht eine Zuweisung
  $stmt = $conn->prepare("DELETE FROM user_has_categories WHERE user_ID = (?) AND category_category_id = (?)");
  $stmt->bind_param("ss", $user_id, $category_id);
  $stmt->execute();
}

function delEveryAssign($conn, $category_id)
{
  $stmt = $conn->prepare("DELETE FROM user_has_categories WHERE category_category_id = (?)");
  $stmt->bind_param("s", $category_id);
  $stmt->execute();
}

function existingAssign($user_id, $category_id, $conn)
{ //Überprüft ob die Kategorie dem Nutzer schon zugeordnet wurde.
  $stmt = $conn->prepare("SELECT * FROM user_has_categories WHERE user_ID = (?) AND category_category_id = (?)");
  $stmt->bind_param("ii", $user_id, $category_id);
  $stmt->execute();
  $stmt->store_result();
  $countRows = $stmt->num_rows;
  if ($countRows >= 1) {
    return true;
  } else {
    return false;
  }
}

function existUser($conn)
{
  $query = $conn->query("SELECT * FROM user where role = 'user'");
  if (!(($query->num_rows  == 0))) {
    return true;
  } else {
    return false;
  }
}
function ShowButton($conn)
{
  global $category_id;
  if (existUser($conn)) {
    echo "<button type=\"submit\" name=\"category_id\" value=\"$category_id\" class=\"btn btn-info\">Senden</button>
          <button type=\"reset\" name=\"button\" value=\"reset\" class=\"btn btn-warning\">Zurücksetzten</button>";
  }
}

function assignUserCategory($category_id, $user_id, $conn)
{ //Erstellt eine Zuweisung
  $stmt = $conn->prepare("INSERT INTO  user_has_categories (user_ID,category_category_id) VALUES (?,?)");
  $stmt->bind_param("ii", $user_id, $category_id);
  $stmt->execute();
}

function alreadyAssigned($category_id, $user_id, $conn)
{ //Überprüft ob es schon zugewiesen worden ist.
  if (existingAssign($user_id, $category_id, $conn)) {
    return "Checked";
  } else {
    return "";
  }
}

function view_checkBox_assign_category_user($conn, $category_id)
{
  $query = $conn->query("SELECT *  FROM user where role = 'user'");
  if (!(($query->num_rows  == 0))) {
    while ($row = $query->fetch_array()) {
      $checked = alreadyAssigned($category_id, $row["ID"], $conn);
      echo "<tr>" .
        "<td>" . $row['username'] . "</td>" .  //Benutzername Anzeige
        "<td>" . $row["firstname"] . "</td>" .   //Vorname Anzeige
        "<td>" . $row["lastname"] . "</td>" .    //Nachname Anzeige
        "<td><div class=\"material-switch pull-right\">" . //Switch für jeder Benutzer
        "<input id=\"" . $row["ID"] . "\" name=\"SwitchForm[]\"" .
        " value=\"" . $row["ID"] . "\" type=\"checkbox\"" . $checked . "/>" .
        "<label for=\"" . $row["ID"] . "\" class=\"label-success\"></label></div></td>" .
        "</tr>";
    }
  } else {
    echo "<tr><td>Es existieren keine Nutzer zum hinzufügen</td></tr>";
  }  //Falls keinne Nutzer existieren
}



if ($_SERVER['REQUEST_METHOD'] == "POST") {
  if (isset($_POST["category_id"])) {
    $category_id = (int) $_POST["category_id"];
    if (!empty($_POST['SwitchForm'])) {
      readCheckBox($_POST['SwitchForm'], $conn, $category_id);
    } else {
      $alert_message = "Kategorie ist keinem Nutzer mehr zugeordnet";
      delEveryAssign($conn, $category_id);
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  if (isset($_GET['ID'])) {
    $category_id = (int)$_GET['ID'];
    if (empty($error)) {
      $alert_message = "Klicken Sie die Benutzer an, die der Kategorie #" . $category_id . " hinzufügt werden sollen";
    } else {
      $alert_message = $error;
    }
  }
}
if (!empty($error)) {
  $alert_message = $error;
}
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
    <form action="index.php?ID=<?php echo $category_id; ?>" method="post">
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Benutzername:</th>
            <th>Vorname:</th>
            <th>Nachname:</th>
            <th>
              <p class="text-right">Auswahl:</p>
            </th>
          </tr>
        </thead>

        <tbody>
          <?php view_checkBox_assign_category_user($conn, $category_id); ?>

        </tbody>
      </table>
      <?php ShowButton($conn); ?>
    </form>
  </div>
</body>

</html>