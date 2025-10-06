<?php
require_once __DIR__ . '/../db.php';
$u = current_user();
$userName = htmlspecialchars($u['nome'] ?? 'Usuário', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$avatarPath = '/assets/uploads/avatar.avif';
$isAdmin = false;
if (is_array($u) && isset($u['id'])) {
  try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT avatar, is_admin FROM usuarios WHERE id = ?');
    $stmt->execute([(int)$u['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!empty($row['avatar'])) { $avatarPath = $row['avatar']; }
    $isAdmin = ((int)($row['is_admin'] ?? 0) === 1);
  } catch (Throwable $e) { /* ignore */ }
}
?>
<header class="header" role="banner">
  <div class="header__container">
    <div class="header__logo">
      <img class="header__logo-image" src="/assets/uploads/LEO.png" alt="Revvo" />
    </div>
    <form class="header__search" role="search" aria-label="Buscar cursos" onsubmit="return false;">
      <input type="search" class="header__search-input" placeholder="Buscar cursos..." aria-label="Buscar cursos">
      <button type="submit" class="header__search-btn" aria-label="Buscar">
        <svg class="header__search-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2" fill="none"/><path d="M20 20l-4-4" stroke="currentColor" stroke-width="2" fill="none"/></svg>
      </button>
    </form>
    <div class="header__user">
      <img src="<?= htmlspecialchars($avatarPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" alt="Foto do usuário" class="header__user-avatar">
      <div class="header__user-info">
        <span class="header__user-greeting">Seja bem-vindo</span>
        <span class="header__user-name"><?= $userName ?><?= $isAdmin ? ' (admin)' : '' ?></span>
      </div>
      <button class="header__user-dropdown" aria-label="Abrir menu do usuário">
        <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6" fill="currentColor"/></svg>
      </button>
      <div class="header__user-menu" role="menu" aria-label="Menu do usuário" aria-hidden="true">
        <button class="header__user-menu-item" role="menuitem" type="button">Profile</button>
        <?php if ($isAdmin): ?>
        <a class="header__user-menu-item" role="menuitem" href="/admin/manage.php">Admin</a>
        <?php endif; ?>
        <a class="header__user-menu-item" role="menuitem" href="/logout.php">Sair</a>
      </div>
    </div>
  </div>
</header>