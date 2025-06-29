<?php
use phpseclib\Crypt\RSA;
use mofodojodino\ProfanityFilter\Filter;

function InitRSA()
{
    set_include_path(get_include_path() . PATH_SEPARATOR . './phpseclib-2.0.23/phpseclib/');
    include 'Math/BigInteger.php';
    include 'Crypt/Hash.php';
    include 'Crypt/RSA.php';

    $rsa = new RSA();
    $rsa->loadKey(file_get_contents('./rsa.xml'), RSA::PRIVATE_FORMAT_XML);  
    //$rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
    return $rsa;
}
function Decrypt($rsa, $post)
{
    $encrypted = strrev(base64_decode($post));
    return $rsa->decrypt($encrypted);
}
function InitSQL()
{
    $creds = simplexml_load_file('./sql.xml');
    // mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $sql = new mysqli($creds->server, $creds->user, $creds->pass, $creds->dbName);
    if ($sql->connect_errno)
    {
        http_response_code(503);
        die();
    }
    return $sql;
}
function VerifyLogin($sql, $username, $password)
{
    // this is case insensitive for the username because of the database collation
    // but not for the password because it is hashed before entry!
    // fortunate coincidence :)
    $stmt = $sql->prepare('SELECT password_hash FROM players WHERE username=?;');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($hashed);
    if ($stmt->fetch() == FALSE)
    {
        http_response_code(401); // username does not exist
        die();
    }
    if (!password_verify($password, $hashed))
    {
        http_response_code(401); // password does not match
        die();
    }
    $stmt->close();
}
function InitProfanityFilter()
{
    set_include_path(get_include_path() . PATH_SEPARATOR . './profanity/');
    include 'Filter.php';
    $filter = new Filter('./profanity/profanities.php');
    return $filter;
}
function Ordinal($num)
{
    $ones = $num % 10;
    $tens = floor($num / 10) % 10;
    if ($tens == 1) {
        $suff = "th";
    } else {
        switch ($ones) {
            case 1 : $suff = "st"; break;
            case 2 : $suff = "nd"; break;
            case 3 : $suff = "rd"; break;
            default : $suff = "th";
        }
    }
    return $num . $suff;
}