<?php
$serverCamping = $campingId ? CampingModel::find((int) $campingId) : null;
$serverReviews = $campingId ? ReviewModel::forCamping((int) $campingId) : [];
$serverMessages = $campingId ? MessageModel::forCamping((int) $campingId) : [];
?>
<section class="page active">
  <div class="shell">
    <a class="btn btn-ghost back-btn" href="index.php?page=campings">Inapoi la lista</a>
    <div class="detail-layout">
      <article class="detail-main" id="detailContent">
        <?php if (!$serverCamping): ?>
          <h2>Camping negasit</h2>
        <?php else: ?>
          <div class="detail-gallery">
            <img src="<?= e($serverCamping['image_url']) ?>" alt="<?= e($serverCamping['name']) ?>">
            <img src="https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?auto=format&fit=crop&w=800&q=80" alt="Camping natura">
            <img src="https://images.unsplash.com/photo-1537905569824-f89f14cceb68?auto=format&fit=crop&w=800&q=80" alt="Camping lac">
          </div>
          <span class="eyebrow"><?= e($serverCamping['zone']) ?></span>
          <h2><?= e($serverCamping['name']) ?></h2>
          <p class="lead"><?= e($serverCamping['description']) ?></p>
          <div class="feature-list">
            <?php foreach ($serverCamping['facilities'] as $facility): ?>
              <div class="feature"><?= e($facility) ?></div>
            <?php endforeach; ?>
          </div>
          <p class="muted">
            Pret: <?= e(number_format((float) $serverCamping['price_per_night'], 0)) ?> RON/noapte ·
            Capacitate: <?= (int) $serverCamping['capacity'] ?> persoane ·
            Rating: <?= e(number_format((float) $serverCamping['rating'], 1)) ?>
          </p>
          <h3>Recenzii</h3>
          <div class="reviews-list">
            <?php if (!$serverReviews): ?>
              <p class="muted">Nu exista recenzii inca.</p>
            <?php endif; ?>
            <?php foreach ($serverReviews as $review): ?>
              <div class="review">
                <div class="avatar"><?= e(strtoupper(substr($review['user_name'] ?? '?', 0, 1))) ?></div>
                <div>
                  <strong><?= e($review['user_name']) ?> · ★ <?= (int) $review['rating'] ?></strong>
                  <p><?= e($review['comment']) ?></p>
                  <?php if (!empty($review['media_path']) && $review['media_type'] === 'photo'): ?>
                    <div class="media-preview">
                      <img src="<?= e($review['media_path']) ?>" alt="Fotografie incarcata de utilizator">
                    </div>
                  <?php elseif (!empty($review['media_path']) && $review['media_type'] === 'audio'): ?>
                    <div class="media-preview">
                      <audio controls src="<?= e($review['media_path']) ?>"></audio>
                    </div>
                  <?php elseif (!empty($review['media_path']) && $review['media_type'] === 'video'): ?>
                    <div class="media-preview">
                      <video controls src="<?= e($review['media_path']) ?>"></video>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <h3>Impresii din comunitate</h3>
          <div class="reviews-list">
            <?php if (!$serverMessages): ?>
              <p class="muted">Nu exista impresii publicate pentru acest camping.</p>
            <?php endif; ?>
            <?php foreach ($serverMessages as $message): ?>
              <div class="review">
                <div class="avatar"><?= e(strtoupper(substr($message['user_name'] ?? '?', 0, 1))) ?></div>
                <div>
                  <strong><?= e($message['user_name']) ?></strong>
                  <p><?= e($message['content']) ?></p>
                  <?php if (!empty($message['media_path']) && $message['media_type'] === 'photo'): ?>
                    <div class="media-preview">
                      <img src="<?= e($message['media_path']) ?>" alt="Fotografie incarcata de utilizator">
                    </div>
                  <?php elseif (!empty($message['media_path']) && $message['media_type'] === 'audio'): ?>
                    <div class="media-preview">
                      <audio controls src="<?= e($message['media_path']) ?>"></audio>
                    </div>
                  <?php elseif (!empty($message['media_path']) && $message['media_type'] === 'video'): ?>
                    <div class="media-preview">
                      <video controls src="<?= e($message['media_path']) ?>"></video>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </article>
      <aside class="side-stack">
        <form class="panel" id="reservationForm">
          <h3>Rezerva loc</h3>
          <input type="hidden" name="camping_id" id="reservationCampingId" value="<?= (int) ($campingId ?? 0) ?>">
          <div class="date-grid">
            <label class="field"><span>Check-in</span><input type="date" name="start_date" required></label>
            <label class="field"><span>Check-out</span><input type="date" name="end_date" required></label>
            <label class="field wide"><span>Persoane</span><input type="number" min="1" name="guests" value="2" required></label>
          </div>
          <button class="btn btn-primary full" type="submit">Trimite rezervare</button>
        </form>

        <form class="panel" id="reviewForm" enctype="multipart/form-data">
          <h3>Adauga recenzie</h3>
          <input type="hidden" name="camping_id" id="reviewCampingId" value="<?= (int) ($campingId ?? 0) ?>">
          <label class="field"><span>Rating</span><select name="rating"><option>5</option><option>4</option><option>3</option><option>2</option><option>1</option></select></label>
          <label class="field"><span>Comentariu</span><textarea name="comment" rows="4" required></textarea></label>
          <label class="field"><span>Foto / audio / video</span><input type="file" name="media" accept="image/*,audio/*,video/*"></label>
          <button class="btn btn-soft full" type="submit">Publica recenzia</button>
        </form>
      </aside>
    </div>
  </div>
</section>
