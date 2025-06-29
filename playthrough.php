<?php
try
{
    include 'security.php';
    $rsa = InitRSA();
    $score = $_POST["score"];
    if ($score != Decrypt($rsa, $_POST["erocs"])) {
        // weed out some cheaters
        http_response_code(500);
        die();
    }

    $username = $_POST["username"];
    $password = Decrypt($rsa, $_POST["password"]);
    $index = $_POST["level_index"];
    $ticks = $_POST["datetime_ticks"];
    $matrix = $_POST["matrix"];
    $actions = $_POST["actions"];
    $device = isset($_POST["device"]) ? $_POST["device"] : null; // earlier versions may not set device

    $sql = InitSQL();
    VerifyLogin($sql, $username, $password);

    // insert into highscores if higher
    $stmt = $sql->prepare('INSERT INTO highscores (username, level_index, highscore) VALUES (?,?,?)
        ON DUPLICATE KEY UPDATE highscore = IF(VALUES(highscore)>highscore, VALUES(highscore), highscore)');
    $stmt->bind_param('sii', $username, $index, $score);
    $stmt->execute();
    $stmt->close();

    // insert into playthroughs
    $stmt = $sql->prepare("INSERT INTO playthroughs (username, level_index, datetime_ticks, score, matrix, actions, device) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param('siiisss', $username, $index, $ticks, $score, $matrix, $actions, $device);
    $stmt->execute();
    if ($stmt->affected_rows == 0) {
        http_response_code(503);
        die();
    }
    $stmt->close();

    $play_id = $sql->insert_id; // this should be the same because the sql connection is not shared

    // insert into leaderboards if higher
    $stmt = $sql->prepare('INSERT INTO leaderboards (username, level_index, score, play_id) VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE play_id = IF(VALUES(score)>score, VALUES(play_id), play_id),
                                score = IF(VALUES(score)>score, VALUES(score), score)');
    $stmt->bind_param('siii', $username, $index, $score, $play_id);
    $stmt->execute();
    $leaderboard_affected = $stmt->affected_rows;
    $stmt->close();

    if ($leaderboard_affected > 0)
    {
        // set median to not cached
        $stmt = $sql->prepare("INSERT INTO medians (level_index) VALUES (?)
            ON DUPLICATE KEY UPDATE is_cached=0");
        $stmt->bind_param('i', $index);
        $stmt->execute();
        $stmt->close();
    }

    $sql->close();
}
catch (error $e)
{
    http_response_code(500);
}