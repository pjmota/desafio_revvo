<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Revvo - Cursos</title>
  <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
  <div id="modal" class="modal" aria-hidden="true" role="dialog" aria-labelledby="modal-title" aria-describedby="modal-desc">
    <div class="modal__dialog">
      <h2 id="modal-title">Bem-vindo!</h2>
      <p id="modal-desc">Este modal aparece apenas no primeiro acesso.</p>
      <button id="modal-close" class="btn" aria-label="Fechar">Fechar</button>
    </div>
  </div>

  <header class="header">
    <div class="container">
      <h1>Revvo Plataforma</h1>
      <nav class="nav" aria-label="Principal">
        <a href="#cursos">Cursos</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <section aria-label="Slideshow">
      <div class="slideshow" id="slideshow" tabindex="0" aria-live="polite"></div>
    </section>

    <section id="cursos" aria-label="Lista de cursos">
      <div class="grid" id="courses-grid"></div>
    </section>
  </main>

  <script src="/assets/js/main.js" defer></script>
</body>
</html>