<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;

$login_error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'] ?? '';
  $senha = $_POST['senha'] ?? '';
  $auth = new AuthController();
  $err = $auth->login($email, $senha);
  if ($err !== null) {
    $login_error = $err;
  } else {
    // AuthController faz redirect em sucesso
    exit;
  }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LEO - Login</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <style>
    .login {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #e5e1e2;
      padding: 2rem;
    }
    .login__card {
      width: 100%;
      max-width: 420px;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 12px;
      box-shadow: 0 6px 24px rgba(0,0,0,.12);
      padding: 2rem;
    }
    .login__logo {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.5rem;
    }
    .login__logo-image {
      display: inline-block;
      width: 120px;
      height: 40px;
      background-color: #333;
      -webkit-mask-image: url('/assets/uploads/LEO.png');
      mask-image: url('/assets/uploads/LEO.png');
      -webkit-mask-repeat: no-repeat;
      mask-repeat: no-repeat;
      -webkit-mask-position: center;
      mask-position: center;
      -webkit-mask-size: contain;
      mask-size: contain;
    }
    .login__form {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }
    .login__label {
      font-size: .875rem;
      color: #666;
    }
    .login__input {
      width: 100%;
      padding: .75rem 1rem;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: .95rem;
      outline: none;
    }
    .login__btn {
      margin-top: .5rem;
      width: 100%;
      padding: .75rem 1rem;
      background-color: #01a02a;
      color: #fff;
      border-radius: 30px;
      font-size: .95rem;
      font-weight: 700;
      letter-spacing: .03em;
      transition: all .2s;
    }
    .login__btn:hover { background-color: #008a43; }
    .login__error { margin-top: .5rem; color: #900; font-size: .875rem; }
  </style>
</head>
<body>
  <main class="login" aria-label="Página de login">
    <section class="login__card" role="form">
      <div class="login__logo">
        <span class="login__logo-image" role="img" aria-label="LEO"></span>
      </div>
      <?php if ($login_error): ?>
        <div class="login__error" role="alert"><?php echo htmlspecialchars($login_error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
      <?php endif; ?>
      <form action="/login.php" method="post" class="login__form">
        <label for="email" class="login__label">Email</label>
        <input type="email" id="email" name="email" required class="login__input" autocomplete="email" placeholder="teste@teste.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : 'teste@teste.com'; ?>">

        <label for="senha" class="login__label">Senha</label>
        <input type="password" id="senha" name="senha" required class="login__input" autocomplete="current-password" placeholder="123456" value="<?php echo isset($_POST['senha']) ? htmlspecialchars($_POST['senha'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '123456'; ?>">

        <button type="submit" class="login__btn">Logar</button>
      </form>
    </section>
  </main>
</body>
</html>