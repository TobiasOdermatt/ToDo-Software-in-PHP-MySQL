<?php
session_start();
session_regenerate_id();
//Ist die Session nicht gesetzt oder der Nutzer kein User ist  wird man auf die Login Seite weitergeleitet.
if (!isset($_SESSION["user"]) || $_SESSION["role"] != "user") {
  header("location:../login");
}
//Ende der Überprüfung

require("../lib/dbconnector.inc.php"); //Datenbank 
$succes_message = " Herzlich Willkommen " . $_SESSION["user"] . " #" . $_SESSION["id"]; //Anfangsmessage
$alert_class_name = "alert alert-success";
$alert_message = $succes_message; //Message wird zu Anfangsmessage gesetzt
$user_ID = $_SESSION["id"]; //UserID wird gesetzt
$searchResult = "";

require("../lib/status.inc.php"); //Fehlermeldungen/Hinweise werden hier geladen




//Gibt ein Array zurück mit den erlaubten ToDo IDs die der Benutzer sehen darf.
function getAllowedToDoIDs($conn, $user_ID)
{
  $ToDoIDArray = [];
  $query = $conn->query("SELECT category_category_id FROM user_has_categories where user_ID = '$user_ID'");
  while ($row = $query->fetch_array()) {
    $category_id = $row["category_category_id"];
    $selectToDoID = $conn->query("SELECT ID from to_do where category_category_id = '$category_id'");
    while ($toDO_ID = $selectToDoID->fetch_array()) {
      array_push($ToDoIDArray, $toDO_ID["ID"]);
    }
  }
  return $ToDoIDArray;
}
//Generiet eine Kategorie ID liste mit den Kategorien die der Benutzer erstellt hat.
function generateCategorylist($conn, $user_ID, $edit_category_ID)
{
  $query = $conn->query("SELECT category_category_id from user_has_categories where user_ID = '$user_ID'");
  while ($row = $query->fetch_array()) {
    $categoryID = $row['category_category_id'];
    $categoryname = getCategoryname($categoryID);
    if ($edit_category_ID == $categoryID) {
      echo "<option value=" . $categoryID . " selected >" . $categoryname . "</option>";
    } else {
      echo "<option value=" . $categoryID . ">" . $categoryname . "</option>";
    }
  }
}

//Lädt den Editmode | Werte des ToDos werden geladen und der Seitentext ändert sich
function editToDo($ToDo_ID, $conn)
{
  global $edit_title, $edit_text, $edit_priority, $edit_date, $edit_category_ID, $edit_status;
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
}

function checkSelected($edit_priority, $currentValue)
{ //Lädt die Prioritätenanzeige für HTML
  if ($edit_priority == $currentValue) {
    return "selected";
  } else {
    return "";
  }
}
//Ruft die createModalEdit Funktion nur auf wenn der Benutzer dazu berechtigt ist.
function generateModals($conn)
{
  $user_ID = $_SESSION['id'];
  $ToDos = getAllowedToDoIDs($conn, $user_ID);
  foreach ($ToDos as $ToDo_ID) {
    if (permissionToAction($conn, $ToDo_ID)) {
      createModalEdit($ToDo_ID, $conn);
    }
  }
}

