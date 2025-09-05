
(function(){
  // Figure out which link is active
  const page = (location.pathname.split('/').pop() || '').toLowerCase();
  const q = new URLSearchParams(location.search);
  const type = (q.get('type')||'').toLowerCase();

  const active = {
    home:       page.includes('home'),
    streaming:  page.includes('platforms') && type === 'streaming',
    webtoon:    page.includes('platforms') && type === 'webtoon',
    update:     page.includes('update')
  };

  // Build the navbar HTML identical to Home
  const navHtml = `
    <div class="brand">
      <div class="logo-dot"></div><span>My E-Library</span>
    </div>
    <a href="home.html" class="${active.home?'active':''}">Home</a>
    <a href="platforms.html?type=streaming" class="${active.streaming?'active':''}">Streaming Platform</a>
    <a href="platforms.html?type=webtoon" class="${active.webtoon?'active':''}">Webtoon Platform</a>
    <div class="spacer"></div>
    <a href="update.html" class="${active.update?'active':''}">Update Wishlist</a>
    <a id="logoutBtn" class="icon-btn" href="#" title="Logout" aria-label="Logout">
      <i class="fa-solid fa-right-from-bracket"></i><span class="label">Logout</span>
    </a>
  `;

  // Render into <nav id="app-nav">
  const container = document.getElementById('app-nav');
  if (container){
    container.classList.add('nav');
    container.innerHTML = navHtml;
  }

  // Hook logout
  const onReady = () => {
    const btn = document.getElementById('logoutBtn');
    if (btn){
      btn.addEventListener('click', (e)=>{
        e.preventDefault();
        localStorage.removeItem('loggedIn');
        localStorage.removeItem('username');
        location.href = 'login.html';
      });
    }
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady);
  } else { onReady(); }
})();

