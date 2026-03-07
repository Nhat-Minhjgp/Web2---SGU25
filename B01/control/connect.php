<?php
$sever='localhost';
$username='root';
$password='';
$database='b01_nhahodau';

$conn=new mysqli($sever,$username,$password,$database);
if($conn->connect_error){
    die("Kết nối thất bại: " . $conn->connect_error);
}
else{
    $conn->set_charset("utf8mb4");
    echo "Kết nối thành công!";
}