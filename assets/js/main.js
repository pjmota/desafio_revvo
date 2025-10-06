/**
 * LEO Learning Platform - Main JavaScript
 * Carrossel nativo sem dependências externas
 */

(function() {
    'use strict';
    
    // Carregar cursos do banco (sem fallback automático), apenas para adicionar via botão
    var COURSES_DB = Array.isArray(window.__CURSOS_DB__) ? window.__CURSOS_DB__.map(function(c, idx) {
        return {
            id: (typeof c.id === 'number' ? c.id : (idx + 1)),
            nome: c.titulo || '',
            descricao: c.descricao || '',
            imagem: c.imagem || '',
            criado_em: c.criado_em || ''
        };
    }) : [];

    var COURSES_DB = Array.isArray(window.__CURSOS_DB__) ? window.__CURSOS_DB__.map(function(c, idx) {
        return {
            id: (typeof c.id === 'number' ? c.id : (idx + 1)),
            nome: c.titulo || '',
            descricao: c.descricao || '',
            imagem: c.imagem || '',
            criado_em: c.criado_em || ''
        };
    }) : [];

    // IDs dos cursos visíveis em tela: se fallback ativo, começar vazio; senão, preencher com até 7 cursos do banco
    var isFallbackFlag = !!window.__CURSOS_IS_FALLBACK__;
    var setupDone = false;
    try { setupDone = localStorage.getItem('courses_setup_done') === '1'; } catch (_) {}
    var isFallback = isFallbackFlag && !setupDone;
    var visibleCourseIds = (isFallback ? [] : COURSES_DB
        .slice()
        .sort(function(a, b) { return a.id - b.id; })
        .slice(0, 7)
        .map(function(c) { return c.id; }));

    // IDs marcados como recém-adicionados (para exibir badge "NOVO")
    var newlyAddedCourseIds = [];

    function getCourseById_DB(id) {
        for (var i = 0; i < COURSES_DB.length; i++) {
            if (COURSES_DB[i].id === id) return COURSES_DB[i];
        }
        return null;
    }
    function isCourseNew(course) {
        var dt = course && course.criado_em;
        if (!dt || typeof dt !== 'string') return false;
        try {
            var iso = dt.replace(' ', 'T');
            var created = new Date(iso.endsWith('Z') ? iso : iso + 'Z');
            if (isNaN(created.getTime())) {
                created = new Date(iso);
            }
            if (isNaN(created.getTime())) return false;
            var diffMs = Date.now() - created.getTime();
            return diffMs <= 24 * 60 * 60 * 1000;
        } catch (e) {
            return false;
        }
    }
    function openAddCourseModal() {
        var modal = document.getElementById('coursesModal');
        if (!modal) return;
        var overlay = modal.querySelector('.modal__overlay');
        var closeBtn = modal.querySelector('.modal__close');
        var listEl = document.getElementById('courses-modal-list');
        var descEl = modal.querySelector('.courses-modal__description');
        if (listEl) {
            listEl.innerHTML = '';
            var available = COURSES_DB.filter(function(c) { return visibleCourseIds.indexOf(c.id) === -1; });
            if (available.length === 0) {
                if (descEl) { descEl.style.display = 'none'; }
                var emptyMsg = document.createElement('p');
                emptyMsg.className = 'courses-modal__description';
                emptyMsg.textContent = 'Nenhum curso disponível para adicionar.';
                listEl.appendChild(emptyMsg);
            } else {
                if (descEl) { descEl.style.display = ''; }
                available.sort(function(a, b) { return a.id - b.id; }).forEach(function(c) {
                    var btn = document.createElement('button');
                    btn.className = 'courses-modal__btn';
                    btn.type = 'button';
                    btn.textContent = c.nome + ' (ID ' + c.id + ')';
                    btn.addEventListener('click', async function() {
                        if (visibleCourseIds.indexOf(c.id) === -1) {
                            visibleCourseIds.push(c.id);
                            // marcar como recém-adicionado para badge
                            if (newlyAddedCourseIds.indexOf(c.id) === -1) {
                                newlyAddedCourseIds.push(c.id);
                            }
                            // Persistir no backend por usuário
                            try {
                                await fetch('/api/homepage-courses', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                                    credentials: 'same-origin',
                                    body: JSON.stringify({ course_id: c.id })
                                });
                            } catch (_) {}
                            // Garantir persistência local e sair de fallback
                            try {
                                localStorage.setItem('courses_setup_done', '1');
                                localStorage.setItem('visible_course_ids', JSON.stringify(visibleCourseIds.slice().sort(function(a,b){return a-b;})));
                            } catch (_) {}
                            isFallback = false;
                            renderCourses();
                        }
                        closeAddCourseModal();
                    });
                    listEl.appendChild(btn);
                });
            }
        }
        modal.classList.add('modal--active');
        document.body.classList.add('modal-open');
        if (closeBtn) closeBtn.focus();
        if (overlay) overlay.addEventListener('click', closeAddCourseModal, { once: true });
        if (closeBtn) closeBtn.addEventListener('click', closeAddCourseModal, { once: true });
    }

    function closeAddCourseModal() {
        var modal = document.getElementById('coursesModal');
        if (!modal) return;
        modal.classList.remove('modal--active');
        document.body.classList.remove('modal-open');
    }

    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    async function init() {
        initModal();
        renderHero();
        // Restaurar localStorage como base
        try {
            var saved = localStorage.getItem('visible_course_ids');
            if (saved) {
                var arr = JSON.parse(saved);
                if (Array.isArray(arr)) {
                    visibleCourseIds = arr.filter(function(x){ return typeof x === 'number'; });
                }
            }
        } catch (_) {}
        // Buscar persistência por usuário no backend e sobrepor se existir
        try {
            var resp = await fetch('/api/homepage-courses', {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            if (resp && resp.ok) {
                var data = await resp.json();
                if (data && Array.isArray(data.course_ids) && data.course_ids.length > 0) {
                    visibleCourseIds = data.course_ids.map(function(x){ return parseInt(x, 10); }).filter(function(x){ return !isNaN(x); });
                    try {
                        localStorage.setItem('courses_setup_done', '1');
                        localStorage.setItem('visible_course_ids', JSON.stringify(visibleCourseIds.slice().sort(function(a,b){return a-b;})));
                    } catch (_) {}
                    isFallback = false;
                }
            }
        } catch (_) {}
        renderCourses();
        initCarousel();
        initUserMenu();
    }
    
    /**
     * Inicializa o carrossel do hero
     * Funcionalidades: navegação por setas, indicadores (bolinhas) e autoplay
     */
    function initCarousel() {
        const carousel = document.querySelector('.hero__carousel');
        if (!carousel) return;
        
        const slides = carousel.querySelectorAll('.hero__slide');
        const indicators = carousel.querySelectorAll('.hero__indicator');
        const prevBtn = carousel.querySelector('.hero__arrow--prev');
        const nextBtn = carousel.querySelector('.hero__arrow--next');
        
        let currentSlide = 0;
        let autoplayInterval = null;
        const autoplayDelay = 5000; // 5 segundos
        
        /**
         * Atualiza o slide ativo
         * @param {number} index - Índice do slide a ser exibido
         */
        function goToSlide(index) {
            // Remover classe active de todos os slides e indicadores
            slides.forEach(slide => slide.classList.remove('hero__slide--active'));
            indicators.forEach(indicator => indicator.classList.remove('hero__indicator--active'));
            
            // Adicionar classe active ao slide e indicador corretos
            slides[index].classList.add('hero__slide--active');
            indicators[index].classList.add('hero__indicator--active');
            
            currentSlide = index;
        }
        
        /**
         * Avança para o próximo slide
         */
        function nextSlide() {
            const next = (currentSlide + 1) % slides.length;
            goToSlide(next);
        }
        
        /**
         * Volta para o slide anterior
         */
        function prevSlide() {
            const prev = (currentSlide - 1 + slides.length) % slides.length;
            goToSlide(prev);
        }
        
        /**
         * Inicia o autoplay
         */
        function startAutoplay() {
            stopAutoplay(); // Limpar qualquer intervalo existente
            autoplayInterval = setInterval(nextSlide, autoplayDelay);
        }
        
        /**
         * Para o autoplay
         */
        function stopAutoplay() {
            if (autoplayInterval) {
                clearInterval(autoplayInterval);
                autoplayInterval = null;
            }
        }
        
        /**
         * Reinicia o autoplay após interação do usuário
         */
        function resetAutoplay() {
            stopAutoplay();
            startAutoplay();
        }
        
        // Event listeners para os botões de navegação
        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                prevSlide();
                resetAutoplay();
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                nextSlide();
                resetAutoplay();
            });
        }
        
        // Event listeners para os indicadores
        indicators.forEach(function(indicator, index) {
            indicator.addEventListener('click', function() {
                goToSlide(index);
                resetAutoplay();
            });
        });
        
        // Navegação por teclado (setas esquerda/direita)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                prevSlide();
                resetAutoplay();
            } else if (e.key === 'ArrowRight') {
                nextSlide();
                resetAutoplay();
            }
        });
        
        // Pausar autoplay quando o mouse está sobre o carrossel
        carousel.addEventListener('mouseenter', stopAutoplay);
        carousel.addEventListener('mouseleave', startAutoplay);
        
        // Pausar autoplay quando a aba não está visível
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopAutoplay();
            } else {
                startAutoplay();
            }
        });
        
        // Suporte para touch/swipe em dispositivos móveis
        let touchStartX = 0;
        let touchEndX = 0;
        const minSwipeDistance = 50;
        
        carousel.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        carousel.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });
        
        function handleSwipe() {
            const swipeDistance = touchEndX - touchStartX;
            
            if (Math.abs(swipeDistance) > minSwipeDistance) {
                if (swipeDistance > 0) {
                    // Swipe para direita - slide anterior
                    prevSlide();
                } else {
                    // Swipe para esquerda - próximo slide
                    nextSlide();
                }
                resetAutoplay();
            }
        }
        
        // Iniciar autoplay
        startAutoplay();
        
        // Log para debug (pode ser removido em produção)
        console.log('Carrossel inicializado com', slides.length, 'slides');
    }
    
    function initModal() {
        var modal = document.getElementById('modal');
        var closeBtn = document.getElementById('modal-close');
        if (!modal || !closeBtn) return;
        var shown = null;
        try { shown = localStorage.getItem('modal_shown'); } catch (e) {}
        if (!shown) {
            modal.setAttribute('aria-hidden', 'false');
        }
        closeBtn.addEventListener('click', function() {
            modal.setAttribute('aria-hidden', 'true');
            try { localStorage.setItem('modal_shown', '1'); } catch (e) {}
        });
    }

    function initUserMenu() {
        var container = document.querySelector('.header__user');
        var toggleBtn = document.querySelector('.header__user-dropdown');
        var menu = document.querySelector('.header__user-menu');
        if (!container || !toggleBtn || !menu) return;
        
        function open() {
            menu.classList.add('header__user-menu--open');
            menu.setAttribute('aria-hidden', 'false');
            document.addEventListener('click', onDocClick);
        }
        
        function close() {
            menu.classList.remove('header__user-menu--open');
            menu.setAttribute('aria-hidden', 'true');
            document.removeEventListener('click', onDocClick);
        }
        
        function onDocClick(e) {
            if (!container.contains(e.target)) {
                close();
            }
        }
        
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (menu.classList.contains('header__user-menu--open')) {
                close();
            } else {
                open();
            }
        });
        
        // Evitar fechar ao clicar nos itens, que por enquanto não têm ação
        menu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    function renderHero() {
        var slidesData = Array.isArray(window.__SLIDES__) ? window.__SLIDES__.slice(0, 5) : [];
        var slidesEl = document.getElementById('hero-slides');
        var indicatorsEl = document.getElementById('hero-indicators');
        if (!slidesEl || !indicatorsEl) return;
    
        slidesEl.innerHTML = '';
        indicatorsEl.innerHTML = '';
    
        slidesData.forEach(function(s, idx) {
            var slideEl = document.createElement('div');
            slideEl.className = 'hero__slide' + (idx === 0 ? ' hero__slide--active' : '');
    
            var imgEl = document.createElement('img');
            imgEl.className = 'hero__slide-image';
            imgEl.src = s.imagem || '';
            imgEl.alt = s.titulo || 'Slide';
            slideEl.appendChild(imgEl);
    
            var overlayEl = document.createElement('div');
            overlayEl.className = 'hero__slide-overlay';
            slideEl.appendChild(overlayEl);
    
            var contentEl = document.createElement('div');
            contentEl.className = 'hero__slide-content';
            contentEl.innerHTML = (
                '<h3 class="hero__slide-title">' + (s.titulo || '') + '</h3>' +
                '<p class="hero__slide-text">' + (s.descricao || '') + '</p>' +
                '<a class="hero__slide-btn" href="' + (s.link || '#cursos') + '">VER CURSOS</a>'
            );
            slideEl.appendChild(contentEl);
    
            slidesEl.appendChild(slideEl);
    
            var indEl = document.createElement('button');
            indEl.type = 'button';
            indEl.className = 'hero__indicator' + (idx === 0 ? ' hero__indicator--active' : '');
            indEl.setAttribute('aria-label', 'Ir para slide ' + (idx + 1));
            indicatorsEl.appendChild(indEl);
        });

        // Smooth scroll para a seção de cursos ao clicar em "VER CURSOS"
        var heroButtons = slidesEl.querySelectorAll('.hero__slide-btn');
        heroButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var cursosSection = document.getElementById('cursos');
                if (cursosSection) {
                    try {
                        cursosSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } catch (_) {
                        window.location.hash = '#cursos';
                    }
                }
            });
        });
    }
    
    function renderCourses() {
        var gridEl = document.getElementById('courses-grid');
        if (!gridEl) return;
        gridEl.innerHTML = '';

        if (isFallback && visibleCourseIds.length === 0) {
            var fallback = Array.isArray(window.__CURSOS_FALLBACK__) ? window.__CURSOS_FALLBACK__ : [];
            // Renderizar os 3 cards padrão (feedback)
            fallback.slice(0, 3).forEach(function(c, idx){
                var card = document.createElement('article');
                card.className = 'course-card';
                var imageWrapper = document.createElement('div');
                imageWrapper.className = 'course-card__image-wrapper';
                var img = document.createElement('img');
                img.src = c.imagem || '/assets/uploads/cards.jpg';
                img.alt = (c.titulo || 'Curso');
                img.className = 'course-card__image';
                imageWrapper.appendChild(img);
                // Badge "NOVO" fixo no segundo card
                if (idx === 1) {
                    var badge = document.createElement('span');
                    badge.className = 'course-card__badge';
                    badge.textContent = 'NOVO';
                    imageWrapper.appendChild(badge);
                }
                card.appendChild(imageWrapper);
                var content = document.createElement('div');
                content.className = 'course-card__content';
                content.innerHTML = (
                    '<h3 class="course-card__title">' + (c.titulo || '') + '</h3>' +
                    '<p class="course-card__description">' + (c.descricao || '') + '</p>' +
                    '<a href="#" class="course-card__btn">VER CURSO</a>'
                );
                card.appendChild(content);
                gridEl.appendChild(card);
            });
            // Card para adicionar novo curso
            var addCardOnly = document.createElement('article');
            addCardOnly.className = 'course-card course-card--add';
            addCardOnly.innerHTML = (
              '<div class="course-card__add-content">' +
                '<svg class="course-card__add-icon" viewBox="0 0 24 24" aria-hidden="true">' +
                  '<rect x="3" y="3" width="18" height="18" rx="4" fill="currentColor"/>' +
                  '<path d="M12 7v10M7 12h10" stroke="#000" stroke-width="2"/>' +
                '</svg>' +
                '<div class="course-card__add-text">Adicionar novo curso</div>' +
              '</div>'
            );
            addCardOnly.addEventListener('click', function() {
                openAddCourseModal();
            });
            gridEl.appendChild(addCardOnly);
            return;
        }
        // Renderizar cursos atualmente visíveis na ordem dos IDs
        visibleCourseIds
            .slice()
            .sort(function(a, b) { return a - b; })
            .forEach(function(id, idx) {
                var c = getCourseById_DB(id);
                if (!c) return;

                var card = document.createElement('article');
                card.className = 'course-card';

                var imageWrapper = document.createElement('div');
                imageWrapper.className = 'course-card__image-wrapper';
                var img = document.createElement('img');
                img.src = c.imagem || '/assets/uploads/cards.jpg';
                img.alt = c.nome || 'Curso';
                img.className = 'course-card__image';
                imageWrapper.appendChild(img);
                
                // Badge "NOVO" baseado na data de criação: aparece até 24h após inserção
                if (isCourseNew(c)) {
                    var badge = document.createElement('span');
                    badge.className = 'course-card__badge';
                    badge.textContent = 'NOVO';
                    imageWrapper.appendChild(badge);
                }
                
                card.appendChild(imageWrapper);

                var content = document.createElement('div');
                content.className = 'course-card__content';
                content.innerHTML = (
                    '<h3 class="course-card__title">' + (c.nome || '') + '</h3>' +
                    '<p class="course-card__description">' + (c.descricao || '') + '</p>' +
                    '<a href="#" class="course-card__btn">VER CURSO</a>'
                );
                // Vincular abertura do modal de detalhes ao botão do card
                var btn = content.querySelector('.course-card__btn');
                if (btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var modal = document.getElementById('courseDetailModal');
                        if (!modal) return;
                        var overlay = modal.querySelector('.modal__overlay');
                        var closeBtn = modal.querySelector('.modal__close');
                        var titleEl = modal.querySelector('.modal__title');
                        var descEl = modal.querySelector('.modal__description');
                        if (titleEl) titleEl.textContent = c.nome || '';
                        if (descEl) descEl.textContent = c.descricao || '';
                        modal.classList.add('modal--active');
                        document.body.classList.add('modal-open');
                        if (closeBtn) setTimeout(function(){ closeBtn.focus(); }, 100);
                        function close(){
                            modal.classList.remove('modal--active');
                            document.body.classList.remove('modal-open');
                        }
                        if (overlay) overlay.addEventListener('click', close, { once: true });
                        if (closeBtn) closeBtn.addEventListener('click', close, { once: true });
                        var escHandler = function(ev){
                            if (ev.key === 'Escape'){
                                close();
                                document.removeEventListener('keydown', escHandler);
                            }
                        };
                        document.addEventListener('keydown', escHandler);
                    });
                }
                card.appendChild(content);

                gridEl.appendChild(card);
            });

        // Card para adicionar novo curso
        var addCard = document.createElement('article');
        addCard.className = 'course-card course-card--add';
        addCard.innerHTML = (
          '<div class="course-card__add-content">' +
            '<svg class="course-card__add-icon" viewBox="0 0 24 24" aria-hidden="true">' +
              '<rect x="3" y="3" width="18" height="18" rx="4" fill="currentColor"/>' +
              '<path d="M12 7v10M7 12h10" stroke="#000" stroke-width="2"/>' +
            '</svg>' +
            '<div class="course-card__add-text">Adicionar novo curso</div>' +
          '</div>'
        );
        addCard.addEventListener('click', function() {
            openAddCourseModal();
        });
        gridEl.appendChild(addCard);
    }

})();