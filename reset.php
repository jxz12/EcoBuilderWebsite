<?php
// credit: https://johnmorrisonline.com/create-email-based-password-reset-feature-login-script/
// Check for tokens
$selector = filter_input(INPUT_GET, 'selector');
$validator = filter_input(INPUT_GET, 'validator');

if ( false !== ctype_xdigit( $selector ) && false !== ctype_xdigit( $validator ) ) :
?>
    <form action='resetted.php' method='post'>
        <input type='hidden' name='selector' value='<?php echo $selector; ?>'>
        <input type='hidden' name='validator' value='<?php echo $validator; ?>'>
        <input type='password' class='text' name='password' placeholder='Enter your new password' required>
        <input type='submit' class='submit' value='Submit'>
    </form>
<?php
endif;
?>