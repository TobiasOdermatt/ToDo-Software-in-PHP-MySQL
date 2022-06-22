<?php
  session_start();
  session_regenerate_id();
//Ist die Session nicht gesetzt oder der Nutzer kein User ist  wird man auf die Login Seite weitergeleitet.
if (!isset($_SESSION["user"]) || $_SESSION["role"] != "user"){
    header("location:../login");}
//Ende der Überprüfung

    require("../lib/dbconnector.inc.php"); //Datenbank 
    $succes_message = " Herzlich Willkommen ".$_SESSION["user"]." #".$_SESSION["id"]; //Anfangsmessage
    $alert_class_name = "alert alert-success";
    $alert_message = $succes_message; //Message wird zu Anfangsmessage gesetzt
    $user_ID = $_SESSION["id"]; //UserID wird gesetzt
    $searchResult = "";

    require("../lib/status.inc.php"); //Fehlermeldungen/Hinweise werden hier geladen
  

//Gibt ein Array zurück mit den erlaubten ToDo IDs die der Benutzer sehen darf.
function getAllowedToDoIDs($conn,$user_ID){  
    $ToDoIDArray = [];
    $query = $conn->query("SELECT category_category_id FROM user_has_categories where user_ID = '$user_ID'");
    while($row = $query->fetch_array()){
       $category_id = $row["category_category_id"];
       $selectToDoID = $conn->query("SELECT ID from to_do where category_category_id = '$category_id'");
       while($toDO_ID = $selectToDoID->fetch_array()){
            array_push($ToDoIDArray,$toDO_ID["ID"]);} }
    return $ToDoIDArray;
}

//Gibt dem Namen einer Kategorie zurück anhand der Kategorien_ID
function getCategoryName($category_id){ global $conn;
    $sql = "SELECT Name from category where category_id = '$category_id'";
    $result = $conn->query($sql);
    $row = $result->fetch_array();
    return $row['Name'];
}

 //Generiert den Fälligkeitstext, wurde ein ToDo archiviert steht nur "erledigt"
function getFinishDate_Text($FinishDate, $archieved = null) { 
    $FinishTimeStamp = strtotime($FinishDate);$today = time();
    $difference = $FinishTimeStamp - $today;
    $difference = $difference/60/60/24+1; //Berechnung für wie viele Tage
    if($archieved == null){
    if ($difference > 1) {//Zukunft
    return "<p class=\"text-success\"> in ".(floor($difference))." Tagen </p>";}
    else if ($difference <= 1 && $difference > 0){ //Heute
    return "<p class=\"text-success\">Heute</p>";}
    else if($difference < 0){//Vergangenheit
    return "<p class=\"text-danger\">"."Seit ".(abs(floor($difference)))." Tagen! </p>";}}
    if($archieved = "true"){return "<p class=\"text-success\">erledigt</p>";}//Falls archiviert
}

//Ist der Archieve Value auf True wird der Text durchgestrichen zurückgegeben
function archievIfExists($text,$archieved){ 
    if($archieved == "true")
    {return "<del>".$text."</del>";}
    else{return $text;};
}

//Generiert die Aktionbutton falls der Benutzer das ToDO Erstellt hat.
function generateActionButton($user_ID,$OwnerUser_ID,$ToDo_ID){
    if($user_ID == $OwnerUser_ID){
        return "<td>".
        "<a href=\"manage_todo?archive=".$ToDo_ID."\">". //Button für das Archivieren
        "<span class=\"fa-stack\">".
        "<i class=\"fa fa-square fa-stack-2x\"></i>".
        "<i class=\"fa fa fa-archive fa-stack-1x fa-inverse\"></i>". //Archivieren Icon
        "</span></a>".
        "<a href=\"manage_todo?ID=".$ToDo_ID."\">". //Button für das Editieren/Ansehen
        "<span class=\"fa-stack\">".
        "<i class=\"fa fa-square fa-stack-2x\"></i>".
        "<i class=\"fa fa fa-pencil fa-stack-1x fa-inverse\"></i>". //Editieren/Ansehen Icon
        "</span></a>".
        "<a href=\"manage_todo?DEL=".$ToDo_ID."\">". //Button für das Archivieren
         "<span class=\"fa-stack\">".
               "<i class=\"fa fa-square fa-stack-2x\"></i>".
                "<i class=\"fa fa-trash-o fa-stack-1x fa-inverse\"></i>". //Lösch Icon
        "</span></a></td>";
    }else{return "<td>Anderer Benutzer</td>";} //Falls der Benutzer das ToDo nicht erstellt hat.
}


