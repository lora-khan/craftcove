<?php
const DB_HOST = "localhost";
const DB_USER = "root";
const DB_PASSWORD = "";
const DB_NAME = "craftcove2";

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if (!$conn) {
    echo "Error";
}
