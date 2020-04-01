<?php
try
{
    include 'security.php';
    $rsa = InitRSA();
    $username = $_POST["username"];
    $password = Decrypt($rsa, $_POST["password"]);
    $team = $_POST["team"];
    $newsletter = $_POST["newsletter"];

    $sql = InitSQL();
    VerifyLogin($sql, $username, $password);

    $stmt = $sql->prepare('UPDATE players SET team=?, newsletter=? WHERE username=?');
    $stmt->bind_param('iis', $team, $newsletter, $username);
    $stmt->execute();
    $stmt->close();
    $sql->close();
}
catch (error $e)
{
    http_response_code(500);
}