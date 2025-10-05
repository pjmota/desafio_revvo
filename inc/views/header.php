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
      <img src="/assets/uploads/avatar.avif" alt="Foto do usuário" class="header__user-avatar">
      <div class="header__user-info">
        <span class="header__user-greeting">Bem-vindo</span>
        <span class="header__user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuário', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
      </div>
      <button class="header__user-dropdown" aria-label="Abrir menu do usuário">
        <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9l6 6 6-6" fill="currentColor"/></svg>
      </button>
      <div class="header__user-menu" role="menu" aria-label="Menu do usuário" aria-hidden="true">
        <button class="header__user-menu-item" role="menuitem" type="button">Profile</button>
        <a class="header__user-menu-item" role="menuitem" href="/logout.php">Sair</a>
      </div>
    </div>
  </div>
</header>