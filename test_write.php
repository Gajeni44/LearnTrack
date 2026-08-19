<?php
$test_data = ["status" => "System is working!"];
if (file_put_contents('data/test.json', json_encode($test_data))) {
    echo "Success: Dockerfile permissions are working!";
} else {
    echo "Error: Check folder permissions.";
}
?>