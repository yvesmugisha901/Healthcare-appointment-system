<?php
// Change this password to whatever you want your admin password to be
$password = 'Yves123';

// Generate the hash
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Output the hashed password
echo "Password: $password<br>";
echo "Hashed Password: $hashedPassword";
?>
