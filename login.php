<?php
try
{
    include 'security.php';
    $rsa = InitRSA();
    $username = $_POST["username"];
    $password = Decrypt($rsa, $_POST["password"]);

    $sql = InitSQL();
    VerifyLogin($sql, $username, $password);

    // get team and reverse_drag
    $stmt = $sql->prepare('SELECT team, reverse_drag FROM players WHERE username=?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($team, $reversed);
    $stmt->fetch();
    $stmt->close();
    if ($team == -1) // catch accounts that did not pass GDPR
    {
        http_response_code(401); // username 'does not exist', as GDPR not passed
        die();
    }
    
    // compose the stored highscores
    $player = $team.';'.$reversed;

    $stmt = $sql->prepare('SELECT level_index, highscore FROM highscores WHERE username=?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($index, $highscore);
    while ($stmt->fetch() == TRUE)
    {
        $player .= ';'.$index.':'.$highscore;
    }
    $stmt->close();
    $sql->close();

    echo $player;
}
catch (error $e)
{
    http_response_code(500);
}