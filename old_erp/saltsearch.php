<html>
<head>
<title>Sales Order @ Albsale-Vlora</title>
<link rel="stylesheet" href="css/stili.css" />
</head>

<body>
<center><br />
<img src="image/logo.png" id="img1" />
<h2>Liste der Artikel</h2>
<img src="image/albsale01.jpg" width="650" />
<br /><br />
<?php
$link = mysqli_connect("localhost", "username", "password", "albsale-vlora"); 
  
if ($link === false) { 
    die("ERROR: Could not connect. "
                .mysqli_connect_error()); 
}

    $sql ="SELECT * FROM salt";
	$res = mysqli_query($link, $sql);

if (!$res) {
	die('<font color="red">Gabim i ndodhur ne pyetesorin $sql:</font>'.mysqli_error());
} else {
          echo"<table class='table1'>";
          echo "<tr>
                    <th><b>Nr:</br></th>
                    <th><b>Titel:</b></th>
                    <th><b>Hersteller:</b></th>
                    <th><b>Salzcode:</b></th>
                    <th><b>Aktien:</b></th>
                    <th><b>Einheit:</b></th>
                    <th><b>Preisproeinheit:</b></th>
                    <th><b>Währung:</b></th>
                </tr>";
            $i=0;
            while ($data = mysqli_fetch_array($res))
                {
                    echo "<tr>
                            <td><i>".++$i.".</i></td>
                            <td>". $data['title']."</td>
                            <td>". $data['producer']."</td>
                            <td>". $data['saltcode']."</td>
                            <td>". $data['stock']."</td>
                            <td>". $data['unit']."</td>
                            <td>". $data['priceperunit']."</td>
                            <td>". $data['currency']."</td>
                          </tr>";
                }
            echo"</table>";
        }
    mysqli_close($link);
?>
<br />
<font face="TimesNewRoman" color="blue">
<h3><a href="index.php">Zurück zur Startseite</a></h3>
<h3><a href="salesorder.php">Klicken Sie hier, um Artikel zu bestellen</a></h3>
</font>
&copy; Copyright <b>:: RealCore Group GmbH</b> 2024
</center>
</body>
</html>
