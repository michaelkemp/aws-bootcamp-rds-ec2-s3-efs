<!DOCTYPE html>
<html>
<head>
    <title>Upload Images to S3</title>

    <style>
        img {
            width: 100%;
            height: auto;
        }
        figure {
          max-width: 50%;
          border: 2px solid black;
          padding: 10px;
        }
        figcaption {
          padding: 0.5em;
        }

    </style>

</head>
<body>

<?php

    $srvr = "kempy-mysql.crzc9xds3qkq.us-west-2.rds.amazonaws.com";
    $user = "dbuser";
    $pass = "XesAchollPanancedBleInts";
    $dbnm = "mydb";

    $conn = new mysqli($srvr, $user, $pass, $dbnm);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } 


    $sql = "SELECT * FROM images";
    $result = $conn->query($sql);

    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<figure>";
            echo "<a href='" .$row["image_s3_url"]. "' target='_blank'><img src='" .$row["thumb_s3_url"]. "'></a>";
            echo "<figcaption>" .htmlspecialchars($row["description"]). "</figcaption>";
            echo "</figure>";
        }
    } 

    $conn->close();

?>

<br>
<br>
<hr>

<form action="upload.php" method="post" enctype="multipart/form-data">
    <table>
        <tr>
            <td>Image File<br><input type="file" name="image" id="image"></td>
        </tr>
        <tr>
            <td>Image Description<br><textarea id="description" name="description" rows="10" cols="50"></textarea></td>
        </tr>
        <tr>
            <td></td>
            <td><input type="submit" value="Upload Image" name="submit"></td>
        </tr>
</form>

</body>
</html>