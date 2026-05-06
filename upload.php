<?php
$target_dir="uploads/";
$target_file=$target_dir.basename($_FILES["myfile"]
["name"]);

if(move_uploaded_file($_FILES["myfile"]["tmp_name"],
$target_file)) {
    echo "File Uploaded sucessfully.";
} else {
    echo "Sorry, there was an error uploading your file.";
}