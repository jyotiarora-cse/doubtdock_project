<?php
$servername="localhost";
$username="root";
$password="";
$database="doubtdock";
//create connection
$conn=new mysqli($servername,$username,$password,$database);
if($conn-> connect_error){
    die("connection failed:".$conn->connect_error);
}
/*if(isset($_POST['user'])&& isset($_POST['pass'])&& isset($_POST['name'])){
echo $_POST['user'];
echo'<br>' .$_POST['pass'];
echo '<br>'.$_POST['name'];
}
else{
    echo"required past data ismissing.";
}*/
?>