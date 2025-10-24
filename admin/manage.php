<?php
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../app/autoload.php';

$user = current_user();
if (!$user) {
    header('Location: /login.php');
    exit;
}

$pdo = db();

// Processar ações via Controller (MVC)
(new \App\Controllers\AdminController())->handlePost();

// Carregar dados via Repositórios
$coursesRepo = new \App\Repositories\CourseRepository();
$slideRepo = new \App\Repositories\SlideRepository();
$userRepo = new \App\Repositories\UserRepository();

$cursos = $coursesRepo->listAll();
$slides = $slideRepo->listAll();
$usuarios = $pdo->query('SELECT id, nome, email, avatar, is_admin, criado_em FROM usuarios ORDER BY criado_em DESC')->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Revvo</title>
  <link rel="stylesheet" href="/assets/css/main.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
  <link rel="stylesheet" href="/assets/css/modal.css">
</head>
<body>
<header class="header"><div class="container"><h1>Admin</h1><a href="/index.php" class="btn">Ver site</a></div></header>
<main class="container">
  <?php if (isset($_GET['status'])): ?>
    <?php if ($_GET['status'] === 'dup'): ?>
      <div class="alert alert-error">Email já cadastrado. Escolha outro.</div>
    <?php elseif ($_GET['status'] === 'ok'): ?>
      <div class="alert alert-success">Operação realizada com sucesso.</div>
    <?php elseif ($_GET['status'] === 'err'): ?>
      <div class="alert alert-error">Preencha os campos obrigatórios.</div>
    <?php elseif ($_GET['status'] === 'admin_guard'): ?>
      <div class="alert alert-error">Para remover o último administrador, defina outro usuário como administrador primeiro.</div>
    <?php endif; ?>
  <?php endif; ?>
  <div class="tabs" role="tablist" aria-label="Admin">
    <button class="tab active" role="tab" aria-selected="true" aria-controls="tab-cursos" id="tab-cursos-btn">Cursos</button>
    <button class="tab" role="tab" aria-selected="false" aria-controls="tab-slides" id="tab-slides-btn">Slides</button>
    <button class="tab" role="tab" aria-selected="false" aria-controls="tab-usuarios" id="tab-usuarios-btn">Usuários</button>
  </div>

  <section id="tab-cursos" class="tab-panel active" role="tabpanel" aria-labelledby="tab-cursos-btn">
    <h2>Cursos</h2>
    <form method="post" enctype="multipart/form-data" class="form-inline">
      <input type="hidden" name="current_tab" value="tab-cursos">
      <input type="hidden" name="action" value="create_course">
      <input type="text" name="titulo" placeholder="Título" required>
      <textarea name="descricao" placeholder="Descrição" rows="3" required></textarea>
      <input type="file" name="imagem" accept="image/*" required>
      <button class="btn" type="submit">Adicionar</button>
    </form>
    <div class="list">
      <?php if (empty($cursos)): ?>
        <p class="empty">Nenhum curso cadastrado ainda.</p>
      <?php else: ?>
        <?php foreach($cursos as $c): ?>
        <article class="list-item">
          <div class="list-thumb">
            <?php if (!empty($c['imagem'])): ?>
              <img src="<?= sanitize($c['imagem']) ?>" alt="<?= sanitize($c['titulo']) ?>">
            <?php endif; ?>
          </div>
          <div class="list-body">
            <h3><?= sanitize($c['titulo']) ?></h3>
            <p><?= sanitize($c['descricao']) ?></p>
            <div class="list-actions">
              <form method="post">
                <input type="hidden" name="action" value="delete_course">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button class="btn btn-danger" type="submit">Excluir</button>
              </form>
              <details>
                <summary>Editar</summary>
                <form method="post" enctype="multipart/form-data" class="form-inline">
                  <input type="hidden" name="current_tab" value="tab-cursos">
                  <input type="hidden" name="action" value="update_course">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <input type="text" name="titulo" placeholder="Título" value="<?= sanitize($c['titulo']) ?>" required>
                  <textarea name="descricao" placeholder="Descrição" rows="3" required><?= sanitize($c['descricao']) ?></textarea>
                  <input type="file" name="imagem" accept="image/*">
                  <button class="btn" type="submit">Salvar</button>
                </form>
              </details>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <section id="tab-slides" class="tab-panel" role="tabpanel" aria-labelledby="tab-slides-btn" hidden>
    <h2>Slides</h2>
    <?php if (count($slides) < 5): ?>
    <form method="post" enctype="multipart/form-data" class="form-inline">
      <input type="hidden" name="current_tab" value="tab-slides">
      <input type="hidden" name="action" value="create_slide">
      <input type="file" name="imagem" accept="image/*" required>
      <input type="text" name="titulo" placeholder="Título" required>
      <textarea name="descricao" placeholder="Descrição" rows="3" required></textarea>
      <input type="url" name="link" placeholder="Link do botão">
      <button class="btn" type="submit">Adicionar Slide</button>
    </form>
    <?php endif; ?>
    <div class="list">
      <?php foreach($slides as $s): ?>
      <article class="list-item">
        <div class="list-thumb">
          <img src="<?= sanitize($s['imagem']) ?>" alt="<?= sanitize($s['titulo']) ?>">
        </div>
        <div class="list-body">
          <h3><?= sanitize($s['titulo']) ?></h3>
          <p><?= sanitize($s['descricao']) ?></p>
          <a class="btn" href="<?= sanitize($s['link']) ?>" target="_blank" rel="noopener">Abrir</a>
          <div class="list-actions">
            <form method="post">
              <input type="hidden" name="action" value="delete_slide">
              <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
              <button class="btn btn-danger" type="submit">Excluir</button>
            </form>
            <details>
              <summary>Editar</summary>
              <form method="post" enctype="multipart/form-data" class="form-inline">
                <input type="hidden" name="current_tab" value="tab-slides">
                <input type="hidden" name="action" value="update_slide">
                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                <input type="file" name="imagem" accept="image/*">
                <input type="text" name="titulo" placeholder="Título" value="<?= sanitize($s['titulo']) ?>" required>
                <textarea name="descricao" placeholder="Descrição" rows="3" required><?= sanitize($s['descricao']) ?></textarea>
                <input type="url" name="link" placeholder="Link do botão" value="<?= sanitize($s['link']) ?>">
                <button class="btn" type="submit">Salvar</button>
              </form>
            </details>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section id="tab-usuarios" class="tab-panel" role="tabpanel" aria-labelledby="tab-usuarios-btn" hidden>
    <h2>Usuários</h2>
    <form method="post" enctype="multipart/form-data" class="form-inline">
       <input type="hidden" name="current_tab" value="tab-usuarios">
       <input type="hidden" name="action" value="create_user">
       <input type="text" name="nome" placeholder="Nome" required>
       <input type="email" name="email" placeholder="Email" required>
       <input type="password" name="senha" placeholder="Senha" required>
       <label class="checkbox"><input type="checkbox" name="is_admin" value="1"> Administrador</label>
       <input type="file" name="imagem" accept="image/*">
       <button class="btn" type="submit">Adicionar Usuário</button>
     </form>
     <div class="list">
       <?php if (empty($usuarios)): ?>
         <p class="empty">Nenhum usuário cadastrado ainda.</p>
       <?php else: ?>
         <?php foreach($usuarios as $u): ?>
         <article class="list-item">
           <div class="list-thumb">
             <?php $avatar = !empty($u['avatar']) ? sanitize($u['avatar']) : '/assets/uploads/avatar.avif'; ?>
             <img src="<?= $avatar ?>" alt="Avatar de <?= sanitize($u['nome']) ?>">
           </div>
           <div class="list-body">
              <h3><?= sanitize($u['nome']) ?><?= ((int)$u['is_admin'] === 1) ? ' (admin)' : '' ?></h3>
              <p><?= sanitize($u['email']) ?> <?= (isset($u['is_admin']) && (int)$u['is_admin'] === 1) ? '<span class="tag">Admin</span>' : '<span class="tag">Usuário</span>' ?></p>
              <details>
                <summary>Editar</summary>
                <form method="post" class="form-inline" enctype="multipart/form-data">
                  <input type="hidden" name="current_tab" value="tab-usuarios">
                  <input type="hidden" name="action" value="update_user">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <input type="text" name="nome" placeholder="Nome" value="<?= sanitize($u['nome']) ?>" required>
                  <input type="email" name="email" placeholder="Email" value="<?= sanitize($u['email']) ?>" required>
                  <label class="checkbox"><input type="checkbox" name="is_admin" value="1" <?= ((int)$u['is_admin'] === 1) ? 'checked' : '' ?>> Administrador</label>
                  <input type="file" name="imagem" accept="image/*">
                  <button class="btn" type="submit">Salvar</button>
                </form>
              </details>
            </div>
          </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
