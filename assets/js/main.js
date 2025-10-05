/**
 * LEO Learning Platform - Main JavaScript
 * Carrossel nativo sem dependências externas
 */

(function() {
    'use strict';
    
    // Mock de cursos e estado dos cursos visíveis
    var COURSES_MOCK = [
        { id: 1, nome: 'Desenvolvimento Web Full Stack', descricao: 'Curso abrangente que ensina a criar aplicações web completas, cobrindo front-end (HTML, CSS, JavaScript) e back-end (Node.js, bancos de dados).' },
        { id: 2, nome: 'Ciência de Dados', descricao: 'Focado em análise e interpretação de dados, utilizando Python, estatísticas e aprendizado de máquina para extrair insights de grandes conjuntos de dados.' },
        { id: 3, nome: 'Desenvolvimento Mobile', descricao: 'Ensina a criar aplicativos para iOS e Android usando frameworks como Flutter ou React Native, com ênfase em interfaces responsivas.' },
        { id: 4, nome: 'Inteligência Artificial', descricao: 'Explora conceitos de IA, incluindo redes neurais, aprendizado profundo e processamento de linguagem natural, com projetos práticos.' },
        { id: 5, nome: 'Segurança Cibernética', descricao: 'Curso voltado para proteção de sistemas e redes, abordando técnicas de defesa contra ataques, criptografia e testes de penetração.' },
        { id: 6, nome: 'DevOps e Cloud Computing', descricao: 'Aborda práticas de DevOps, integração contínua, entrega contínua e gerenciamento de infraestrutura em nuvens como AWS, Azure e Google Cloud.' },
        { id: 7, nome: 'Desenvolvimento de Jogos', descricao: 'Ensina a criar jogos 2D e 3D utilizando engines como Unity ou Unreal Engine, com foco em programação, design e narrativa interativa.' },
        { id: 8, nome: 'Internet das Coisas (IoT)', descricao: 'Explora o desenvolvimento de dispositivos conectados, utilizando sensores, microcontroladores e plataformas como Arduino e Raspberry Pi.' },
        { id: 9, nome: 'Engenharia de Software', descricao: 'Focado em metodologias ágeis, design de sistemas, testes de software e gerenciamento de projetos para construir aplicações robustas.' },
        { id: 10, nome: 'Big Data e Hadoop', descricao: 'Ensina a processar grandes volumes de dados utilizando frameworks como Hadoop e Spark, com foco em análise escalável.' },
        { id: 11, nome: 'Blockchain e Criptomoedas', descricao: 'Aborda fundamentos de blockchain, desenvolvimento de smart contracts e aplicações descentralizadas usando Ethereum e Solidity.' },
        { id: 12, nome: 'UX/UI Design', descricao: 'Ensina a criar interfaces de usuário intuitivas e experiências digitais envolventes, utilizando ferramentas como Figma e Adobe XD.' }
    ];

    // IDs dos cursos visíveis em tela (inicialmente 7 primeiros pelo ID)
    var visibleCourseIds = COURSES_MOCK
        .slice()
        .sort(function(a, b) { return a.id - b.id; })
        .slice(0, 7)
        .map(function(c) { return c.id; });

    // IDs marcados como recém-adicionados (para exibir badge "NOVO")
    var newlyAddedCourseIds = [];


    function getCourseById(id) {
        for (var i = 0; i < COURSES_MOCK.length; i++) {
            if (COURSES_MOCK[i].id === id) return COURSES_MOCK[i];
        }
        return null;
    }

    function openAddCourseModal() {
        var modal = document.getElementById('coursesModal');
        if (!modal) return;
        var overlay = modal.querySelector('.modal__overlay');
        var closeBtn = modal.querySelector('.modal__close');
        var listEl = document.getElementById('courses-modal-list');
        if (listEl) {
            listEl.innerHTML = '';
            var available = COURSES_MOCK.filter(function(c) { return visibleCourseIds.indexOf(c.id) === -1; });
            if (available.length === 0) {
                var emptyMsg = document.createElement('p');
                emptyMsg.className = 'courses-modal__description';
                emptyMsg.textContent = 'Nenhum curso disponível para adicionar.';
                listEl.appendChild(emptyMsg);
            } else {
                available.sort(function(a, b) { return a.id - b.id; }).forEach(function(c) {
                    var btn = document.createElement('button');
                    btn.className = 'courses-modal__btn';
                    btn.type = 'button';
                    btn.textContent = c.nome + ' (ID ' + c.id + ')';
                    btn.addEventListener('click', function() {
                        if (visibleCourseIds.indexOf(c.id) === -1) {
                            visibleCourseIds.push(c.id);
                            // marcar como recém-adicionado para badge
                            if (newlyAddedCourseIds.indexOf(c.id) === -1) {
                                newlyAddedCourseIds.push(c.id);
                            }
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
    
    function init() {
        initModal();
        renderHero();
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
        var slidesData = Array.isArray(window.__SLIDES__) ? window.__SLIDES__ : [];
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

        // Renderizar cursos atualmente visíveis na ordem dos IDs
        visibleCourseIds
            .slice()
            .sort(function(a, b) { return a - b; })
            .forEach(function(id, idx) {
                var c = getCourseById(id);
                if (!c) return;

                var card = document.createElement('article');
                card.className = 'course-card';

                var imageWrapper = document.createElement('div');
                imageWrapper.className = 'course-card__image-wrapper';
                var img = document.createElement('img');
                img.src = '/assets/uploads/cards.jpg';
                img.alt = c.nome || 'Curso';
                img.className = 'course-card__image';
                imageWrapper.appendChild(img);
                
                // Badge "NOVO" fixo no segundo card e para cursos recém-adicionados
                var shouldShowNewBadge = (idx === 1) || (newlyAddedCourseIds.indexOf(c.id) !== -1);
                if (shouldShowNewBadge) {
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