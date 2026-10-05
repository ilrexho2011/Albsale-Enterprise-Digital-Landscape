<html>
<head>
<title>Abmeldung des Benutzers</title>
<link rel="stylesheet" href="css/stili.css" />
</head>
<body>
<center><br />
<img src="image/logo.png" id="img1" />
<font face="Arial" color="#000055">
<h2>Abmeldung des Benutzers</h2>
</font>
<img src="image/vlora01.jpg" width="450" />
<br /><br />
<form action="unregister.php" method="POST" id="forma">

<font face="Arial">
<h4>Geben Sie Ihren ZINN Kod ein:</h4><input type="text" name="ZINN" /><br />
</font><br /><br />
<input type="submit" value="Einreichen" id="button" />
</form>

<?php
$link = mysqli_connect("localhost", "username", "password", "albsale-vlora"); 
  
if ($link === false) { 
    die("ERROR: Could not connect. "
                .mysqli_connect_error()); 
} 

// Überprüfen wir, ob die Variable deklariert und ihr ein Wert zugewiesen wurde
if (isset($_POST['ZINN'])){

	$ZINN=$_POST['ZINN'];

    // Der Fragebogen ist vorbereitet
	$query ="DELETE FROM user WHERE ZINN='$ZINN'";

    // Der Fragebogen ist aktiviert
	$rezultati = mysqli_query($link, $query);

    // Das Ergebnis wird in der DB überprüft
if (!$rezultati) {
	die('<font color="red">Bei der Abmeldung ist ein Fehler aufgetreten $query:</font>'.mysqli_error());
}

	// mysql_query($query);
	echo '<font color="red">Der Benutzer wurde erfolgreich abgemeldet</font>';
    
	// Die Verbindung zur MySQL-Datenbank wird geschlossen
	mysqli_close($link);
}
?>
<br />
<h3><a href="index.php">Zurück zur Startseite</a></h3>
<br />
&copy; Copyright <b>:: RealCore Group GmbH</b> 2024
</center>
</body>
</html>
