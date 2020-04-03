<?php
try
{
    include 'security.php';
    $rsa = InitRSA();

    $username = $_POST["username"];
    $password = Decrypt($rsa, $_POST["password"]);
    $index = $_POST["level_index"];

    $sql = InitSQL();
    VerifyLogin($sql, $username, $password);

    // first get an array of levels that we will need to populate

    $nearby = '';
    $filter = InitProfanityFilter();
    die("TODO");

    // get top n rows
    $stmt = $sql->prepare('SELECT username, score FROM leaderboards WHERE level_index=? ORDER BY score DESC, play_id ASC LIMIT ?,?');
    $stmt->bind_param('iii', $index, $first_rank, $num_scores);
    $stmt->execute();
    $stmt->bind_result($highname, $highscore);
    $rank = $first_rank + 1;
    while ($stmt->fetch() == TRUE)
    {
        $leaderboards .= $rank . '. ' . $filter->obfuscateIfProfane($highname) . ' ' . number_format($highscore) . "\n";
        $rank += 1;
    }
    $stmt->close();
    
    $sql->close();
    echo $leaderboards;
}
catch (error $e)
{
    http_response_code(500);
}