//Erstellt ein ToDo Beitrag, ruft dazu alle nötigen Funktionen zum Anzeigen auf, wird nach Priorität und nach Datum sortiert.
function viewToDos($conn)
{
    $user_ID = $_SESSION['id'];
    $query = $conn->query("SELECT * FROM to_do where archieve = 'false' order by priority DESC, finishDate ASC");
    $AllowedToDoIDArray = getAllowedToDoIDs($conn,$user_ID);
    while($row = $query->fetch_array()){
        if(!in_array($row['ID'],$AllowedToDoIDArray)){continue;};
        echo
        "<tr data-toggle=\"collapse\" data-target=\"#".$row['ID']."\" class=\"accordion-toggle\">".
        "<td><a href=\"#\"><span class=\"fa-stack\"><i class=\"fa fa-square fa-stack-2x\"></i>".
        "<i class=\"fa fa fa-eye fa-stack-1x fa-inverse\"></i></span></a></td>".
        "<td>".$row['ID']."</td>".
        "<td>".$row['priority']."</td>".
        "<td>".getCategoryName($row['category_category_id'])."</td>".
         "<td><strong>".($row['title'])."</strong></td>".
         "<td>".(date("d.m.Y", strtotime($row['addDate'])))."</td>".
         "<td>".getFinishDate_Text($row['finishDate'])."</td>".
         "<td><div class=\"progress\">".
         "<div class=\"progress-bar progress-bar\" role=\"progressbar\" style=\"width: ".$row["status"]."%\" aria-valuenow=\"".$row["status"]."\" aria-valuemin=\"0\" aria-valuemax=\"100\">".$row["status"]."%</div>".
         "</div></td>".(generateActionButton($user_ID,$row['user_ID'],$row['ID']))."</tr>".
         "<tr><td colspan=\"9\" class=\"hiddenRow\"><div id=\"".$row['ID']."\" class=\"accordian-body collapse\"><strong>Beschreibung: </strong><br />"
        .$row["text"]."<hr></div></td></tr>";
}}

