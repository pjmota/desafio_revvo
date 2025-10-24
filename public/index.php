<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Repositories\UserRepository;
use App\Controllers\PublicController;

$userRepo = new UserRepository();
$user = $userRepo->getCurrentUser();
if (!$user) {
  header('Location: /login.php');
  exit;
}

$publicController = new PublicController();
$home = $publicController->home();
$db_error = $home['db_error'] ?? null;
$slides = $home['slides'] ?? [];
$cursosDb = $home['cursosDb'] ?? [];
$cursos = $home['cursos'] ?? [];
$isCursosFallback = $home['isCursosFallback'] ?? true;
$userName = (string)($user['nome'] ?? 'Usuário');
$profile = $userRepo->getProfileById((int)$user['id']);
$avatarPath = !empty($profile['avatar'] ?? '') ? (string)$profile['avatar'] : '/assets/uploads/avatar.avif';
$isAdmin = ((int)($profile['is_admin'] ?? 0) === 1);
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LEO - Cursos</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/modal.css">
</head>
<body>
  <?php require_once __DIR__ . '/../inc/views/modal.php'; ?>
  <?php require_once __DIR__ . '/../inc/views/courses-modal.php'; ?>
  <?php require_once __DIR__ . '/../inc/views/course-detail-modal.php'; ?>

  <?php require_once __DIR__ . '/../inc/views/header.php'; ?>

  <main class="container">

    <?php if ($db_error): ?>
      <div class="alert" role="alert" style="margin:1rem 0;padding:0.75rem;border:1px solid #e1a; background:#fee; color:#900;">
        <?= $db_error ?>
      </div>
    <?php endif; ?>

    <?php require_once __DIR__ . '/../inc/views/hero.php'; ?>

    <?php require_once __DIR__ . '/../inc/views/courses.php'; ?>
  </main>

  <?php require_once __DIR__ . '/../inc/views/footer.php'; ?>

  <script>
    window.__SLIDES__ = <?= json_encode($slides, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    window.__CURSOS_DB__ = <?= json_encode($cursosDb, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    window.__CURSOS_FALLBACK__ = <?= json_encode($cursos, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    window.__CURSOS_IS_FALLBACK__ = <?= $isCursosFallback ? 'true' : 'false' ?>;
  </script>
  <script src="/assets/js/main.js" defer></script>
  <script src="/assets/js/modal.js" defer></script>
</body>
</html>