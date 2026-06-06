<?php
$selectedSearch = clean($_GET['search'] ?? '', 80);
$selectedZone = clean($_GET['zone'] ?? 'all', 80);
$selectedSort = clean($_GET['sort'] ?? 'rating', 20);
$serverCampings = CampingModel::all($selectedSearch, $selectedZone);
$serverZones = CampingModel::zones();

usort($serverCampings, function (array $a, array $b) use ($selectedSort): int {
    if ($selectedSort === 'price') {
        return $a['price_per_night'] <=> $b['price_per_night'];
    }
    if ($selectedSort === 'name') {
        return strcmp($a['name'], $b['name']);
    }

    return $b['rating'] <=> $a['rating'];
});
?>
<section class="page active">
  <div class="shell">
    <div class="section-head">
      <div>
        <span class="eyebrow">Catalog</span>
        <h1>Locuri de camping</h1>
      </div>
    </div>

    <form class="filters" id="filtersForm" method="get" action="index.php">
      <input type="hidden" name="page" value="campings">
      <label class="field">
        <span>Cauta</span>
        <input type="search" name="search" id="searchInput" value="<?= e($selectedSearch) ?>" placeholder="nume, zona, facilitate">
      </label>
      <label class="field">
        <span>Zona</span>
        <select name="zone" id="zoneFilter">
          <option value="all">Toate zonele</option>
          <?php foreach ($serverZones as $zone): ?>
            <option value="<?= e($zone) ?>" <?= $selectedZone === $zone ? 'selected' : '' ?>><?= e($zone) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">
        <span>Sortare</span>
        <select name="sort" id="sortSelect">
          <option value="rating" <?= $selectedSort === 'rating' ? 'selected' : '' ?>>Rating</option>
          <option value="price" <?= $selectedSort === 'price' ? 'selected' : '' ?>>Pret</option>
          <option value="name" <?= $selectedSort === 'name' ? 'selected' : '' ?>>Nume</option>
        </select>
      </label>
      <button class="btn btn-primary" type="submit">Filtreaza</button>
    </form>

    <h2 class="sr-only">Rezultate campinguri</h2>

    <div class="grid-3" id="campGrid">
      <?php if (!$serverCampings): ?>
        <div class="panel">
          <h3>Nu exista rezultate</h3>
          <p class="muted">Schimba filtrele sau importa campinguri din modulul admin.</p>
        </div>
      <?php endif; ?>

      <?php foreach ($serverCampings as $camping): ?>
        <article class="camp-card">
          <div class="camp-image">
            <img src="<?= e($camping['image_url']) ?>" alt="<?= e($camping['name']) ?>">
          </div>
          <div class="camp-body">
            <div class="camp-title">
              <div>
                <h3><?= e($camping['name']) ?></h3>
                <p><?= e($camping['zone']) ?></p>
              </div>
              <span class="rating">★ <?= e(number_format((float) $camping['rating'], 1)) ?></span>
            </div>
            <p><?= e($camping['description']) ?></p>
            <div class="tags">
              <?php foreach (array_slice($camping['facilities'], 0, 4) as $facility): ?>
                <span class="tag"><?= e($facility) ?></span>
              <?php endforeach; ?>
            </div>
            <div class="camp-footer">
              <div class="price">
                <strong><?= e(number_format((float) $camping['price_per_night'], 0)) ?> RON</strong>
                <span>pe noapte</span>
              </div>
              <a class="btn btn-primary" href="index.php?page=detail&id=<?= (int) $camping['id'] ?>">Vezi</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