//Erstellt der Inhalt der angefordeten Suche,ruft dazu alle nötigen Funktionen zum Anzeigen auf, 
//wird nach Priorität und nach Datum sortiert, es werden nur ToDos angezeigt mit dennen der Nutzer eine Kategorie teilt
function searchToDo($conn,$SearchString){
    $searchCount = 0;
    $user_ID = $_SESSION['id'];
    $searchedTitle = "%".$SearchString."%";$searchedText = "%".$SearchString."%";
    $AllowedToDoIDArray = getAllowedToDoIDs($conn,$user_ID);
    $ToDo_ID=$priority= $title= $text= $addDate= $finishDate=$archieve=$category_name=$status=$ToDouser_ID = "";
    $stmt = $conn->prepare("SELECT ID,priority,addDate,title,text,addDate,finishDate,archieve,status,user_ID, category.name as category_name FROM category JOIN to_do ON category.category_id = to_do.category_category_id where title like (?) OR text like (?) order by priority DESC, finishDate ASC");
    $stmt->bind_param('ss', $searchedTitle, $searchedText);
    $stmt->execute();
    $stmt->bind_result($ToDo_ID,$priority, $addDate, $title, $text,$addDate, $finishDate,$archieve,$status,$ToDouser_ID,$category_name);
    while ($stmt->fetch()) {
        if(!in_array($ToDo_ID,$AllowedToDoIDArray)){continue;}
        $searchCount++;
        echo   
        "<tr data-toggle=\"collapse\" data-target=\"#".$ToDo_ID."\" class=\"accordion-toggle\">".
        "<td><a href=\"#\"><span class=\"fa-stack\"><i class=\"fa fa-square fa-stack-2x\"></i>".
        "<i class=\"fa fa fa-eye fa-stack-1x fa-inverse\"></i></span></a></td>".
        "<td>".archievIfExists($ToDo_ID,$archieve)."</td>".
        "<td>".archievIfExists($priority,$archieve)."</td>".
        "<td>".archievIfExists($category_name,$archieve)."</td>".
         "<td><strong>".archievIfExists($title,$archieve)."</strong></td>".
         "<td>".(date("d.m.Y", strtotime($addDate)))."</td>".
         "<td>".getFinishDate_Text($finishDate,$archieve)."</td>".
         "<td><div class=\"progress\">".
         "<div class=\"progress-bar progress-bar\" role=\"progressbar\" style=\"width: ".$status."%\" aria-valuenow=\"".$status."\" aria-valuemin=\"0\" aria-valuemax=\"100\">".$status."%</div>".
         "</div></td>".(generateActionButton($user_ID,$ToDouser_ID,$ToDo_ID))."</tr>".
         "<tr><td colspan=\"9\" class=\"hiddenRow\"><div id=\"".$ToDo_ID."\" class=\"accordian-body collapse\"><strong>Beschreibung: </strong><br />"
        .$text."<hr></div></td></tr>";      
    }
}

    //Falls ein Beitrag gesucht wird
    if($_SERVER['REQUEST_METHOD'] == 'GET') //Wird ein GET-Request gesendet
    { if(isset($_GET["ToDoSearch"]))
    {$searchString = htmlspecialchars($_GET["ToDoSearch"]);}}   
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
            <button type="submit" class="btn btn-primary"><span class="glyphicon glyphicon-home"></span> Home | ToDoVerwaltung</button></form>
             </div>  </div>
              
             <div class="nav navbar-nav navbar-right">
                        <div class="panel-buttons">

            <form style="display: inline" action="manage_todo" method="get">
            <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-inbox"></span> ToDo erstellen</button></form>


            <form style="display: inline" action="../logout" method="get">
            <button type="submit" class="btn btn-default "><span class="glyphicon glyphicon-log-out"></span> Log out</button></form>

                       </div>
             </div>
            </div>
    </nav>
    
    <div class="well well-sm"><h4>Userpanel / ToDoVerwaltung</h4>  </div>
    <div class="container-fluid">
		

            <!-- Hier werden Fehlermeldungen/Meldungen ausgegeben -->
        <div class="form-group"><div class="<?php echo $alert_class_name;?>" role="alert"><?php echo $alert_message; ?></div></div>
   

        <!-- Suchleiste -->
<form action="index.php" method="GET"> 
  <div class="row"><div class="col-xs-1 col-md-3"><div class="input-group">
        <input type="text" class="form-control" placeholder="Alle ToDos durchsuchen" name="ToDoSearch"/>
        <div class="input-group-btn">
          <button class="btn btn-primary" type="submit">
            <span class="glyphicon glyphicon-search"></span>
          </button>
        </div></div></div></div>
</form><br>

        <table class="table table-striped">
                <thead>
                    <tr>
                        <th style = "width: 4%"></th>
                        <th style = "width: 4%">ID:</th>
                        <th style = "width: 8%">Priorität:</th>
                        <th style = "width: 9%">Kategorie:</th>
                        <th style = "width: 22%">Titel:</th>						
                        <th style = "width: 12%">Erstellt:</th>
                        <th style = "width: 10%">Fällig:</th>
                        <th style = "width: 16%">Status:</th>
                        <th style = "width: 15%">Aktion:</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($searchString)){viewToDos($conn);}
                          else{searchToDo($conn, $searchString);}?>
                </tbody>
            </table> </div>
            
        </body>
</html>