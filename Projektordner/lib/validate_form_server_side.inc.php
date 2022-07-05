<?php
//Dieses Script ist für die vollständige Validierung der Formfelder dieses Projekt verantwortlich
//Falls die Validierungsbestimmungen geändert werden, können Sie hier mit CTR + F nach dem Namen des
//Formfeldes suchen und bearbeiten

//Validiert alle Felder von der manage ToDo Page
function validateToDo($conn)
{
  global $error, $ToDo_title, $ToDo_text, $ToDo_priority, $ToDo_finishDate, $status, $ToDo_category_ID;
  // Titel vorhanden, mindestens 1 Zeichen und maximal 45 Zeichen lang
  if (isset($_POST['title']) && !empty(trim($_POST['title'])) && strlen(trim($_POST['title'])) <= 45) {
    // Spezielle Zeichen Escapen > Script Injection verhindern
    $ToDo_title = htmlspecialchars(trim($_POST['title']));
  } else {
    // Ausgabe Fehlermeldung
    $error .= "Geben Sie bitte einen korrekten ToDo Titel ein.<br />";
  }

  // Beschreibung/Text vorhanden, mindestens 1 Zeichen und maximal 300 zeichen lang
  if (isset($_POST['text']) && !empty(trim($_POST['text'])) && strlen(trim($_POST['text'])) <= 300) {
    // Spezielle Zeichen Escapen > Script Injection verhindern
    $ToDo_text = str_replace("\n", "<br>", htmlspecialchars(trim($_POST['text'])));
  } else {
    // Ausgabe Fehlermeldung
    $error .= "Geben Sie bitte eine korrekte Beschreibung ein.<br />";
  }

  // Priorität maximal 5 mindestens 1
  if (isset($_POST['priority']) && ($_POST['priority']) <= 5 && ($_POST['priority']) >= 0) {
    $ToDo_priority = (int)($_POST['priority']);
  } else {
    // Ausgabe Fehlermeldung
    $error .= "Geben Sie bitte eine Priorität ein.<br />";
  }

  if (isset($_POST['category_ID'])) {
    //Überprüft ob eine Zuweisung vorhanden ist.
    function AssignExist($conn, $categoryID, $user_ID){ 
      $stmt = $conn->prepare("SELECT * FROM user_has_categories WHERE category_category_id = (?) AND user_ID = (?)");
      $stmt->bind_param("ii",$categoryID,$user_ID);
      $stmt->execute();
      $stmt->store_result();
      $countRows = $stmt->num_rows;
      if($countRows >= 1){ //Wurde ein Eintrag gefunden true wenn nicht false
      return true;}else{return false;}
      }
    //Überprüft ob der Benutzer die Berechtigung hat ein ToDo unter dieser Kategorie abzuspeichern
    if (AssignExist($conn, $_POST['category_ID'], $_SESSION["id"])) {
      $ToDo_category_ID = (int)$_POST['category_ID'];
    } else {
      $error .= "Sie haben keine Berechtigung ein ToDo unter dieser Kategorie zu erstellen.<br />";
    }
  } else {
    // Ausgabe Fehlermeldung
    $error .= "Geben Sie bitte eine Kategorie ein.<br />";
  }
  //Falls ein Status geschrieben wurde
  if (isset($_POST['status']) && ($_POST['status']) <= 100 && ($_POST['status']) >= 0) {
    $status = (int)($_POST['status']);
  } else {
    $error .= "Geben Sie bitte einen Status an. <br />";
  }

  //Falls ein Datum geschrieben wurde
  if (isset($_POST['date']) && !empty($_POST['date'])) {
    //Datum wird aufgeteilt
    $date = str_replace(".", "/", $_POST['date']);
    $date = str_replace("-", "/", $_POST['date']);
    list($month, $day, $year) = explode('/', $date);
    //Überprüft ob das Datum das entsprechende Format hat
    if (checkDate($month, $day, $year)) {
      //Datum wird für die weitergabe in die Datenbank "zusammengebaut"
      $ToDo_finishDate = $year . "." . $month . "." . $day;
      //Falls das Datumformat ungültig ist
    } else {
      $error .= "Bitte geben Sie ein gültiges Datum ein. Format Jahr/Monat/Tag <br />";
    }
  } else {
    $error .= "Geben Sie bitte ein Fälligkeitsdatum ein. <br />";
  }
} //Falls das Datumfeld nicht ausgefüllt wurde.