function createModalEdit($ToDoID, $conn) //Erstellt ein Bearbeite Formfeld zum bearbeiten eines Nutzers
{
  global $edit_title, $edit_text, $edit_priority, $edit_date, $edit_category_ID, $edit_status;
  editToDo($ToDoID, $conn);
?>
  <!-- Modal -->
  <div class="modal fade" id="<?php echo $ToDoID ?>" role="dialog">
    <div class="modal-dialog">

      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Hier können das ToDo #<?php echo $ToDoID ?> bearbeiten</h4>
        </div>
        <div class="modal-body">

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



            <!-- Senden und Löschen buttons -->
            <button type="submit" name="writemode" value="<?php echo $ToDoID; ?>" class="btn btn-info">Senden</button>
            <button type="reset" name="button" value="reset" class="btn btn-warning">Löschen</button>
        </div>
        </form>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Verwerfen</button>

        </div>
      </div>
    </div>
  </div><?php
      }
      //Gibt dem Namen einer Kategorie zurück anhand der Kategorien_ID
      function getCategoryName($category_id)
      {
        global $conn;
        $sql = "SELECT Name from category where category_id = '$category_id'";
        $result = $conn->query($sql);
        $row = $result->fetch_array();
        return $row['Name'];
      }

      //Generiert den Fälligkeitstext, wurde ein ToDo archiviert steht nur "erledigt"
      function getFinishDate_Text($FinishDate, $archieved = null)
      {
        $FinishTimeStamp = strtotime($FinishDate);
        $today = time();
        $difference = $FinishTimeStamp - $today;
        $difference = $difference / 60 / 60 / 24 + 1; //Berechnung für wie viele Tage
        if ($archieved == null) {
          if ($difference > 1) { //Zukunft
            return "<p class=\"text-success\"> in " . (floor($difference)) . " Tagen </p>";
          } else if ($difference <= 1 && $difference > 0) { //Heute
            return "<p class=\"text-success\">Heute</p>";
          } else if ($difference < 0) { //Vergangenheit
            return "<p class=\"text-danger\">" . "Seit " . (abs(floor($difference))) . " Tagen! </p>";
          }
        }
        if ($archieved = "true") {
          return "<p class=\"text-success\">erledigt</p>";
        } //Falls archiviert
      }

      //Ist der Archieve Value auf True wird der Text durchgestrichen zurückgegeben
      function archievIfExists($text, $archieved)
      {
        if ($archieved == "true") {
          return "<del>" . $text . "</del>";
        } else {
          return $text;
        };
      }
      //Überprüft ob der Benutzer das ToDo erstellt hat
      function permissionToAction($conn, $ToDo_ID)
      {
        $user_id = $_SESSION["id"];
        $result = mysqli_query($conn, "SELECT ID FROM to_do WHERE ID = '$ToDo_ID' AND user_ID = '$user_id'");
        if (empty(mysqli_fetch_assoc($result)["ID"])) {
          return false;
        } else {
          return true;
        }
      }
      //Generiert die Aktionbutton falls der Benutzer das ToDO Erstellt hat.
      function generateActionButton($user_ID, $OwnerUser_ID, $ToDo_ID)
      {
        if ($user_ID == $OwnerUser_ID) {
        ?> <td>
      <a href="manage_todo?archive=<?php echo $ToDo_ID ?>">
        <span class="fa-stack">
          <i class="fa fa-square fa-stack-2x"></i>
          <i class="fa fa fa-archive fa-stack-1x fa-inverse"></i>
        </span></a>

      <a>
        <span class="fa-stack" data-toggle="modal" data-target="#<?php echo $ToDo_ID ?>">
          <i class="fa fa-square fa-stack-2x"></i>
          <i class="fa fa fa-pencil fa-stack-1x fa-inverse"></i>
        </span></a>
      <a href="manage_todo?DEL=<?php echo $ToDo_ID ?>">
        <span class="fa-stack">
          <i class="fa fa-square fa-stack-2x"></i>
          <i class="fa fa-trash-o fa-stack-1x fa-inverse"></i>
        </span></a>
    </td><?php
        } else {
          return "<td>Anderer Benutzer</td>";
        } //Falls der Benutzer das ToDo nicht erstellt hat.
      }


      //Erstellt ein ToDo Beitrag, ruft dazu alle nötigen Funktionen zum Anzeigen auf, wird nach Priorität und nach Datum sortiert.
      function viewToDos($conn)
      {
        $user_ID = $_SESSION['id'];
        $query = $conn->query("SELECT * FROM to_do where archieve = 'false' order by priority DESC, finishDate ASC");
        $AllowedToDoIDArray = getAllowedToDoIDs($conn, $user_ID);
        while ($row = $query->fetch_array()) {
          if (!in_array($row['ID'], $AllowedToDoIDArray)) {
            continue;
          }; ?>
    <tr>
      <td><a href="#"><span class="fa-stack" data-toggle="collapse" data-target="#view<?php echo $row['ID'] ?>" class="accordion-toggle"><i class="fa fa-square fa-stack-2x"></i>
            <i class="fa fa fa-eye fa-stack-1x fa-inverse"></i></span></a></td>
      <td><?php echo $row['ID'] ?></td>
      <td><?php echo $row['priority'] ?></td>
      <td><?php echo getCategoryName($row['category_category_id']) ?></td>
      <td><strong><?php echo ($row['title']) ?></strong></td>
      <td><?php echo (date("d.m.Y", strtotime($row['addDate']))) ?></td>
      <td><?php echo getFinishDate_Text($row['finishDate']) ?></td>
      <td>
        <div class="progress">
          <div class="progress-bar progress-bar" role="progressbar" style="width: <?php echo $row["status"] ?>%" aria-valuenow="<?php echo $row["status"] ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $row["status"] ?>%</div>
        </div>
      </td><?php echo generateActionButton($user_ID, $row['user_ID'], $row['ID']) ?>
    </tr>
    <tr>
      <td colspan="9" class="hiddenRow">
        <div id="view<?php echo $row['ID'] ?>" class="accordian-body collapse"><strong>Beschreibung: </strong><br />
          <?php echo $row['text'] ?>
          <hr>
        </div>
      </td>
    </tr><?php
        }
      }

      //Erstellt der Inhalt der angefordeten Suche,ruft dazu alle nötigen Funktionen zum Anzeigen auf, 
      //wird nach Priorität und nach Datum sortiert, es werden nur ToDos angezeigt mit dennen der Nutzer eine Kategorie teilt
      function searchToDo($conn, $SearchString)
      {
        $searchCount = 0;
        $user_ID = $_SESSION['id'];
        $searchedTitle = "%" . $SearchString . "%";
        $searchedText = "%" . $SearchString . "%";
        $AllowedToDoIDArray = getAllowedToDoIDs($conn, $user_ID);
        $ToDo_ID = $priority = $title = $text = $addDate = $finishDate = $archieve = $category_name = $status = $ToDouser_ID = "";
        $stmt = $conn->prepare("SELECT ID,priority,addDate,title,text,addDate,finishDate,archieve,status,user_ID, category.name as category_name FROM category JOIN to_do ON category.category_id = to_do.category_category_id where title like (?) OR text like (?) order by priority DESC, finishDate ASC");
        $stmt->bind_param('ss', $searchedTitle, $searchedText);
        $stmt->execute();
        $stmt->bind_result($ToDo_ID, $priority, $addDate, $title, $text, $addDate, $finishDate, $archieve, $status, $ToDouser_ID, $category_name);
        while ($stmt->fetch()) {
          if (!in_array($ToDo_ID, $AllowedToDoIDArray)) {
            continue;
          }
          $searchCount++;
          ?>
    <tr data-toggle="collapse" data-target="#<?php echo $ToDo_ID ?>" class="accordion-toggle">
      <td><a href="#"><span class="fa-stack"><i class="fa fa-square fa-stack-2x"></i>
            <i class="fa fa fa-eye fa-stack-1x fa-inverse"></i></span></a></td>
      <td><?php echo archievIfExists($ToDo_ID, $archieve) ?></td>
      <td><?php echo archievIfExists($priority, $archieve) ?></td>
      <td><?php echo archievIfExists($category_name, $archieve) ?></td>
      <td><strong><?php echo archievIfExists($title, $archieve) ?></strong></td>
      <td><?php echo (date("d.m.Y", strtotime($addDate))) ?></td>
      <td><?php echo getFinishDate_Text($finishDate, $archieve) ?></td>
      <td>
        <div class="progress">
          <div class="progress-bar progress-bar" role="progressbar" style="width: <?php echo $status ?>%" aria-valuenow="<?php echo $status ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $status ?>%</div>
        </div>
      </td><?php echo (generateActionButton($user_ID, $ToDouser_ID, $ToDo_ID)) ?>
    </tr>
    <tr>
      <td colspan="9" class="hiddenRow">
        <div id="<?php echo $ToDo_ID ?>" class="accordian-body collapse"><strong>Beschreibung: </strong><br />
          <?php echo $text ?>
          <hr>
        </div>
      </td>
    </tr><?php createModalEdit($ToDo_ID, $conn);
        }
      }


      //Wird ein POST-Request gesendet
      if ($_SERVER['REQUEST_METHOD'] == "POST") {
        require("../lib/validate_form_server_side.inc.php");
        validateToDo($conn);

        if (empty($error)) {
          if (isset($_POST['writemode'])) { //Dient zur unterscheidung von ToDo erstellen und Bearbeiten Modus
            $DateNow = date("y.m.d");
            $tempID = (int)$_POST['writemode']; //Beim bearbeiten, das TODO wird aktuallisiert
            if (permissionToAction($conn, $tempID)) { //Hat der Nutzer die berechtigung dafür?
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


      //Falls ein Beitrag gesucht wird
      if ($_SERVER['REQUEST_METHOD'] == 'GET') //Wird ein GET-Request gesendet
      {
        if (isset($_GET["ToDoSearch"])) //Überprüfung ob etwas in die Suche eingegeben wurde
        {
          $searchString = htmlspecialchars($_GET["ToDoSearch"]);
        } else { //Falls nicht wird davon ausegegangen das ein ToDo bearbeitet wird
          require("../lib/validate_form_server_side.inc.php");
          validateToDo($conn);
        } //ToDo wird Validiertt
        if (empty($error)) {
          if (isset($_POST['writemode'])) { //Dient zur unterscheidung von ToDo erstellen und Bearbeiten Modus
            $DateNow = date("y.m.d");
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
          ?>
<!DOCTYPE html>
<html lang="de">

<head>
  <title>Startseite</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="../CSS/stylesheet.css">
  <script src="//code.jquery.com/jquery-1.11.1.min.js"></script>
  <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
  <script src="../js/script.js"></script>

  <link rel="icon" type="image/x-icon" href="../img/favicon.png">
</head>

<body>
  <nav class="navbar navbar-default">
    <div class="container-fluid">
      <div class="nav navbar-nav navbar-left">
        <div class="panel-buttons">
          <form style="display: inline" action="../user" method="get">
            <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-home"></span> Home | ToDoVerwaltung</button>
          </form>
        </div>
      </div>

      <div class="nav navbar-nav navbar-right">
        <div class="panel-buttons">

          <form style="display: inline" action="manage_todo" method="get">
            <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-inbox"></span> ToDo erstellen</button>
          </form>


          <form style="display: inline" action="../logout" method="get">
            <button type="submit" class="btn btn-default "><span class="glyphicon glyphicon-log-out"></span> Log out</button>
          </form>

        </div>
      </div>
    </div>
  </nav>

  <div class="well well-sm">
    <h4>Userpanel / ToDoVerwaltung</h4>
  </div>
  <div class="container-fluid">


    <!-- Hier werden Fehlermeldungen/Meldungen ausgegeben -->
    <div class="form-group">
      <div class="<?php echo $alert_class_name; ?>" role="alert"><?php echo $alert_message; ?></div>
    </div>


    <!-- Suchleiste -->
    <form action="index.php" method="GET">
      <div class="row">
        <div class="col-xs-1 col-md-3">
          <div class="input-group">
            <input type="text" class="form-control" placeholder="Alle ToDos durchsuchen" name="ToDoSearch" />
            <div class="input-group-btn">
              <button class="btn btn-primary" type="submit">
                <span class="glyphicon glyphicon-search"></span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </form><br>

    <?php generateModals($conn); ?>
    <table class="table table-striped">
      <thead>
        <tr>
          <th style="width: 4%"></th>
          <th style="width: 4%">ID:</th>
          <th style="width: 8%">Priorität:</th>
          <th style="width: 9%">Kategorie:</th>
          <th style="width: 22%">Titel:</th>
          <th style="width: 12%">Erstellt:</th>
          <th style="width: 10%">Fällig:</th>
          <th style="width: 16%">Status:</th>
          <th style="width: 15%">Aktion:</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($searchString)) {
          viewToDos($conn);
        } else {
          searchToDo($conn, $searchString);
        } ?>
      </tbody>
    </table>
  </div>

</body>

</html>