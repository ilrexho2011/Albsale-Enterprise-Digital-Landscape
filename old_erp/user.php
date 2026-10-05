<html>
<head>
<title>Benutzer auflisten</title>
<link rel="stylesheet" href="css/stili.css" />
</head>
<body>
<center><br />
<img src="image/logo.png" id="img1" />
<h2>Liste der Benutzer:</h2>
<img src="image/albsale04.jpg" width="520" />
<br /><br />
<?php
$link = mysqli_connect("localhost", "username", "password", "albsale-vlora"); 
  
if ($link === false) { 
    die("ERROR: Could not connect. "
                .mysqli_connect_error()); 
} 
  
$sql = "SELECT * FROM user";
	$res = mysqli_query($link, $sql);

if (!$res) {
	die('<font color="red">Bei einer $sql-Abfrage ist ein Fehler aufgetreten:</font>'.mysqli_error());
} else {
           echo"<table class='table1'>";
           echo "<tr>
                    <th>ID:</th>
                    <th>Name:</th>
                    <th>Nachname:</th>
                    <th>ZINN:</th>
                    <th>Email:</th>
                    <th>Tel:</th>
                </tr>";

           while($data = mysqli_fetch_array($res))
                    {
                        echo "<tr>
                                <td>". $data['id']."</td>
                                <td>". $data['name']."</td>
                                <td>". $data['surname']."</td>
                                <td>". $data['ZINN']."</td>
                                <td>". $data['email']."</td>
                                <td>". $data['tel']."</td>
                              </tr>";
                    }
           echo"</table>";
        }
?>
<h3><a href="index.php">Zurück zur Startseite</a></h3>
<h3><a href="salesorder.php">Klicken Sie hier, um zu bestellen</a></h3>

&copy; Copyright <b>:: RealCore Group GmbH</b> 2024
</center>
</body>
</html>
