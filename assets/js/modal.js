/**
 * LEO Learning Platform - Modal Controller
 * Controle nativo de modal sem dependências externas
 */

(function() {
    'use strict';
    
    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        initModal();
    }
    
    /**
     * Inicializa o sistema de modal
     * Funcionalidades: abrir, fechar, overlay click, ESC key
     */
    function initModal() {
        const modal = document.getElementById('courseModal');
        if (!modal) return;
        
        const overlay = modal.querySelector('.modal__overlay');
        const closeBtn = modal.querySelector('.modal__close');
        const modalBtn = modal.querySelector('.modal__btn');
        
        /**
         * Abre o modal
         */
        function openModal() {
            modal.classList.add('modal--active');
            document.body.classList.add('modal-open');
            
            // Foco no botão de fechar para acessibilidade
            setTimeout(() => {
                closeBtn.focus();
            }, 100);
        }
        
        /**
         * Fecha o modal
         */
        function closeModal() {
            modal.classList.remove('modal--active');
            document.body.classList.remove('modal-open');
            // Persistir no backend para não abrir mais para este usuário
            try {
                fetch('/api/user/main-modal/close', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' } }).catch(function(){});
            } catch (_e) {}
            // Fallback local
            try { localStorage.setItem('modal_shown', '1'); } catch (e) {}
        }
        
        /**
         * Toggle do modal (abre se fechado, fecha se aberto)
         */
        function toggleModal() {
            if (modal.classList.contains('modal--active')) {
                closeModal();
            } else {
                openModal();
            }
        }
        
        // Event listener para o botão de fechar
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }
        
        // Event listener para clicar no overlay (fundo escuro)
        if (overlay) {
            overlay.addEventListener('click', closeModal);
        }
        
        // Event listener para o botão principal do modal
        if (modalBtn) {
            modalBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Rolagem suave para a seção de cursos
                var cursosSection = document.getElementById('cursos');
                if (cursosSection) {
                    try {
                        cursosSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } catch (_) {
                        window.location.hash = '#cursos';
                    }
                }
                // Fechar o modal após a ação
                closeModal();
            });
        }
        
        // Fechar modal com tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('modal--active')) {
                closeModal();
            }
        });
        
        // Expor funções globalmente para uso externo
        window.LEOModal = {
            open: openModal,
            close: closeModal,
            toggle: toggleModal
        };
        
        // Checar estado do modal via API por usuário logado, com fallback localStorage
        (function checkModalStateAndOpen(){
            fetch('/api/user/modal-state', { credentials: 'same-origin' })
                .then(function(res){ return res.ok ? res.json() : Promise.reject(new Error('http '+res.status)); })
                .then(function(data){
                    if (data && data.show_main_modal === true) {
                        openModal();
                    }
                })
                .catch(function(){
                    var shown = null;
                    try { shown = localStorage.getItem('modal_shown'); } catch (e) {}
                    if (shown !== '1') {
                        openModal();
                    }
                });
        })();
        
        // Log para debug (pode ser removido em produção)
        console.log('Modal inicializado. Use LEOModal.open() para abrir.');
    }
    
})();

/**
 * EXEMPLOS DE USO:
 * 
 * 1. Abrir modal ao clicar em um card de curso:
 *    document.querySelectorAll('.course-card__btn').forEach(btn => {
 *        btn.addEventListener('click', function(e) {
 *            e.preventDefault();
 *            LEOModal.open();
 *        });
 *    });
 * 
 * 2. Abrir modal programaticamente:
 *    LEOModal.open();
 * 
 * 3. Fechar modal programaticamente:
 *    LEOModal.close();
 * 
 * 4. Toggle modal:
 *    LEOModal.toggle();
 */
