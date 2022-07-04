<?php
session_start();
session_regenerate_id();
if (!isset($_SESSION["user"]) || $_SESSION["role"] != "admin") { //Ist die Session nicht gesetzt oder der Nutzer kein Admin wird man auf die Login Seite weitergeleitet.
    header("location:../../login");
}
require("../../lib/dbconnector.inc.php"); //Datenbank
$site_title = "Adminpanel / Kategorieverwaltung"; //Standart Seitentitel
$alert_class_name = "alert alert-success";
$alert_message = "Hier können alle Kategorien eingesehen werden."; //Standart Seitentext

if (countCategory($conn) == 0) { // Existiert keine Kategorie wird dies angezeigt
    $alert_class_name = "alert alert-warning";
    $alert_message = "Es existieren noch keine Kategorieeinträge.";
}

function countCategory($conn)
{ //Gibt die Anzahl der Kategorien zurück
    $sql = "SELECT COUNT(*) from category";
    $result = $conn->query($sql);
    $data =  $result->fetch_assoc();
    return $data['COUNT(*)'];
}
function view_category($conn)
{  //Gibt alle Nutzer in einer Tabelle aus.
    $query = $conn->query("SELECT *  FROM category");
    while ($row = $query->fetch_array()) {
        echo "<tr>" .
            "<td>" . $row['category_id'] . "</td>" .
            "<td>" . $row['name'] . "</td>" .
            "<td style=\"width: 20%;\">" .
            "<a href=\"../assign_category_user/?ID=" . $row['category_id'] . "\">" . //Button für das zuweisen
            "<span class=\"fa-stack\">" .
            "<i class=\"fa fa-square fa-stack-2x\"></i>" .
            "<i class=\"fa fa-user-plus fa-stack-1x fa-inverse\"></i>" . //Icon Zuweisen
            "</span></a>" .
            "<a href=\"../manage_category?ID=" . $row['category_id'] . "\">" . //Button für das bearbeiten
            "<span class=\"fa-stack\">" .
            "<i class=\"fa fa-square fa-stack-2x\"></i>" .
            "<i class=\"fa fa-pencil fa-stack-1x fa-inverse\"></i>" . //Icon bearbeiten
            "</span></a>" .
            "<a href=\"../manage_category?DEL=" . $row['category_id'] . "\">" . //Button für das löschen
            "<span class=\"fa-stack\">" .
            "<i class=\"fa fa-square fa-stack-2x\"></i>" .
            "<i class=\"fa fa-trash-o fa-stack-1x fa-inverse\"></i>" . //Icon löschen
            "</span></a></td>" .
            "</tr>";
    }
}

if (isset($_GET['status'])) { //Wird ein GET-Request mit Status Attribut gesendet wird eine Meldung Ausgegeben
    if ($_GET['status'] == "delsuccess") { //Meldung wenn die Kategorie erfolgreich gelöscht wurde
        $alert_class_name = "alert alert-success";
        $alert_message = "Die Kategorie wurde erfolgreich gelöscht.";
    } else if ($_GET['status'] == "delfail") { //Meldung wenn die Kategorie nicht existiert.
        $alert_class_name = "alert alert-danger";
        $alert_message = "Die Kategorie existiert nicht.";
    }
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
    </head>
    <body>
        <nav class="navbar navbar-default">
            <div class="container-fluid">
                <div class="nav navbar-nav navbar-left">
                    <div class="panel-buttons">
                        <form style="display: inline" action="../" method="get">
                            <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-home"></span> Home | Benutzerverwaltung</button>
                        </form>
                    </div>
                </div>

                <div class="nav navbar-nav navbar-right">
                    <div class="panel-buttons">

                        <form style="display: inline" action="index.php" method="get">
                            <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-inbox"></span> Kategorien ansehen</button>
                        </form>

                        <form style="display: inline" action="../manage_category" method="get">
                            <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-tag"> </span> Kategorie erstellen</button>
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
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID:</th>
                        <th>Name:</th>
                        <th>Aktion:</th>
                    </tr>
                </thead>
                <tbody>
                    <?php view_category($conn); ?>
                </tbody>
            </table>
        </div>
    </body>
</html>