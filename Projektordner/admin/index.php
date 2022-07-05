<?php
session_start();
session_regenerate_id();
//Ist die Session nicht gesetzt oder der Nutzer kein Admin wird man auf die Login Seite weitergeleitet.
if (!isset($_SESSION["user"]) || $_SESSION["role"] != "admin") {
  header("location:../login");
}
require("../lib/dbconnector.inc.php"); //Datenbank 
$site_title = "Adminpanel / Benutzerverwaltung";   //Standart Seitentitel
$succes_message = " Herzlich Willkommen " . $_SESSION["user"] . " #" . $_SESSION["id"]; //Anfangsmessage
$alert_class_name = "alert alert-success";
$alert_message = $succes_message; //Message wird zu Anfangsmessage gesetzt
$error = "";

require("../lib/queryCollection.inc.php");

if (countUSER($conn) == 0) { // Existiert keine Nutzer wird dies angezeigt
  $alert_class_name = "alert alert-warning";
  $alert_message = "Es existieren noch keine Benutzereinträge";
}


function createModalEdit($userID, $conn, $username) //Erstellt ein Bearbeite Formfeld zum bearbeiten eines Nutzers
{
  editUSER($userID, $conn);
  global $edit_firstname, $edit_lastname, $edit_username;
?>
  <!-- Modal -->
  <div class="modal fade" id="<?php echo $userID ?>" role="dialog">
    <div class="modal-dialog">

      <!-- Modal content-->
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Hier können Sie den Benutzer "<?php echo $username ?>" bearbeiten</h4>
        </div>
        <div class="modal-body">
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
            <button type="submit" name="writemode" value="<?php echo $userID; ?>" class="btn btn-info">Senden</button>
            <button type="reset" name="button" value="reset" class="btn btn-warning">Löschen</button>
        </div>
        </form>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Verwerfen</button>

        </div>
      </div><?php
          }

          function viewUSER($conn)
          {   //Gibt alle Nutzer in einer Tabelle aus.
            $query = $conn->query("SELECT ID,firstname,lastname,username,role FROM user");
            while ($row = $query->fetch_array()) {
              if ($row["role"] == "admin") {
                continue;
              } //Admin Nutzer werden nicht aufgeführt.
            ?> <tr>
          <td><?php echo $row['ID'] ?></td>
          <td><?php echo $row['username'] ?></td>
          <td><?php echo $row['firstname'] ?></td>
          <td><?php echo $row['lastname'] ?></td>
          <td style="width: 22%;\">
            <a href="assign_user_category?ID=<?php echo $row['ID'] ?>">
              <span class="fa-stack">
                <i class="fa fa-square fa-stack-2x"></i>
                <i class="fa fa-tag fa-stack-1x fa-inverse"></i>
              </span></a>
            <a data-toggle="modal" data-target="#<?php echo $row['ID'] ?>">
              <span class="fa-stack">
                <i class="fa fa-square fa-stack-2x"></i>
                <i class="fa fa-pencil fa-stack-1x fa-inverse"></i>
              </span></a>
            <a href="../admin/?DEL=<?php echo $row['ID'] ?>">
              <span class="fa-stack">
                <i class="fa fa-square fa-stack-2x"></i>
                <i class="fa fa-trash-o fa-stack-1x fa-inverse"></i>
              </span></a>
          </td>
        </tr><?php
              createModalEdit($row['ID'], $conn, $row["username"]);
            }
          }
          if ($_SERVER['REQUEST_METHOD'] == "POST") { //Wird ein Nutzer bearbeitet
            require("../lib/validate_form_server_side.inc.php");
            validateUser();  //Eingegebene Formfelder werden validiert

            // wenn kein Fehler vorhanden ist, schreiben der Daten in die Datenbank
            if (empty($error)) {
              if (isset($_POST['writemode'])) { //Falls Benutzer bearbeitet wird
                $tempID = (int)$_POST['writemode'];
                if (existUserID($tempID, $conn)) {
                  $stmt = $conn->prepare("UPDATE user SET firstname = (?), lastname = (?),username = (?), password = (?) WHERE ID = (?)");
                  $stmt->bind_param("ssssi", $firstname, $lastname, $username, $password, $tempID);
                  $stmt->execute();
                  $alert_class_name = "alert alert-success";
                  $alert_message = "Der Nutzer wurde erfolgreich bearbeitet.";
                }
              } else {
                $alert_message = $error;
              }
            }
          } elseif (isset($_GET['DEL'])) {  //Sollte ein Beitrag gelöscht werden, dann ist die Variabel DEL gesetzt.
            delUSER($_GET['DEL'], $conn);
          }  //Funktion für die Löschung wird gestartet, die Variabel DEL wird mitgegeben.
          if (!(empty($error))) {
            $alert_class_name = "alert alert-danger";
            $alert_message = $error;
          } //Fehler werden ausgeben


          if (isset($_GET['status'])) { //Wird ein GET-Request mit Status Attribut gesendet wird eine Meldung Ausgegeben
            if ($_GET['status'] == "delsuccess") { //Meldung wenn der Nutzer erfolgreich gelöscht wurde
              $alert_class_name = "alert alert-success";
              $alert_message = "Der Benutzer wurde erfolgreich gelöscht.";
            } else if ($_GET['status'] == "delfail") { //Meldung wenn der Nutzer nicht existiert.
              $alert_class_name = "alert alert-danger";
              $alert_message = "Der Benutzer existiert nicht.";
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
      <link rel="icon" type="image/x-icon" href="../img/favicon.png">
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
      <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    </head>

    <body>
      <nav class="navbar navbar-default">
        <div class="container-fluid">
          <div class="nav navbar-nav navbar-left">
            <div class="panel-buttons">
              <form style="display: inline" action="index.php" method="get">
                <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-home"></span> Home | Benutzerverwaltung</button>
              </form>
            </div>
          </div>

          <div class="nav navbar-nav navbar-right">
            <div class="panel-buttons">

              <form style="display: inline" action="view_category" method="get">
                <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-inbox"></span> Kategorien ansehen</button>
              </form>

              <form style="display: inline" action="manage_category" method="get">
                <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-tag"> </span> Kategorie erstellen</button>
              </form>

              <form style="display: inline" action="manage_user" method="get">
                <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-user"></span> Benutzer erstellen</button>
              </form>

              <form style="display: inline" action="../logout" method="get">
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


        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>ID:</th>
              <th style="width: 20%">Nutzername:</th>
              <th>Vorname:</th>
              <th>Nachname:</th>
              <th>Aktion:</th>
            </tr>
          </thead>

          <tbody>
            <?php viewUSER($conn); ?>

          </tbody>
        </table>
    </body>

    </html>