//Validiert die Formfelder für die Login Seite
function validatelogin()
{
  global $error, $username, $password;
  // username
  if (!empty(trim($_POST['username']))) {

    $username = trim($_POST['username']);

    // prüfung benutzername
    if (!preg_match("/(?=.*[a-z])(?=.*[A-Z])[a-zA-Z]{6,}/", $username) || strlen($username) > 30) {
      $error .= "Der Benutzername entspricht nicht dem geforderten Format.<br />";
    }
  } else {
    $error .= "Geben Sie bitte den Benutzername an.<br />";
  }
  // password
  if (!empty(trim($_POST['password']))) {
    $password = trim($_POST['password']);
    // passwort gültig?
    if (!preg_match("/(?=^.{8,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/", $password)) {
      $error .= "Das Passwort entspricht nicht dem geforderten Format.<br />";
    }
  } else {
    $error .= "Geben Sie bitte das Passwort an.<br />";
  }
}

//Validiert die manage_user Formfelder
function validateUser()
{
  global $error, $firstname, $lastname, $username, $password;

  // vorname vorhanden, mindestens 1 Zeichen und maximal 30 Zeichen lang
  if (isset($_POST['firstname']) && !empty(trim($_POST['firstname'])) && strlen(trim($_POST['firstname'])) <= 30) {
    // Spezielle Zeichen Escapen > Script Injection verhindern
    $firstname = htmlspecialchars(trim($_POST['firstname']));
  } else {
    // Ausgabe Fehlermeldung
    $error .= "Geben Sie bitte einen korrekten Vornamen ein.<br />";
  }

  // nachname vorhanden, mindestens 1 Zeichen und maximal 30 zeichen lang
  if (isset($_POST['lastname']) && !empty(trim($_POST['lastname'])) && strlen(trim($_POST['lastname'])) <= 30) {
    // Spezielle Zeichen Escapen > Script Injection verhindern
    $lastname = htmlspecialchars(trim($_POST['lastname']));
  } else {
    // Ausgabe Fehlermeldung
    $error .= "Geben Sie bitte einen korrekten Nachnamen ein.<br />";
  }
  // benutzername vorhanden, mindestens 6 Zeichen und maximal 30 zeichen lang
  if (isset($_POST['username']) && !empty(trim($_POST['username'])) && strlen(trim($_POST['username'])) <= 30) {
    $username = trim($_POST['username']);
    // entspricht der benutzername unseren vogaben (minimal 6 Zeichen, Gross- und Kleinbuchstaben)
    if (!preg_match("/(?=.*[a-z])(?=.*[A-Z])[a-zA-Z]{6,}/", $username)) {
      $error .= "Der Benutzername entspricht nicht dem geforderten Format.<br />";
    }
  } else {
    // Ausgabe Fehlermeldung
    $error .= "Geben Sie bitte einen korrekten Benutzernamen ein.<br />";
  }

  // passwort vorhanden, mindestens 8 Zeichen
  if (isset($_POST['password']) && !empty(trim($_POST['password']))) {
    $password = trim($_POST['password']);
    //entspricht das passwort unseren vorgaben? (minimal 8 Zeichen, Zahlen, Buchstaben, keine Zeilenumbrüche, mindestens ein Gross- und ein Kleinbuchstabe)
    if (!preg_match("/(?=^.{8,}$)((?=.*\d+)(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/", $password)) {
      $error .= "Das Passwort entspricht nicht dem geforderten Format.<br />";
    }
    $password = password_hash($password, PASSWORD_DEFAULT);
  } else {
    // Ausgabe Fehlermeldung
    $error .= "Geben Sie bitte ein korrektes Password ein.<br />";
  }
}


//Validiert die manage_category
function validateCategory()
{
  global $category, $error;
  if (isset($_POST['category']) && !empty(trim($_POST['category'])) && strlen(trim($_POST['category'])) <= 255) {
    // Spezielle Zeichen Escapen > Script Injection verhindern
    $category = htmlspecialchars(trim($_POST['category']));
  } else {
    // Ausgabe Fehlermeldung
    $error .= "Geben Sie bitte einen korrekten Kategoriebezeichnung an.<br />";
  }
}
?>