</main>
<script src="/assets/js/modal.js"></script>
<script>
  (function(){
    const tabBtns = Array.from(document.querySelectorAll('.tab'));
    const panels = Array.from(document.querySelectorAll('.tab-panel'));
    tabBtns.forEach(btn => btn.addEventListener('click', () => {
      tabBtns.forEach(b => { b.classList.toggle('active', b === btn); b.setAttribute('aria-selected', b === btn ? 'true' : 'false'); });
      panels.forEach(p => {
        const isTarget = p.id === btn.getAttribute('aria-controls');
        p.classList.toggle('active', isTarget);
        p.toggleAttribute('hidden', !isTarget);
      });
    }));

    const params = new URLSearchParams(window.location.search);
    const tabParam = params.get('tab');
    if (tabParam) {
      const targetBtn = tabBtns.find(btn => btn.getAttribute('aria-controls') === tabParam);
      if (targetBtn) targetBtn.click();
    }

    // Abrir popup de alerta quando houver status=admin_guard
    const statusParam = params.get('status');
    if (statusParam === 'admin_guard') {
      // Reutilizar HTML do modal com mensagem simples
      const alertModal = document.createElement('div');
      alertModal.className = 'modal';
      alertModal.id = 'adminGuardModal';
      alertModal.innerHTML = `
        <div class="modal__overlay"></div>
        <div class="modal__container">
          <div class="modal__content">
            <button class="modal__close" aria-label="Fechar modal">
              <svg class="modal__close-icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ff9275" stroke-width="3.2" stroke-linecap="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
            <div class="modal__body">
              <h2 class="modal__title">Atenção</h2>
              <p class="modal__description">Para remover o último administrador, defina outro usuário como administrador primeiro.</p>
              <button class="modal__btn small" type="button">Entendi</button>
            </div>
          </div>
        </div>
      `;
      document.body.appendChild(alertModal);
      // Abrir e wire de fechar usando o mesmo padrão do modal
      const overlay = alertModal.querySelector('.modal__overlay');
      const closeBtn = alertModal.querySelector('.modal__close');
      const okBtn = alertModal.querySelector('.modal__btn');
      function open() {
        alertModal.classList.add('modal--active');
        document.body.classList.add('modal-open');
        setTimeout(() => closeBtn && closeBtn.focus(), 100);
      }
      function close() {
        alertModal.classList.remove('modal--active');
        document.body.classList.remove('modal-open');
      }
      overlay && overlay.addEventListener('click', close);
      closeBtn && closeBtn.addEventListener('click', close);
      okBtn && okBtn.addEventListener('click', close);
      document.addEventListener('keydown', function(e){ if (e.key === 'Escape') close(); });
      open();
    }
  })();
</script>
</body>
</html>