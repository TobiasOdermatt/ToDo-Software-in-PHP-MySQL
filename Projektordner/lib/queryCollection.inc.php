<?php
//Admin Panel Querys
   function delUserData ($conn, $user_id){ //Löscht jede Zuweisung sowie ToDo die dem Nutzer angehören.
    $stmt = $conn->prepare("DELETE FROM user_has_categories WHERE user_ID = (?)");
    $stmt->bind_param("s",$user_id);
    $stmt->execute();
    $stmt = $conn->prepare("DELETE FROM to_do WHERE user_ID = (?)");
    $stmt->bind_param("s",$user_id);
    $stmt->execute();
  }
  
  
  function delUSER($user_id,$conn){ //Löscht einen User wenn die ID mitgegeben wurd
    if(existUserID($user_id,$conn)){ //Überprüft ob die ID existiert
      delUserData($conn,$user_id); //Nutzerdaten löschen
      $stmt = $conn->prepare("DELETE FROM user WHERE ID = (?)");
      $stmt->bind_param("s",$user_id);
      $stmt->execute();
      $stmt->store_result();
      header("location: ../admin/?status=delsuccess"); //War die Löschung Erfolgreich wird dies der Startseite mitgeteilt
    }else{header("location: ../admin/?status=delfail");}//War die Löschung nicht Erfolgreich wird dies der Startseite mitgeteilt
  }

  
  function usernameExists($username, $conn){ //Überprüft ob ein Nutzername in der Datenbank existiert returnt true oder false     
    $stmt = $conn->prepare("SELECT username FROM user WHERE username = (?)");
    $stmt->bind_param("s",$username);
    $stmt->execute();
    $stmt->store_result();
    $countRows = $stmt->num_rows;
    if($countRows >= 1){
      return true;}else{return  false;}
  }

  function editUSER($user_id,$conn){ //Lädt den Editmode | Werte des Nutzers werden geladen und der Seitentext ändert sich
    if(existUserID($user_id,$conn)){
      global $edit_firstname,$edit_lastname,$edit_username;
      $sql = "SELECT firstname,lastname,username from user where ID = '$user_id'";
      $result = $conn->query($sql);
      $row = $result->fetch_assoc();
      $edit_firstname = $row['firstname'];
      $edit_lastname = $row['lastname'];
      $edit_username = $row['username'];
    }
  }
  function countCategory($conn){ //Gibt die Anzahl der Kategorien zurück
    $sql = "SELECT COUNT(*) from category";
    $result = $conn->query($sql);
    $data =  $result->fetch_assoc();
    return $data['COUNT(*)'];
}

  function countUSER($conn){  //Gibt die Anzahl der Nutzer zurück
    $sql = "SELECT COUNT(*) from user where role = 'user'";
    $result = $conn->query($sql);
    $data =  $result->fetch_assoc();
    return $data['COUNT(*)'];
}
function existUserID($user_id,$conn){ //Überprüft ob eine User ID in der Datenbank existiert returnt true oder false
    global $error;
    if($user_id == 1){$error .= "Der Administrator kann nicht gelöscht oder bearbeitet werden."; return false;}
    $stmt = $conn->prepare("SELECT ID FROM user WHERE ID = (?)");
    $stmt->bind_param("s",$user_id);
    $stmt->execute();
    $stmt->store_result();
    $countRows = $stmt->num_rows;
    if($countRows >= 1){return true;}
    else{return false;};
  }

  function existCategoryID($ID,$conn){ //Überprüft ob die ID einer Kategorie  existiert
    $stmt = $conn->prepare("SELECT category_id FROM category WHERE category_id = (?)");
    $stmt->bind_param("i",$ID);
    $stmt->execute();
    $stmt->store_result();
    $countRows = $stmt->num_rows;
    if($countRows >= 1){return true;}
    else{return false;}
  }
?>