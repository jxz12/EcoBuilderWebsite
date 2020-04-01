<?php
try
{
    // credit: https://johnmorrisonline.com/create-email-based-password-reset-feature-login-script/
    include 'security.php';
    $rsa = InitRSA();
    $recipient = Decrypt($rsa, $_POST['recipient']);

    $sql = InitSQL();

    // try email or username (should only return one row at most because '@' is not allowed in username)
    $stmt = $sql->prepare('SELECT username, email FROM players WHERE username=? OR email=?');
    $stmt->bind_param('ss', $recipient, $recipient);
    $stmt->bind_result($username, $email);
    $stmt->execute();
    if ($stmt->fetch() == FALSE || $email == '')
    {
        http_response_code(409); // no username or (matched) email
        exit();
    }
    $stmt->close();

    // Create tokens
    $selector = bin2hex(random_bytes(8));
    $token = random_bytes(32);

    // Create url from tokens (hard coded to make it safe)
    $url = 'http://localhost/ecobuilder/reset.php?' . http_build_query(['selector'=>$selector, 'validator'=>bin2hex($token)]);
    // $url = 'https://www.ecobuildergame.org/Beta/reset.php?' . http_build_query(['selector'=>$selector, 'validator'=>bin2hex($token)]);

    // Token expiration
    $time = new DateTime('NOW');

    // Quit if email already sent and within time limit
    $stmt = $sql->prepare('SELECT * FROM password_reset WHERE username=? AND expires>=?');
    $current_ticks = $time->format('U');
    $stmt->bind_param('ss', $username, $current_ticks);
    $stmt->execute();
    if ($stmt->fetch() == TRUE) {
        http_response_code(412); // token already there
        die();
    }
    $stmt->close();

    // Insert/update reset token into database
    $token_hash = hash('sha256', $token);
    $time->add(new DateInterval('PT01H')); // 1 hour
    $expires_ticks = $time->format('U');
    $stmt = $sql->prepare('INSERT INTO password_reset (username, selector, token, expires) VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE selector=VALUES(selector), token=VALUES(token), expires=VALUES(expires)');
    $stmt->bind_param('ssssi', $username, $selector, $token_hash, $expires_ticks);
    $stmt->execute();
    $stmt->close();

    // Send the email
    $subject = 'Your EcoBuilder password reset link';

    // content
    $message = "Hello ".$username.",";
    $message .= "\n\nWe received a password reset request. The link to reset your password is below. ";
    $message .= "If you did not make this request, then please ignore this email.";
    $message .= "\n\nYour username is '" . $username . "'"; 
    $message .= "\nHere is your password reset link:";
    $message .= "\n" . $url;
    $message .= "\n\nThanks!";

    // content
    $admin_name = 'EcoBuilder Admin';
    $admin_email = 'ecobuilder@imperial.ac.uk';
    $headers = 'From: ' . $admin_name . ' <' . $admin_email . '>\r\n';
    $headers .= 'Reply-To: ' . $admin_email . '\r\n';
    $headers .= 'Content-type: text/html\r\n';

    // Send email
    $sent = mail($email, $subject, $message, $headers);
}
catch (error $e)
{
    http_response_code(500);
}