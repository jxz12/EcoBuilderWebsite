<?php
try
{
    include 'security.php';
    $rsa = InitRSA();
    $username = $_POST["username"];
    $password = Decrypt($rsa, $_POST["password"]);

    $sql = InitSQL();
    VerifyLogin($sql, $username, $password);

    // the highscores table should handle itself through a cascade in foreign key
    // playthroughs have no personal data so no need to delete from them
    // password resets should deal with themselves through cascades in foreign key
    // median cache will NOT be correct after this, but that is not a big problem

    // delete player itself (causes cascades)
    $stmt = $sql->prepare('DELETE FROM players WHERE username=?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->close();

    $sql->close();
}
catch (error $e)
{
    http_response_code(500);
}