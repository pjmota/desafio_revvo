<!-- Modal exclusivo para adicionar cursos dinamicamente -->
<div class="modal courses-modal" id="coursesModal">
  <div class="modal__overlay"></div>
  <div class="modal__container">
    <button class="modal__close" aria-label="Fechar modal">
      <svg class="modal__close-icon" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ff9275" stroke-width="3.2" stroke-linecap="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>
    <div class="modal__content">
      <div class="modal__body courses-modal__body">
        <h2 class="modal__title courses-modal__title">Adicionar curso</h2>
        <p class="modal__description courses-modal__description">Selecione um curso para adicionar à grade. Cursos já exibidos foram ocultados.</p>
        <div id="courses-modal-list" class="courses-modal__list" role="list"></div>
      </div>
    </div>
  </div>
</div>