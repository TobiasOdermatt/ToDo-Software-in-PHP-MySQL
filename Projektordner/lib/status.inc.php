<?php
if(isset($_GET['status'])){ //Wird ein GET-Request mit Status Attribut gesendet wird eine Meldung Ausgegeben

    //Meldung wenn der Nutzer erfolgreich gelöscht wurde
    if($_GET['status'] == "delsuccess"){ 
        $alert_class_name = "alert alert-success";
        $alert_message = "Das ToDo wurde erfolgreich gelöscht.";}

    //Meldung falls ToDo nicht existiert oder Nutzer keine Berechtigung hat
    else if($_GET['status'] == "permissionfail"){
        $alert_class_name = "alert alert-danger";
        $alert_message = "Sie haben keine Berechtigung für dieses ToDo";
    }
    //Meldung falls ToDo Archiviert
    else if($_GET['status'] == "archieved"){ 
        $alert_class_name = "alert alert-success";
        $alert_message = "ToDo wurde archieviert";
    }
    //Meldung falls ToDo nicht mehr Archiviert ist
    else if($_GET['status'] == "unarchieved"){ 
        $alert_class_name = "alert alert-info";
        $alert_message = "ToDo wurde unarchieviert";
    }}
    //Sind keine ToDos vorhanden
    if(getAllowedToDoIDs($conn,$user_ID) == null){ 
        $alert_class_name = "alert alert-warning";
        $alert_message = "Es existieren noch keine ToDos";
        }
?>