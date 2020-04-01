<?php
try
{
    include 'security.php';
    $rsa = InitRSA();
    $sql = InitSQL();

    // first get an array of indices that we will need to populate
    // needs to be one at a time because of not being able to use FLOOR in LIMIT
    $stmt = $sql->prepare('SELECT level_index FROM medians WHERE is_cached=1');
    $stmt->execute();
    $stmt->bind_result($index);
    $todos = array();
    while ($stmt->fetch())
    {
        array_push($todos, $index);
    }
    $stmt->close();
    foreach ($todos as $todo)
    {
        $stmt = $sql->prepare('UPDATE medians
            SET num_scores=(SELECT COUNT(*) FROM leaderboards WHERE level_index=?)
            WHERE level_index=?');
        $stmt->bind_param('ii', $todo, $todo);
        $stmt->execute();
        $stmt->close();

        $stmt = $sql->prepare('SELECT num_scores FROM medians WHERE level_index=?');
        $stmt->bind_param('i', $todo);
        $stmt->execute();
        $stmt->bind_result($num_scores);
        $stmt->fetch();
        $stmt->close();

        $median_row = floor($num_scores/2);
        $stmt = $sql->prepare('UPDATE medians
            SET median_score=(SELECT score FROM leaderboards
                              WHERE level_index=?
                              ORDER BY score DESC, play_id ASC
                              LIMIT ?,1),
                is_cached=1
            WHERE level_index=?');
        $stmt->bind_param('iii', $todo, $median_row, $todo);
        $stmt->execute();
        $stmt->close();
    }
    
    // get the actual scores
    $stmt = $sql->prepare('SELECT level_index, median_score FROM medians');
    $stmt->execute();
    $stmt->bind_result($index, $median);
    $medians = '';
    while ($stmt->fetch())
    {
        $medians .= $index . ':' . $median . ',';
    }

    if ($medians != '')
    {
        // remove final comma
        $medians = substr($medians, 0, -1);
    }
    echo $medians;
}
catch (error $e)
{
    http_response_code(500);
}