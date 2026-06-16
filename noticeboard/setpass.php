<?php
$pass = 'admin123';
$hash = password_hash($pass, PASSWORD_BCRYPT);

require_once 'config/database.php';
$db = getDB();
$db->prepare("DELETE FROM users WHERE email='admin@college.edu'")->execute();
$db->prepare("INSERT INTO users (name,email,password,role,dept,approved) VALUES (?,?,?,'admin','Administration',1)")
   ->execute(['Admin User','admin@college.edu',$hash]);

echo "Done! Hash: " . $hash;
echo "<br>Now login with admin@college.edu / admin123";
?>