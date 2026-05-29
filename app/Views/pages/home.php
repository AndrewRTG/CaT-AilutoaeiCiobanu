<section class="page active">
  <div class="shell home-layout">
    <div>
      <span class="eyebrow">CaT - Camping Info Web Tool</span>
      <h1>Campinguri disponibile, rezervari si harta interactiva</h1>
      <p class="lead">Gaseste locuri de campare potrivite, compara zonele si trimite rapid o cerere de rezervare.</p>

      <form class="quick-search" id="quickSearchForm" method="get" action="index.php">
        <input type="hidden" name="page" value="campings">
        <label class="field">
          <span>Cautare</span>
          <input type="search" name="search" placeholder="padure, lac, Apuseni">
        </label>
        <label class="field">
          <span>Zona</span>
          <select name="zone" id="homeZoneSelect">
            <option value="all">Toate zonele</option>
          </select>
        </label>
        <button class="btn btn-primary" type="submit">Cauta</button>
      </form>

      <div class="mini-stats" id="homeStats"></div>
    </div>

    <div class="hero-panel">
      <img src="https://images.unsplash.com/photo-1504851149312-7a075b496cc7?auto=format&fit=crop&w=1200&q=80" alt="Camping in natura">
      <div class="hero-card">
        <strong>Rezervare rapida</strong>
        <span>Alege perioada, numarul de persoane si trimite cererea catre administrator.</span>
      </div>
    </div>
  </div>
</section>
