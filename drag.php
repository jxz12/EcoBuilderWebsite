<?php
try
{
    include 'security.php';
    $rsa = InitRSA();
    $username = $_POST["username"];
    $password = Decrypt($rsa, $_POST["password"]);
    $reversed = $_POST["reversed"];

    $sql = InitSQL();
    VerifyLogin($sql, $username, $password);

    $stmt = $sql->prepare('UPDATE players SET reverse_drag=? WHERE username=?');
    $stmt->bind_param('is', $reversed, $username);
    $stmt->execute();
    $stmt->close();
    $sql->close();
}
catch (error $e)
{
    http_response_code(500);
}