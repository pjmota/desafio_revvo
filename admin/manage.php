<?php
require_once __DIR__ . '/../inc/db.php';

$pdo = db();

// CRUD Cursos (create/update/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create_course') {
        $stmt = $pdo->prepare('INSERT INTO cursos(titulo, descricao) VALUES (?, ?)');
        $stmt->execute([$_POST['titulo'] ?? '', $_POST['descricao'] ?? '']);
    } elseif ($action === 'update_course') {
        $stmt = $pdo->prepare('UPDATE cursos SET titulo=?, descricao=? WHERE id=?');
        $stmt->execute([$_POST['titulo'] ?? '', $_POST['descricao'] ?? '', (int)($_POST['id'] ?? 0)]);
    } elseif ($action === 'delete_course') {
        $stmt = $pdo->prepare('DELETE FROM cursos WHERE id=?');
        $stmt->execute([(int)($_POST['id'] ?? 0)]);
    } elseif ($action === 'create_slide') {
        $imgPath = upload_image($_FILES['imagem'] ?? []);
        if ($imgPath) {
            $stmt = $pdo->prepare('INSERT INTO slides(imagem, titulo, descricao, link) VALUES (?, ?, ?, ?)');
            $stmt->execute([$imgPath, $_POST['titulo'] ?? '', $_POST['descricao'] ?? '', $_POST['link'] ?? '#']);
        }
    } elseif ($action === 'delete_slide') {
        $stmt = $pdo->prepare('DELETE FROM slides WHERE id=?');
        $stmt->execute([(int)($_POST['id'] ?? 0)]);
    }
    header('Location: manage.php');
    exit;
}

$cursos = $pdo->query('SELECT * FROM cursos ORDER BY criado_em DESC')->fetchAll(PDO::FETCH_ASSOC);
$slides = $pdo->query('SELECT * FROM slides ORDER BY criado_em DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Revvo</title>
  <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<header class="header"><div class="container"><h1>Admin</h1><a href="/public/index.php" class="btn">Ver site</a></div></header>
<main class="container">
  <section>
    <h2>Cursos</h2>
    <form method="post" style="margin-bottom:1rem">
      <input type="hidden" name="action" value="create_course">
      <input type="text" name="titulo" placeholder="Título" required>
      <input type="text" name="descricao" placeholder="Descrição" required>
      <button class="btn" type="submit">Adicionar</button>
    </form>
    <div class="grid">
      <?php foreach($cursos as $c): ?>
      <article class="card">
        <h3><?= sanitize($c['titulo']) ?></h3>
        <p><?= sanitize($c['descricao']) ?></p>
        <form method="post">
          <input type="hidden" name="action" value="delete_course">
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <button class="btn" type="submit">Excluir</button>
        </form>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section>
    <h2>Slides</h2>
    <form method="post" enctype="multipart/form-data" style="margin-bottom:1rem">
      <input type="hidden" name="action" value="create_slide">
      <input type="file" name="imagem" accept="image/*" required>
      <input type="text" name="titulo" placeholder="Título" required>
      <input type="text" name="descricao" placeholder="Descrição" required>
      <input type="url" name="link" placeholder="Link do botão" required>
      <button class="btn" type="submit">Adicionar Slide</button>
    </form>
    <div class="grid">
      <?php foreach($slides as $s): ?>
      <article class="card">
        <img src="<?= sanitize($s['imagem']) ?>" alt="<?= sanitize($s['titulo']) ?>" style="max-width:100%">
        <h3><?= sanitize($s['titulo']) ?></h3>
        <p><?= sanitize($s['descricao']) ?></p>
        <a class="btn" href="<?= sanitize($s['link']) ?>" target="_blank" rel="noopener">Abrir</a>
        <form method="post">
          <input type="hidden" name="action" value="delete_slide">
          <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
          <button class="btn" type="submit">Excluir</button>
        </form>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
</main>
</body>
</html>