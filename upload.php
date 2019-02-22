<?php

    // ========== PHP AWS SDK ==========
    require 'vendor/autoload.php';

    use Aws\Credentials\Credentials;
    use Aws\Credentials\CredentialProvider;
    use Aws\Exception\CredentialsException;
    use Aws\S3\S3Client;
    use Aws\S3\Exception\S3Exception;

    $provider = CredentialProvider::defaultProvider();

    $s3 = new Aws\S3\S3Client([
            'region'  => 'us-west-2',
            'version' => 'latest',
            'credentials' => $provider
    ]);

    $bucket = 'kempy-bootcamp-bucket';


    // ========== UPLOAD TO IMAGES DIR ==========
    $target_dir = "images/";

    // ========== GIVE UNIQUE NAME ==========
    $guid = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    
  
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check !== false) {
            $mime = isset($check["mime"]) ? $check["mime"] : "";
            $name = strtolower(basename($_FILES["image"]["name"]));
            $extn = pathinfo($name,PATHINFO_EXTENSION);
            $file = $guid . "." . $extn;
            $target_file = $target_dir . $file;
            if ( $extn == "jpg" || $extn == "png" || $extn == "jpeg" || $extn == "gif" ) {
                // ========== SAVE IMAGE ==========
                move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
            
                // ========== RESIZE IMAGE ==========
                $thumb = $guid . "-small.jpg";
                $target_thumb = $target_dir . $thumb;
                $maxDim = 1024;
                $wide = isset($check[0]) ? $check[0] : 0;
                $high = isset($check[1]) ? $check[1] : 0;
                $ratio = $wide/$high;
                if ($ratio > 1) {
                    $new_width = $maxDim;
                    $new_height = $maxDim/$ratio;
                } else {
                    $new_width = $maxDim*$ratio;
                    $new_height = $maxDim;
                }
                $src = imagecreatefromstring(file_get_contents($target_file));
                $dst = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled( $dst, $src, 0, 0, 0, 0, $new_width, $new_height, $wide, $high );
                imagejpeg($dst, $target_thumb);
                imagedestroy($src);
                imagedestroy($dst);


                // ========== UPLOAD THUMBNAIL TO S3 ==========
                $s3->putObject(array(
                    'Bucket'=>$bucket,
                    'Key' => $thumb,
                    'SourceFile' => $target_thumb,
                    'StorageClass' => 'STANDARD',
                    'ACL' => 'public-read'
                ));
                
                // ========== RETRIEVE URL ==========
                $thumburl = $s3->getObjectUrl($bucket, $thumb);

                // ========== UPLOAD FULL FILE TO S3 ==========
                $s3->putObject(array(
                    'Bucket'=>$bucket,
                    'Key' => $file,
                    'SourceFile' => $target_file,
                    'StorageClass' => 'STANDARD',
                    'ACL' => 'public-read'
                ));
                $url = $s3->getObjectUrl($bucket, $file);

                // ========== WRITE TO DATABASE ==========
                $description = $_POST['description'];

                $srvr = "kempy-mysql.crzc9xds3qkq.us-west-2.rds.amazonaws.com";
                $user = "dbuser";
                $pass = "XesAchollPanancedBleInts";
                $dbnm = "mydb";

                $conn = new mysqli($srvr, $user, $pass, $dbnm);
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                } 

                $stmt = $conn->prepare('INSERT INTO images (image_name,image_type,image_s3_key,image_s3_url,thumb_s3_key,thumb_s3_url,description) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->bind_param("sssssss", $name, $mime, $file, $url, $thumb, $thumburl, $description);
                $stmt->execute();

                $stmt->close();
                $conn->close();
            }

        }

    }
    header('Location: /index.php');

?>