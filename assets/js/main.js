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

  // Basic slideshow rendering (will be replaced by backend data)
  var slideshow = document.getElementById('slideshow');
  if(slideshow){
    var slides = [
      {img:'/assets/uploads/placeholder1.jpg', title:'Slide 1', desc:'Descrição do slide 1', link:'#'},
      {img:'/assets/uploads/placeholder2.jpg', title:'Slide 2', desc:'Descrição do slide 2', link:'#'}
    ];
    slides.forEach(function(s){
      var item = document.createElement('div');
      item.className = 'slide';
      item.innerHTML = '<img src="'+s.img+'" alt="'+s.title+'">' +
        '<div class="slide__content"><h3>'+s.title+'</h3><p>'+s.desc+'</p><a href="'+s.link+'" class="btn">Saiba mais</a></div>';
      slideshow.appendChild(item);
    });
  }

  // Placeholder courses grid (will be fed from PHP + DB)
  var grid = document.getElementById('courses-grid');
  if(grid){
    var courses = [
      {title:'Curso A', desc:'Descrição curta do Curso A'},
      {title:'Curso B', desc:'Descrição curta do Curso B'},
      {title:'Curso C', desc:'Descrição curta do Curso C'},
      {title:'Curso D', desc:'Descrição curta do Curso D'}
    ];
    courses.forEach(function(c){
      var card = document.createElement('article');
      card.className = 'card';
      card.innerHTML = '<h3>'+c.title+'</h3><p>'+c.desc+'</p>';
      grid.appendChild(card);
    });
  }
})();