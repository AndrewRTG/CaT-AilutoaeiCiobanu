<section class="page active">
  <div class="shell">
    <div class="section-head">
      <div>
        <span class="eyebrow">Administrare</span>
        <h2>Dashboard admin</h2>
      </div>
    </div>

    <div class="admin-locked panel" id="adminLocked">
      <h3>Acces rezervat administratorilor</h3>
      <p class="muted">Intra folosind un cont de administrator pentru a vedea aceasta sectiune.</p>
      <a class="btn btn-primary" href="index.php?page=auth">Autentificare</a>
    </div>

    <div class="admin-layout" id="adminArea">
      <aside class="admin-menu">
        <button class="admin-tab active" type="button" data-admin="dashboard">Dashboard</button>
        <button class="admin-tab" type="button" data-admin="offers">Campinguri</button>
        <button class="admin-tab" type="button" data-admin="reservations">Rezervari</button>
        <button class="admin-tab" type="button" data-admin="users">Utilizatori</button>
        <button class="admin-tab" type="button" data-admin="exports">Import / Export</button>
      </aside>

      <div class="admin-content">
        <section class="admin-section active" id="admin-dashboard">
          <div class="stat-grid" id="adminStats"></div>

          <div class="grid-2">
            <div class="panel">
              <h3>Zone populare</h3>
              <div class="chart-wrap" id="svgChart"></div>
            </div>

            <div class="panel">
              <h3>Perioade populare</h3>
              <div class="chart-wrap" id="periodChart"></div>
            </div>

            <div class="panel">
              <h3>Top campinguri</h3>
              <div id="popularList" class="feed-list"></div>
            </div>

            <div class="panel">
              <h3>Status rezervari</h3>
              <div id="statusList" class="feed-list"></div>
            </div>
          </div>
        </section>

        <section class="admin-section" id="admin-offers">
          <form class="panel" id="campingForm" enctype="multipart/form-data">
            <h3 id="campingFormTitle">Adauga camping</h3>
            <input type="hidden" name="camping_id" id="campingEditId">

            <div class="form-grid">
              <label class="field">
                <span>Nume</span>
                <input name="name" required>
              </label>

              <label class="field">
                <span>Zona</span>
                <input name="zone" required>
              </label>

              <label class="field">
                <span>Pret/noapte</span>
                <input type="number" min="1" step="0.01" name="price_per_night" required>
              </label>

              <label class="field">
                <span>Capacitate</span>
                <input type="number" min="1" name="capacity" value="30">
              </label>

              <label class="field">
                <span>Latitudine</span>
                <input type="number" step="0.000001" name="latitude" required>
              </label>

              <label class="field">
                <span>Longitudine</span>
                <input type="number" step="0.000001" name="longitude" required>
              </label>

              <label class="field wide">
                <span>Imagine camping</span>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
              </label>

              <label class="field wide">
                <span>Facilitati</span>
                <input name="facilities" placeholder="Wi-Fi, Dusuri, Parcare">
              </label>

              <label class="field wide">
                <span>Descriere</span>
                <textarea rows="4" name="description" required></textarea>
              </label>
            </div>

            <div class="export-actions">
              <button class="btn btn-primary" type="submit" id="campingSubmit">Salveaza oferta</button>
              <button class="btn btn-ghost" type="button" id="resetCampingForm">Formular nou</button>
            </div>
          </form>

          <div class="panel table-wrap">
            <h3>Campinguri existente</h3>
            <table class="admin-table" id="adminCampingsTable"></table>
          </div>
        </section>

        <section class="admin-section" id="admin-reservations">
          <div class="panel table-wrap">
            <h3>Rezervari</h3>
            <table class="admin-table" id="reservationsTable"></table>
          </div>
        </section>

        <section class="admin-section" id="admin-users">
          <div class="panel table-wrap">
            <h3>Utilizatori</h3>
            <table class="admin-table" id="usersTable"></table>
          </div>
        </section>

        <section class="admin-section" id="admin-exports">
          <div class="grid-2">
            <form class="panel" id="importForm" enctype="multipart/form-data">
              <h3>Import campinguri</h3>
              <p class="muted">Incarca un fisier CSV sau JSON cu campinguri si detaliile lor.</p>

              <label class="field">
                <span>Fisier</span>
                <input type="file" name="file" accept=".csv,.json" required>
              </label>

              <button class="btn btn-primary" type="submit">Importa</button>
            </form>

            <div class="panel">
              <h3>Export date</h3>

              <div class="export-actions">
                 <button class="btn btn-soft" type="button" data-export-url="api/export.php?format=csv&entity=campings">
                  Campinguri CSV
                </button>

                <button class="btn btn-soft" type="button" data-export-url="api/export.php?format=json&entity=campings">
                  Campinguri JSON
                </button>

                <button class="btn btn-soft" type="button" data-export-url="api/export.php?format=csv&entity=reservations">
                  Rezervari CSV
                </button>

                <button class="btn btn-soft" type="button" data-export-url="api/export.php?format=json&entity=users">
                  Utilizatori JSON
                </button>

                <button class="btn btn-soft" type="button" data-export-url="api/export.php?format=svg">
                  Grafic statistici SVG
                </button>

                <button class="btn btn-primary" type="button" data-export-url="api/export.php?format=pdf">
                  Raport complet PDF
                </button>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</section>