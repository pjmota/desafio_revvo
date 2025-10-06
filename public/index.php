<?php
require_once __DIR__ . '/../inc/db.php';
$user = current_user();
if (!$user) {
  header('Location: /login.php');
  exit;
}
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
    <?php
    $db_error = null;
    $slides = [];
    $cursosDb = [];
    $cursos = [];
    $isCursosFallback = true;
    try {
      require_once __DIR__ . '/../inc/db.php';
      if (extension_loaded('pdo_sqlite')) {
        $pdo = db();
        $slides = $pdo->query('SELECT * FROM slides ORDER BY criado_em ASC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
        $cursosDb = $pdo->query('SELECT * FROM cursos ORDER BY criado_em DESC')->fetchAll(PDO::FETCH_ASSOC);
      } else {
        $db_error = 'SQLite (pdo_sqlite) não está habilitado. Renderizando com dados vazios.';
      }
    } catch (Throwable $e) {
      $db_error = 'Erro ao carregar dados: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // Fallback para conteúdo estático se nenhum dado for retornado
    if (empty($slides)) {
      $slides = [
        [
          'titulo' => 'LOREM IPSUM',
          'descricao' => 'Aenean lacinia bibendum nulla sed consectetur. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.',
          'link' => '#',
          'imagem' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=1600&h=600&fit=crop'
        ],
        [
          'titulo' => 'LOREM IPSUM',
          'descricao' => 'Aenean lacinia bibendum nulla sed consectetur. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.',
          'link' => '#',
          'imagem' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1600&h=600&fit=crop'
        ],
        [
          'titulo' => 'LOREM IPSUM',
          'descricao' => 'Aenean lacinia bibendum nulla sed consectetur. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Morbi leo risus, porta ac consectetur ac, vestibulum at eros.',
          'link' => '#',
          'imagem' => 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=1600&h=600&fit=crop'
        ],
      ];
    }

    if (empty($cursos)) {
      $isCursosFallback = true;
      $cursos = [
        [
          'titulo' => 'PELLENTESQUE MALESUADA',
          'descricao' => 'Curabitur blandit tempus porttitor. Nulla vitae elit libero, a pharetra augue.',
          'link' => '#',
          'imagem' => '/assets/uploads/cards.jpg',
          'novo' => false
        ],
        [
          'titulo' => 'PELLENTESQUE MALESUADA',
          'descricao' => 'Curabitur blandit tempus porttitor. Nulla vitae elit libero, a pharetra augue.',
          'link' => '#',
          'imagem' => '/assets/uploads/cards.jpg',
          'novo' => true
        ],
        [
          'titulo' => 'PELLENTESQUE MALESUADA',
          'descricao' => 'Curabitur blandit tempus porttitor. Nulla vitae elit libero, a pharetra augue.',
          'link' => '#',
          'imagem' => '/assets/uploads/cards.jpg',
          'novo' => false
        ],
      ];
    }
    ?>

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