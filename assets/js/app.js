(function () {
  const bootstrapNode = document.getElementById('bootstrap-data');
  const bootstrap = bootstrapNode ? JSON.parse(bootstrapNode.textContent || '{}') : {};
 const legacyPage = location.hash.replace('#', '');

  if (legacyPage && !new URLSearchParams(location.search).has('page')) {
    const pages = ['home', 'campings', 'map', 'compare', 'community', 'admin', 'auth'];
    if (pages.includes(legacyPage)) {
      location.replace('index.php?page=' + encodeURIComponent(legacyPage));
      return;
    }
  }

  let authToken = localStorage.getItem('cat_token') || '';
let currentUser = bootstrap.user || null;
let campings = [];
let zones = [];
let map = null;
let mapMarkers = [];

function saveTokenFromUrl() {
  const params = new URLSearchParams(location.search);
  const token = params.get('token');

  if (token) {
    localStorage.setItem('cat_token', token);
    authToken = token;
    history.replaceState(null, '', 'index.php?page=campings');
    window.location.href = 'index.php?page=campings';
  }
}

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function showToast(message) {
    const toast = $('#toast');
    if (!toast) {
      return;
    }
    toast.textContent = message;
    toast.classList.add('show');
    window.clearTimeout(showToast.timer);
    showToast.timer = window.setTimeout(() => toast.classList.remove('show'), 2600);
  }

  async function request(path, options = {}) {
   const headers = options.headers ? { ...options.headers } : {};

   if (authToken) {
  headers.Authorization = 'Bearer ' + authToken;
   }

    let body = options.body;
    if (options.json) {
      headers['Content-Type'] = 'application/json';
      body = JSON.stringify(options.json);
    }

    const response = await fetch(path, {
      method: options.method || 'GET',
      headers,
      body,
    });

    const contentType = response.headers.get('Content-Type') || '';
    const text = await response.text();
    let payload = {};

    if (contentType.includes('application/json') || text.trim().startsWith('{') || text.includes('{"')) {
      const jsonStart = text.indexOf('{');
      if (jsonStart >= 0) {
        payload = JSON.parse(text.slice(jsonStart));
      }
    }
    if (!response.ok) {
      throw new Error(payload.error || 'Cererea a esuat.');
    }
    

    return payload;
  }

  function formParams(form) {
    const params = new URLSearchParams();
    new FormData(form).forEach((value, key) => {
      if (String(value).trim() !== '') {
        params.set(key, value);
      }
    });
    return params;
  }

  async function loadSession() {
    const session = await request('api/session.php');
    currentUser = session.user;
  }

  async function loadCampings(params = new URLSearchParams()) {
    const payload = await request('api/campings.php?' + params.toString());
    campings = payload.campings || [];
    zones = payload.zones || [];
    sortCampings();
  }

  function sortCampings() {
    const sortSelect = $('#sortSelect');
    const sort = sortSelect ? sortSelect.value : 'rating';

    campings.sort((a, b) => {
      if (sort === 'price') {
        return a.price_per_night - b.price_per_night;
      }
      if (sort === 'name') {
        return a.name.localeCompare(b.name);
      }
      return b.rating - a.rating;
    });
  }

  function renderZoneOptions() {
    ['#zoneFilter', '#homeZoneSelect'].forEach(selector => {
      const select = $(selector);
      if (!select) {
        return;
      }

      const selected = new URLSearchParams(location.search).get('zone') || select.value || 'all';
      select.innerHTML = '<option value="all">Toate zonele</option>' + zones
        .map(zone => `<option value="${escapeHtml(zone)}">${escapeHtml(zone)}</option>`)
        .join('');
      select.value = zones.includes(selected) ? selected : 'all';
    });
  }

  function renderHomeStats() {
    const box = $('#homeStats');
    if (!box) {
      return;
    }

    const reviews = campings.reduce((sum, camping) => sum + Number(camping.review_count || 0), 0);
    box.innerHTML = `
      <div><strong>${campings.length}</strong><span>campinguri</span></div>
      <div><strong>${reviews}</strong><span>recenzii</span></div>
    `;
  }

  function renderCampings() {
    const grid = $('#campGrid');
    if (!grid) {
      return;
    }

    if (!campings.length) {
      if (!grid.children.length) {
        grid.innerHTML = '<div class="panel"><h3>Nu exista rezultate</h3><p class="muted">Schimba filtrele sau importa campinguri din modulul admin.</p></div>';
      }
      return;
    }

    grid.innerHTML = campings.map(camping => `
      <article class="camp-card">
        <div class="camp-image">
          <img src="${escapeHtml(camping.image_url)}" alt="${escapeHtml(camping.name)}">
        </div>
        <div class="camp-body">
          <div class="camp-title">
            <div>
              <h3>${escapeHtml(camping.name)}</h3>
              <p>${escapeHtml(camping.zone)}</p>
            </div>
            <span class="rating">★ ${Number(camping.rating).toFixed(1)}</span>
          </div>
          <p>${escapeHtml(camping.description)}</p>
          <div class="tags">${camping.facilities.slice(0, 4).map(tag => `<span class="tag">${escapeHtml(tag)}</span>`).join('')}</div>
          <div class="camp-footer">
            <div class="price"><strong>${Number(camping.price_per_night).toFixed(0)} RON</strong><span>pe noapte</span></div>
            <a class="btn btn-primary" href="index.php?page=detail&id=${camping.id}">Vezi</a>
          </div>
        </div>
      </article>
    `).join('');
  }

  async function renderDetail() {
    const detail = $('#detailContent');
    if (!detail) {
      return;
    }

    const id = bootstrap.camping_id || new URLSearchParams(location.search).get('id');
    if (!id) {
      detail.innerHTML = '<h2>Camping negasit</h2>';
      return;
    }

    const payload = await request('api/campings.php?id=' + encodeURIComponent(id));
    const camping = payload.camping;
    const reviews = payload.reviews || [];
    const messages = payload.messages || [];

    $('#reservationCampingId').value = camping.id;
    $('#reviewCampingId').value = camping.id;

    detail.innerHTML = `
      <div class="detail-gallery">
        <img src="${escapeHtml(camping.image_url)}" alt="${escapeHtml(camping.name)}">
        <img src="https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?auto=format&fit=crop&w=800&q=80" alt="Camping natura">
        <img src="https://images.unsplash.com/photo-1537905569824-f89f14cceb68?auto=format&fit=crop&w=800&q=80" alt="Camping lac">
      </div>
      <span class="eyebrow">${escapeHtml(camping.zone)}</span>
      <h2>${escapeHtml(camping.name)}</h2>
      <p class="lead">${escapeHtml(camping.description)}</p>
      <div class="feature-list">${camping.facilities.map(facility => `<div class="feature">${escapeHtml(facility)}</div>`).join('')}</div>
      <p class="muted">Pret: ${Number(camping.price_per_night).toFixed(0)} RON/noapte · Capacitate: ${camping.capacity} persoane · Rating: ${Number(camping.rating).toFixed(1)}</p>
      <h3>Recenzii</h3>
      <div class="reviews-list">${reviews.length ? reviews.map(renderReview).join('') : '<p class="muted">Nu exista recenzii inca.</p>'}</div>
      <h3>Impresii din comunitate</h3>
      <div class="reviews-list">${messages.length ? messages.map(renderMessageOnDetail).join('') : '<p class="muted">Nu exista impresii publicate pentru acest camping.</p>'}</div>
    `;
  }

  function renderReview(review) {
    return `
      <div class="review">
        <div class="avatar">${escapeHtml(String(review.user_name || '?').slice(0, 1).toUpperCase())}</div>
        <div>
          <strong>${escapeHtml(review.user_name)} · ★ ${escapeHtml(review.rating)}</strong>
          <p>${escapeHtml(review.comment)}</p>
          ${mediaMarkup(review.media_type, review.media_path)}
        </div>
      </div>
    `;
  }

  function renderMessageOnDetail(message) {
    return `
      <div class="review">
        <div class="avatar">${escapeHtml(String(message.user_name || '?').slice(0, 1).toUpperCase())}</div>
        <div>
          <strong>${escapeHtml(message.user_name)}</strong>
          <p>${escapeHtml(message.content)}</p>
          ${mediaMarkup(message.media_type, message.media_path)}
        </div>
      </div>
    `;
  }

  function mediaMarkup(type, path) {
    if (!path) {
      return '';
    }
    const safePath = escapeHtml(path);
    if (type === 'photo') {
      return `<div class="media-preview"><img src="${safePath}" alt="Media incarcat"></div>`;
    }
    if (type === 'audio') {
      return `<div class="media-preview"><audio controls src="${safePath}"></audio></div>`;
    }
    if (type === 'video') {
      return `<div class="media-preview"><video controls src="${safePath}"></video></div>`;
    }
    return '';
  }

  function renderCompare() {
    const table = $('#compareTable');
    if (!table) {
      return;
    }

    const selected = campings.slice(0, 3);
    if (!selected.length) {
      table.innerHTML = '<tbody><tr><td>Nu exista campinguri de comparat.</td></tr></tbody>';
      return;
    }

    const rows = [
      ['Zona', ...selected.map(camping => camping.zone)],
      ['Pret/noapte', ...selected.map(camping => `${Number(camping.price_per_night).toFixed(0)} RON`)],
      ['Rating', ...selected.map(camping => `★ ${Number(camping.rating).toFixed(1)}`)],
      ['Capacitate', ...selected.map(camping => `${camping.capacity} persoane`)],
      ['Facilitati', ...selected.map(camping => camping.facilities.slice(0, 4).join(', '))],
    ];

    table.innerHTML = `
      <thead><tr><th>Criteriu</th>${selected.map(camping => `<th>${escapeHtml(camping.name)}</th>`).join('')}</tr></thead>
      <tbody>${rows.map(row => `<tr>${row.map(cell => `<td>${escapeHtml(cell)}</td>`).join('')}</tr>`).join('')}</tbody>
    `;
  }

  function renderMapList() {
    const list = $('#mapList');
    if (!list) {
      return;
    }

    list.innerHTML = campings.map(camping => `
      <article class="map-place" data-map-camping="${camping.id}">
        <img src="${escapeHtml(camping.image_url)}" alt="${escapeHtml(camping.name)}">
        <div>
          <h4>${escapeHtml(camping.name)}</h4>
          <p>${escapeHtml(camping.zone)} · ★ ${Number(camping.rating).toFixed(1)} · ${Number(camping.price_per_night).toFixed(0)} RON</p>
          <a class="btn btn-soft" href="index.php?page=detail&id=${camping.id}">Detalii</a>
        </div>
      </article>
    `).join('');
  }

  function renderMap() {
    const mapEl = $('#osmMap');
    if (!mapEl) {
      return;
    }

    if (!window.L) {
      mapEl.innerHTML = '<div class="panel"><h3>Harta indisponibila</h3><p class="muted">Nu am putut incarca harta momentan.</p></div>';
      return;
    }

    map = L.map('osmMap').setView([45.94, 24.96], 6);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    mapMarkers = campings.map(camping => {
      const marker = L.marker([camping.latitude, camping.longitude]).addTo(map);
      marker.bindPopup(`
        <strong>${escapeHtml(camping.name)}</strong><br>
        ${escapeHtml(camping.zone)}<br>
        ★ ${Number(camping.rating).toFixed(1)} · ${Number(camping.price_per_night).toFixed(0)} RON/noapte
      `);
      marker.on('click', () => highlightMapCamping(camping.id));
      return marker;
    });

    if (mapMarkers.length) {
      map.fitBounds(L.featureGroup(mapMarkers).getBounds().pad(0.2));
    }
  }

  function highlightMapCamping(id) {
    $$('.map-place').forEach(item => item.classList.toggle('active', Number(item.dataset.mapCamping) === Number(id)));
  }

  async function loadMessages() {
    const feed = $('#messageFeed');
    if (!feed) {
      return;
    }

    const payload = await request('api/messages.php');
    feed.innerHTML = (payload.messages || []).map(message => `
      <div class="message">
        <div class="avatar">${escapeHtml(String(message.user_name || '?').slice(0, 1).toUpperCase())}</div>
        <div>
          <strong>${escapeHtml(message.user_name)}${message.camping_name ? ' · ' + escapeHtml(message.camping_name) : ''}</strong>
          <p>${escapeHtml(message.content)}</p>
          ${mediaMarkup(message.media_type, message.media_path)}
        </div>
      </div>
    `).join('') || '<p class="muted">Nu exista mesaje.</p>';
  }

  function renderMessageCampingOptions() {
    const select = $('#messageCampingSelect');
    if (!select) {
      return;
    }

    select.innerHTML = '<option value="">Fara camping specific</option>' + campings
      .map(camping => `<option value="${camping.id}">${escapeHtml(camping.name)}</option>`)
      .join('');
  }

  function requireLogin() {
    if (currentUser) {
      return true;
    }
    showToast('Autentifica-te pentru aceasta actiune.');
    window.location.href = 'index.php?page=auth';
    return false;
  }

  function renderAuthActions() {
  const box = $('#authActions');

  if (!box) {
    return;
  }

  if (!currentUser) {
    box.innerHTML = '<a class="btn btn-ghost" href="index.php?page=auth">Intra in cont</a>';
    return;
  }

  box.innerHTML = `
    <span class="user-pill">${escapeHtml(currentUser.name)} · ${escapeHtml(currentUser.role)}</span>
    <button class="btn btn-ghost" type="button" id="logoutButton">Logout</button>
  `;

  const logoutButton = $('#logoutButton');

  if (logoutButton) {
    logoutButton.addEventListener('click', async () => {
      try {
        await request('auth/logout.php', { method: 'POST' });
      } catch (error) {
      }

      localStorage.removeItem('cat_token');
      authToken = '';
      currentUser = null;
      window.location.href = 'index.php?page=home';
    });
  }
}

  function bindBasicUI() {
    const mobileMenu = $('#mobileMenu');
    const mobilePanel = $('#mobilePanel');
    if (mobileMenu && mobilePanel) {
      mobileMenu.addEventListener('click', () => mobilePanel.classList.toggle('open'));
    }

    if (bootstrap.oauth_error) {
      const error = $('#oauthError');
      if (error) {
        error.textContent = bootstrap.oauth_error;
        error.classList.add('show');
      }
    }
  }
  function bindAuthForms() {
  const loginForm = $('#loginForm');
  const registerForm = $('#registerForm');

  async function finishAuth(payload) {
    localStorage.setItem('cat_token', payload.token);
    authToken = payload.token;
    currentUser = payload.user;
    renderAuthActions();
    showToast('Autentificare reusita.');
    window.location.href = 'index.php?page=campings';
  }

  if (loginForm) {
    loginForm.addEventListener('submit', async event => {
      event.preventDefault();

      const data = new FormData(loginForm);

      try {
        const payload = await request('api/auth.php', {
          method: 'POST',
          json: {
            action: 'login',
            email: data.get('email'),
            password: data.get('password'),
          },
        });

        await finishAuth(payload);
      } catch (error) {
        showToast(error.message);
      }
    });
  }

  if (registerForm) {
    registerForm.addEventListener('submit', async event => {
      event.preventDefault();

      const data = new FormData(registerForm);

      if (data.get('password') !== data.get('confirm_password')) {
        showToast('Parolele nu coincid.');
        return;
      }

      try {
        const payload = await request('api/auth.php', {
          method: 'POST',
          json: {
            action: 'register',
            name: data.get('name'),
            email: data.get('email'),
            password: data.get('password'),
            confirm_password: data.get('confirm_password'),
          },
        });

        await finishAuth(payload);
      } catch (error) {
        showToast(error.message);
      }
    });
  }
}

  function bindCampingsPage() {
    const form = $('#filtersForm');
    if (!form) {
      return;
    }

    const url = new URLSearchParams(location.search);
    if ($('#searchInput')) {
      $('#searchInput').value = url.get('search') || '';
    }
    if ($('#sortSelect')) {
      $('#sortSelect').addEventListener('change', () => {
        sortCampings();
        renderCampings();
      });
    }

    form.addEventListener('submit', event => {
      event.preventDefault();
      const params = formParams(form);
      window.location.href = 'index.php?page=campings&' + params.toString();
    });
  }

  function bindHomePage() {
    const form = $('#quickSearchForm');
    if (!form) {
      return;
    }

    form.addEventListener('submit', event => {
      event.preventDefault();
      window.location.href = 'index.php?page=campings&' + formParams(form).toString();
    });
  }

  function bindDetailForms() {
    const reservationForm = $('#reservationForm');
    if (reservationForm) {
      reservationForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!requireLogin()) {
          return;
        }

        try {
          const form = new FormData(reservationForm);
          await request('api/reservations.php', {
            method: 'POST',
            json: {
              camping_id: form.get('camping_id'),
              start_date: form.get('start_date'),
              end_date: form.get('end_date'),
              guests: form.get('guests'),
            },
          });
          reservationForm.reset();
          showToast('Rezervarea a fost trimisa.');
        } catch (error) {
          showToast(error.message);
        }
      });
    }

    const reviewForm = $('#reviewForm');
    if (reviewForm) {
      reviewForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!requireLogin()) {
          return;
        }

        try {
          await request('api/reviews.php', { method: 'POST', body: new FormData(reviewForm) });
          showToast('Recenzia a fost publicata.');
          window.location.reload();
        } catch (error) {
          showToast(error.message);
        }
      });
    }
  }

  function bindCommunityForm() {
    const form = $('#messageForm');
    if (!form) {
      return;
    }

    form.addEventListener('submit', async event => {
      event.preventDefault();
      if (!requireLogin()) {
        return;
      }

      try {
        await request('api/messages.php', { method: 'POST', body: new FormData(form) });
        form.reset();
        renderMessageCampingOptions();
        await loadMessages();
        showToast('Mesajul a fost publicat.');
      } catch (error) {
        showToast(error.message);
      }
    });
  }

  async function loadAdmin() {
    const adminArea = $('#adminArea');
    const locked = $('#adminLocked');
    if (!adminArea) {
      return;
    }

    if (!currentUser || currentUser.role !== 'admin') {
      adminArea.style.display = 'none';
      locked.classList.add('show');
      return;
    }

    adminArea.style.display = 'grid';
    locked.classList.remove('show');

    const stats = await request('api/stats.php');
    const reservations = await request('api/reservations.php');
    const users = await request('api/admin_users.php');

    renderStats(stats.stats);
    renderReservations(reservations.reservations || []);
    renderUsers(users.users || []);
    renderAdminCampings();
  }

  function renderStats(stats) {
    const totals = stats.totals || {};
    $('#adminStats').innerHTML = `
      <div class="stat-card"><span>Campinguri</span><strong>${totals.campings || 0}</strong></div>
      <div class="stat-card"><span>Utilizatori</span><strong>${totals.users || 0}</strong></div>
      <div class="stat-card"><span>Rezervari</span><strong>${totals.reservations || 0}</strong></div>
      <div class="stat-card"><span>Recenzii</span><strong>${totals.reviews || 0}</strong></div>
    `;

    const zonesStats = stats.zones || [];
    const max = Math.max(1, ...zonesStats.map(item => Number(item.total || 0)));
    const bars = zonesStats.map((item, index) => {
      const height = 140 * Number(item.total || 0) / max;
      const x = 76 + index * 96;
      const y = 190 - height;
      return `
        <rect x="${x}" y="${y}" width="46" height="${height}" rx="6" fill="${index % 2 ? '#d9853b' : '#2f6b3f'}"></rect>
        <text x="${x - 8}" y="220" font-size="12" fill="#65705f">${escapeHtml(item.zone)}</text>
      `;
    }).join('');

    $('#svgChart').innerHTML = `
      <svg width="100%" height="250" viewBox="0 0 620 250" role="img" aria-label="Zone populare">
        <rect width="620" height="250" rx="8" fill="#f7faf2"></rect>
        <line x1="48" y1="190" x2="580" y2="190" stroke="#dbe4d1" stroke-width="2"></line>
        ${bars}
      </svg>
    `;

    $('#popularList').innerHTML = (stats.popular || []).map(item => `
      <div class="message">
        <div class="avatar">${escapeHtml(String(item.name).slice(0, 1))}</div>
        <div>
          <strong>${escapeHtml(item.name)}</strong>
          <p>${escapeHtml(item.zone)} · ${Number(item.reservations || 0)} rezervari · ★ ${Number(item.rating || 0).toFixed(1)}</p>
        </div>
      </div>
    `).join('');
  }

  function renderAdminCampings() {
    const table = $('#adminCampingsTable');
    if (!table) {
      return;
    }

    table.innerHTML = `
      <thead><tr><th>Nume</th><th>Zona</th><th>Pret</th><th>Rating</th><th>Actiuni</th></tr></thead>
      <tbody>
        ${campings.map(camping => `
          <tr>
            <td>${escapeHtml(camping.name)}</td>
            <td>${escapeHtml(camping.zone)}</td>
            <td>${Number(camping.price_per_night).toFixed(0)} RON</td>
            <td>${Number(camping.rating).toFixed(1)}</td>
            <td>
              <button class="btn btn-soft" type="button" data-edit-camping="${camping.id}">Editeaza</button>
              <a class="btn btn-soft" href="index.php?page=detail&id=${camping.id}">Vezi</a>
              <button class="btn btn-danger" type="button" data-delete-camping="${camping.id}">Sterge</button>
            </td>
          </tr>
        `).join('')}
      </tbody>
    `;
  }

  function renderReservations(reservations) {
    $('#reservationsTable').innerHTML = `
      <thead><tr><th>Utilizator</th><th>Camping</th><th>Perioada</th><th>Persoane</th><th>Status</th><th>Actiuni</th></tr></thead>
      <tbody>
        ${reservations.map(reservation => `
          <tr>
            <td>${escapeHtml(reservation.user_name)}</td>
            <td>${escapeHtml(reservation.camping_name)}</td>
            <td>${escapeHtml(reservation.start_date)} - ${escapeHtml(reservation.end_date)}</td>
            <td>${escapeHtml(reservation.guests)}</td>
            <td><span class="status">${escapeHtml(reservation.status)}</span></td>
            <td>
              <button class="btn btn-soft" type="button" data-reservation-status="${reservation.id}" data-status="confirmed">Confirma</button>
              <button class="btn btn-danger" type="button" data-reservation-status="${reservation.id}" data-status="cancelled">Anuleaza</button>
            </td>
          </tr>
        `).join('')}
      </tbody>
    `;
  }

  function renderUsers(users) {
    $('#usersTable').innerHTML = `
      <thead><tr><th>Nume</th><th>Email</th><th>Provider</th><th>Rol</th><th>Status</th><th>Actiuni</th></tr></thead>
      <tbody>
        ${users.map(user => `
          <tr>
            <td>${escapeHtml(user.name)}</td>
            <td>${escapeHtml(user.email)}</td>
            <td>${escapeHtml(user.provider)}</td>
            <td>${escapeHtml(user.role)}</td>
            <td><span class="status">${escapeHtml(user.status)}</span></td>
            <td>
              <button class="btn btn-soft" type="button" data-user-role="${user.id}" data-role="${user.role === 'admin' ? 'member' : 'admin'}" data-status="${escapeHtml(user.status)}">${user.role === 'admin' ? 'Member' : 'Admin'}</button>
              <button class="btn btn-danger" type="button" data-user-role="${user.id}" data-role="${escapeHtml(user.role)}" data-status="${user.status === 'active' ? 'blocked' : 'active'}">${user.status === 'active' ? 'Blocheaza' : 'Activeaza'}</button>
            </td>
          </tr>
        `).join('')}
      </tbody>
    `;
  }

  function resetCampingForm() {
    const form = $('#campingForm');
    if (!form) {
      return;
    }
    form.reset();
    $('#campingEditId').value = '';
    $('#campingFormTitle').textContent = 'Adauga camping';
    $('#campingSubmit').textContent = 'Salveaza oferta';
  }

  function bindAdmin() {
    const adminArea = $('#adminArea');
    if (!adminArea) {
      return;
    }

    $$('.admin-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        const key = tab.dataset.admin;
        $$('.admin-tab').forEach(item => item.classList.remove('active'));
        tab.classList.add('active');
        $$('.admin-section').forEach(section => section.classList.toggle('active', section.id === 'admin-' + key));
      });
    });

    const reset = $('#resetCampingForm');
    if (reset) {
      reset.addEventListener('click', resetCampingForm);
    }

    const form = $('#campingForm');
    if (form) {
      form.addEventListener('submit', async event => {
        event.preventDefault();
        try {
          const data = new FormData(form);
          const id = $('#campingEditId').value;
          await request(id ? 'api/campings.php?id=' + encodeURIComponent(id) : 'api/campings.php', {
            method: id ? 'PATCH' : 'POST',
            json: {
              name: data.get('name'),
              zone: data.get('zone'),
              price_per_night: data.get('price_per_night'),
              capacity: data.get('capacity'),
              latitude: data.get('latitude'),
              longitude: data.get('longitude'),
              image_url: data.get('image_url'),
              facilities: String(data.get('facilities') || '').split(',').map(item => item.trim()).filter(Boolean),
              description: data.get('description'),
            },
          });
          resetCampingForm();
          await loadCampings();
          await loadAdmin();
          showToast('Campingul a fost salvat.');
        } catch (error) {
          showToast(error.message);
        }
      });
    }

    const importForm = $('#importForm');
    if (importForm) {
      importForm.addEventListener('submit', async event => {
        event.preventDefault();
        try {
          const payload = await request('api/import.php', { method: 'POST', body: new FormData(importForm) });
          importForm.reset();
          await loadCampings();
          await loadAdmin();
          showToast(`Import finalizat: ${payload.imported} campinguri.`);
        } catch (error) {
          showToast(error.message);
        }
      });
    }

    document.addEventListener('click', async event => {
      const edit = event.target.closest('[data-edit-camping]');
      if (edit) {
        const camping = campings.find(item => Number(item.id) === Number(edit.dataset.editCamping));
        if (!camping) {
          return;
        }
        form.name.value = camping.name;
        form.zone.value = camping.zone;
        form.price_per_night.value = camping.price_per_night;
        form.capacity.value = camping.capacity;
        form.latitude.value = camping.latitude;
        form.longitude.value = camping.longitude;
        form.image_url.value = camping.image_url;
        form.facilities.value = camping.facilities.join(', ');
        form.description.value = camping.description;
        $('#campingEditId').value = camping.id;
        $('#campingFormTitle').textContent = 'Editeaza camping';
        $('#campingSubmit').textContent = 'Actualizeaza oferta';
      }

      const remove = event.target.closest('[data-delete-camping]');
      if (remove) {
        await request('api/campings.php?id=' + encodeURIComponent(remove.dataset.deleteCamping), { method: 'DELETE' });
        await loadCampings();
        await loadAdmin();
        showToast('Campingul a fost sters.');
      }

      const reservation = event.target.closest('[data-reservation-status]');
      if (reservation) {
        await request('api/reservations.php?id=' + encodeURIComponent(reservation.dataset.reservationStatus), {
          method: 'PATCH',
          json: { status: reservation.dataset.status },
        });
        await loadAdmin();
      }

      const user = event.target.closest('[data-user-role]');
      if (user) {
        await request('api/admin_users.php?id=' + encodeURIComponent(user.dataset.userRole), {
          method: 'PATCH',
          json: { role: user.dataset.role, status: user.dataset.status },
        });
        await loadAdmin();
      }
    });
  }

  async function init() {
  saveTokenFromUrl();

  bindBasicUI();
  bindAuthForms();
  bindHomePage();
  bindCampingsPage();
  bindDetailForms();
  bindCommunityForm();
  bindAdmin();

    try {
      await loadSession();
      renderAuthActions();
      const listParams = new URLSearchParams(location.search);
      listParams.delete('page');
      listParams.delete('id');
      await loadCampings(listParams);
      renderZoneOptions();
      renderHomeStats();
      renderCampings();
      renderCompare();
      renderMapList();
      renderMap();
      renderMessageCampingOptions();
      await renderDetail();
      await loadMessages();
      await loadAdmin();
    } catch (error) {
      showToast(error.message);
    }
  }

  init();
})();
