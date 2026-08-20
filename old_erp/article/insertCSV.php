<html>
<title>CSV FIle Insertion</title>
<head><link rel="stylesheet" href="style.css"></head>

<body>
 <div class="topnav">
  <a class="active" href="http://localhost/saltapi/welcome.html">Home</a>
  <a href="http://localhost/saltapi/new_record.php">Add Record</a>
  <a href="http://localhost/saltapi/modifyrecord.php">Modify Record</a>
  <a href="http://localhost/saltapi/removeRecord.php">Remove Record</a>
  <a href="http://localhost/saltapi/view.php">View Records</a>
  <a href="http://localhost/saltapi/index.html">StudentDB</a>
</div>
<br>

<?php  
	require_once('mysql_conn.php');

	if (mysqli_connect_errno()) {    
		printf("Connect failed: %s\n", mysqli_connect_error());    
		exit();
	}
	
	echo $filename=$_FILES["file"]["name"];
	$ext=substr($filename,strrpos($filename,"."),(strlen($filename)-strrpos($filename,".")));
	if($ext=="csv"){
  		$file = fopen($filename, "r");
		     while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE)
		     {
		        $query = "INSERT INTO students
		(first_name, last_name, email, street, city, state, zip, phone, birth_date, sex, date_entered, lunch_cost) 
		VALUES('$emapData[0]', '$emapData[1]', '$emapData[2]', '$emapData[3]', '$emapData[4]', '$emapData[5]', '$emapData[6]', '$emapData[7]', '$emapData[8]', '$emapData[9]', CURDATE(), '$emapData[10]')";
				echo $query;
				mysqli_query($dbc,$query); 
				mysqli_close($dbc);
				}
         fclose($file);
         echo "CSV File has been successfully Imported.";
}
else {
    echo " Error reading CSV File ";
}
?>
</body>
</html>

