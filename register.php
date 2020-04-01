<?php
try {
    include 'security.php';
    $rsa = InitRSA();
    $username = $_POST['username'];
    $password = Decrypt($rsa, $_POST['password']);
    $email = Decrypt($rsa, $_POST['email']);

    // these two are potentially set from someone who did not create an account at first
    $reversed = $_POST['reversed'];
    $highscores = $_POST['highscores'];

    $sql = InitSQL();

    $stmt = $sql->prepare('SELECT team FROM players WHERE username=?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($oldteam);
    if ($stmt->fetch() == TRUE && $oldteam != -1) {
        http_response_code(409); // conflict so already registered (check -1 for accounts that never passed gdpr)
        die();
    }
    $stmt->close();

    // TODO: check for 'ddos' if someone is trying to just fill out the database?
    if ($email == "") // ignore unspecified emails
    {
        $email = NULL;
    }
    else
    {
        $stmt = $sql->prepare('SELECT * FROM players WHERE email=?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->fetch() == TRUE) {
            http_response_code(409); // email conflict TODO: send different error?
            die();
        }
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $sql->prepare('INSERT INTO players (username, password_hash, email, reverse_drag, team) VALUES (?,?,?,?,-1)
        ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), 
                                email=VALUES(email),
                                reverse_drag=VALUES(reverse_drag)');
        // may have duplicate key if team=-1 so GDPR not passed
    $stmt->bind_param('sssi', $username, $hashed, $email, $reversed);
    $stmt->execute();
    $stmt->close();

    // in case the player registering has played before
    if ($highscores != NULL && $highscores != "")
    {
        $highscores = explode(';', $highscores);
        foreach ($highscores as $score)
        {
            $score = explode(':', $score);

            // insert high score (the row should never exist beforehand)
            $stmt = $sql->prepare('INSERT INTO highscores (username, level_index, highscore) VALUES (?,?,?)');
            $stmt->bind_param('sii', $username, $score[0], $score[1]);
            $stmt->execute();
            $stmt->close();
        }
    }

    $sql->close();
}
catch (Error $e)
{
    http_response_code(500);
}