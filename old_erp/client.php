<!DOCTYPE html>
<?php
require 'lib/nusoap.php'; 
$client = new nusoap_client("http://localhost/salt/service01.php?wsdl"); // creation of an instance for soap client
?>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <form>
            <label for="name">Name:</label><br />
            <input type="text" name="name" placeholder="Enter your name" ><br />
            <input type="submit" name="submit" value="Send" />
        </form>
        
        <?php
            if(isset($_POST['submit'])) {
                $name = $_POST['name'];

                $response = $client->call('getUserByName',array("name"=>$name));
                
                $result = json_decode($response, true);
                
                if($result!=null){
                    echo "<table>";
                    echo "<tr>";
                    echo "<th>ID</th>";
                    echo "<th>Name</th>";
                    echo "<th>Nachname</th>";
                    echo "<th>Email</th>";
                    echo "</tr>";
                    
                    foreach ($result as $key){
                        echo "<tr>";
                        echo "<td>$key[0]</td>";
                        echo "<td>$key[1]</td>";
                        echo "<td>$key[2]</td>";
                        echo "<td>$key[3]</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                else{
                    echo "<p>no data</p>";
                }
            }
        ?>
    </body>
</html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
