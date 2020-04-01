<?php
try
{
    // credit: https://johnmorrisonline.com/create-email-based-password-reset-feature-login-script/
    include 'security.php';
    $selector = $_POST['selector'];
    $validator = $_POST['validator'];
    $password = $_POST['password'];

    // Get tokens
    $sql = InitSQL();
    $stmt = $sql->prepare('SELECT token, username FROM password_reset WHERE selector=? AND expires>=?');
    $time = (new DateTime('NOW'))->format('U');
    $stmt->bind_param('ii', $selector, $time);
    $stmt->bind_result($token, $username);
    $stmt->execute();
    if ($stmt->fetch() == FALSE) {
        // no reset requested
        echo 'There was an error processing your request (Code 1). Please try requesting another reset email.';
        die();
    }
    $stmt->close();

    // validate tokens, fail silently if token does not match
    $calc = hash('sha256', hex2bin($validator));
    if (!hash_equals($token, $calc))
    {
        echo 'There was an error processing your request (Code 2). Please try requesting another reset email.';
        die();
    }

    // check if player exists
    $stmt = $sql->prepare('SELECT * FROM players WHERE username=?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    if ($stmt->fetch() == FALSE) {
        // somehow no username that matches in database, even though token worked
        echo 'There was an error processing your request (Code 3). Please try requesting another reset email.';
        die();
    }
    $stmt->close();

    // Update password
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $sql->prepare('UPDATE players SET password_hash=? WHERE username=?');
    $stmt->bind_param('ss', $hashed, $username);
    $stmt->execute();
    $stmt->close();

    // Delete any existing password reset this email
    $stmt = $sql->prepare('DELETE FROM password_reset WHERE username=?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->close();

    echo 'Password updated successfully.';
}
catch (error $e)
{
    http_response_code(500);
}