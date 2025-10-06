<?php
// Limpa tokens e redireciona
$opts = [
  'expires' => time() - 3600,
  'path' => '/',
  'httponly' => true,
  'samesite' => 'Lax',
  'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
];
if (isset($_COOKIE['jwt'])) {
  setcookie('jwt', '', $opts);
}
if (isset($_COOKIE['refresh'])) {
  setcookie('refresh', '', $opts);
}
header('Location: /login.php');
exit;
?>