<?php
try
{
    include 'security.php';
    $index = $_POST["level_index"];
    $score = $_POST["score"];

    $sql = InitSQL();
    $stmt = $sql->prepare('SELECT COUNT(*) FROM (SELECT * FROM leaderboards WHERE level_index=? AND score>? LIMIT 10000) AS foo');
    $stmt->bind_param('ii', $index, $score);
    $stmt->execute();
    $stmt->bind_result($rank);
    $stmt->fetch();
    $stmt->close();

    if ($rank >= 10000) {
        echo '>10000th';
    } else {
        echo Ordinal($rank+1);
    }
}
catch (error $e)
{
    // echo($e->getMessage());
    http_response_code(500);
}