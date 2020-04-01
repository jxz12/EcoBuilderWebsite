<?php
try
{
    include 'security.php';
    $rsa = InitRSA();
    $username = $_POST["username"];
    $password = Decrypt($rsa, $_POST["password"]);
    $age = $_POST["age"];
    $gender = $_POST["gender"];
    $education =  $_POST["education"];

    $sql = InitSQL();
    VerifyLogin($sql, $username, $password);

    $stmt = $sql->prepare('UPDATE players SET age=?, gender=?, education=? WHERE username=?');
    $stmt->bind_param('iiis', $age, $gender, $education, $username);
    $stmt->execute();
    $stmt->close();
    $sql->close();
}
catch (error $e)
{
    http_response_code(500);
}