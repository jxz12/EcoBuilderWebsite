<?php
try
{
    include 'security.php';
    $rsa = InitRSA();
    $level_index = $_POST["level_index"];
    $first_rank = $_POST["first_rank"];
    $num_scores = $_POST["num_scores"];

    $sql = InitSQL();

    // first get an array of levels that we will need to populate

    $leaderboards = '';
    $filter = InitProfanityFilter();

    // get top n rows
    $stmt = $sql->prepare('SELECT username, score FROM leaderboards WHERE level_index=? ORDER BY score DESC LIMIT ?,?');
    $stmt->bind_param('iii', $index, $first_rank, $n_scores);
    $stmt->execute();
    $stmt->bind_result($highname, $highscore);
    while ($stmt->fetch() == TRUE)
    {
        $leaderboards .= ',' . $filter->obfuscateIfProfane($highname) . ':' . $highscore;
    }
    $stmt->close();
    
    $sql->close();
    echo $leaderboards;
}
catch (error $e)
{
    http_response_code(500);
}