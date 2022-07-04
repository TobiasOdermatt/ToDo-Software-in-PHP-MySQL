<?php
session_start();
session_regenerate_id();
$user_id = "";
$user_id = "";
if (!isset($_SESSION["user"]) || $_SESSION["role"] == "user") {
  header("location:../../login");
}
require("../../lib/dbconnector.inc.php");
$site_title = "Einem Nutzer ein oder mehrere Kategorien zuweisen. ";
$alert_class_name = "alert alert-info";
$alert_message = "Hier kann einem Nutzer eine oder mehr Kategorien zugewiesen werden. <br />";
$error = "";

function readCheckBox($ArrayCheckbox, $conn, $user_id)
{
  global $alert_message; //Ruft die Zuweisungsfunktion auf
  foreach ($ArrayCheckbox as $key => $category_id) {
    assignUserCategory($user_id, $category_id, $conn);
    $alert_message = "Änderungen wurden gespeichert";
  }

  $query = $conn->query("SELECT category_id FROM category");
  if (!(($query->num_rows  == 0))) {
    while ($row = $query->fetch_array()) {
      if (!(in_array($row["category_id"], $ArrayCheckbox))) { // Wurde ein Feld nicht angewählt wird die Zuweisung gelöscht
        delAssign($conn, $user_id, $row["category_id"]);
      }
    }
  }
}

function delAssign($conn, $user_id, $category_id)
{ //Löscht eine Zuweisung
  $stmt = $conn->prepare("DELETE FROM user_has_categories WHERE user_ID = (?) AND category_category_id = (?)");
  $stmt->bind_param("ss", $user_id, $category_id);
  $stmt->execute();
}
function uncheckEverything($conn, $user_id)
{
  $stmt = $conn->prepare("DELETE FROM user_has_categories WHERE user_ID = (?)");
  $stmt->bind_param("s", $user_id);
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
  $query = $conn->query("SELECT *  FROM category");
  if (!(($query->num_rows  == 0))) {
    return true;
  } else {
    return false;
  }
}
function ShowButton($conn)
{
  global $user_id;
  if (existUser($conn)) {
    echo "<button type=\"submit\" name=\"category_id\" value=\"$user_id\" class=\"btn btn-info\">Senden</button>
        <button type=\"reset\" name=\"button\" value=\"reset\" class=\"btn btn-warning\">Zurücksetzten</button>";
  }
}

function assignUserCategory($user_id, $category_id, $conn)
{ //Erstellt eine Zuweisung
  $stmt = $conn->prepare("INSERT INTO  user_has_categories (user_ID,category_category_id) VALUES (?,?)");
  $stmt->bind_param("ii", $user_id, $category_id);
  $stmt->execute();
}
function alreadyAssigned($user_id, $category_id, $conn)
{ //Überprüft ob es schon zugewiesen worden ist.
  if (existingAssign($user_id, $category_id, $conn)) {
    return "Checked";
  } else {
    return "";
  }
}
function view_checkBox_assign_user_category($conn, $user_id)
{
  $query = $conn->query("SELECT *  FROM category");
  if (existUser($conn)) {
    while ($row = $query->fetch_array()) {
      $checked = alreadyAssigned($user_id, $row["category_id"], $conn);
      echo "<tr>" .
        "<td>" . $row['name'] . "</td>" .  //Benutzername Anzeige
        "<td><div class=\"material-switch pull-right\">" . //Switch für jeder Benutzer
        "<input id=\"" . $row["category_id"] . "\" name=\"SwitchForm[]\"" .
        " value=\"" . $row["category_id"] . "\" type=\"checkbox\"" . $checked . "/>" .
        "<label for=\"" . $row["category_id"] . "\" class=\"label-success\"></label></div></td>" .
        "</tr>";
    }
  } else {
    echo "<td>Es existieren keine Nutzer zum hinzufügen</td>";
  }
}



if ($_SERVER['REQUEST_METHOD'] == "POST") {
  if (isset($_POST["category_id"])) {
    $user_id = (int) $_POST["category_id"];
    if (!empty($_POST['SwitchForm'])) {
      readCheckBox($_POST['SwitchForm'], $conn, $user_id);
    } else {
      $alert_message = "Nutzer ist keiner Kategorie zugeordnet";
      uncheckEverything($conn, $user_id);
    }
  }
}


if ($_SERVER['REQUEST_METHOD'] == 'GET') {
  if (isset($_GET['ID'])) {
    $user_id = (int)$_GET['ID'];
    if (empty($error)) {
      $alert_message = "Klicken Sie die Kategorien an, die dem Nutzer #" . $user_id . " hinzufügt werden sollen";
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
    <form action="index.php?ID=<?php echo $user_id; ?>" method="post">
      <table class="table table-striped table-hover">
        <thead>
          <tr>
            <th>Kategorie Namen:</th>
            <th>
              <p class="text-right">Auswahl:</p>
            </th>
          </tr>
        </thead>

        <tbody>
          <?php view_checkBox_assign_user_category($conn, $user_id); ?>
        </tbody>
      </table>
      <?php ShowButton($conn); ?>
    </form>
  </div>
</body>

</html>