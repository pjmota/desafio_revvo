// Simple modal and slideshow placeholder
(function(){
  // Modal first visit using localStorage fallback to cookies
  function hasVisited(){
    try { return localStorage.getItem('revvo_visited') === '1'; } catch(e) {}
    return document.cookie.includes('revvo_visited=1');
  }
  function setVisited(){
    try { localStorage.setItem('revvo_visited','1'); } catch(e) {}
    document.cookie = 'revvo_visited=1; path=/; max-age=' + (60*60*24*365);
  }
  var modal = document.getElementById('modal');
  var closeBtn = document.getElementById('modal-close');
  if(modal){
    if(!hasVisited()){
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden','false');
    }
    closeBtn && closeBtn.addEventListener('click', function(){
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden','true');
      setVisited();
    });
  }

  // Slideshow from backend data
  var slideshow = document.getElementById('slideshow');
  if(slideshow){
    var slides = (window.__SLIDES__ || []);
    slideshow.innerHTML = '';
    slides.forEach(function(s){
      var item = document.createElement('div');
      item.className = 'slide';
      var imgAlt = (s.titulo || 'Slide');
      item.innerHTML = '<img src="'+s.imagem+'" alt="'+imgAlt+'">' +
        '<div class="slide__content"><h3>'+s.titulo+'</h3><p>'+s.descricao+'</p><a href="'+s.link+'" class="btn">Saiba mais</a></div>';
      slideshow.appendChild(item);
    });
  }

  // Courses grid from backend data
  var grid = document.getElementById('courses-grid');
  if(grid){
    var courses = (window.__CURSOS__ || []);
    grid.innerHTML = '';
    courses.forEach(function(c){
      var card = document.createElement('article');
      card.className = 'card';
      card.innerHTML = '<h3>'+c.titulo+'</h3><p>'+c.descricao+'</p>';
      grid.appendChild(card);
    });
  }
})();