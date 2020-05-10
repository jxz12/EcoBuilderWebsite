<?php
try
{
    include 'security.php';
    $rsa = InitRSA();
    $index = $_POST["level_index"];
    $first_rank = $_POST["first_rank"];
    $num_scores = $_POST["num_scores"];

    $sql = InitSQL();

    // first get an array of levels that we will need to populate

    $leaderboards = '';
    $filter = InitProfanityFilter();

    // get top n rows
    $stmt = $sql->prepare('SELECT username, score FROM leaderboards WHERE level_index=? ORDER BY score DESC, play_id ASC LIMIT ?,?');
    $stmt->bind_param('iii', $index, $first_rank, $num_scores);
    $stmt->execute();
    $stmt->bind_result($highname, $highscore);
    if ($stmt->fetch() == TRUE)
    {
        // TODO: show same rank number for same scores
        $rank = $first_rank + 1;
        $leaderboards .= Ordinal($rank) . ': ' . $filter->obfuscateIfProfane($highname) . ' ' . number_format($highscore);
        while ($stmt->fetch() == TRUE)
        {
            $rank += 1;
            $leaderboards .= "\n" . Ordinal($rank) . ': ' . $filter->obfuscateIfProfane($highname) . ' ' . number_format($highscore);
        }
    }
    $stmt->close();
    
    $sql->close();
    echo $leaderboards;
}
catch (error $e)
{
    http_response_code(500);
}