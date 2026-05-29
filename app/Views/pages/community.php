<section class="page active">
  <div class="shell">
    <div class="section-head">
      <div>
        <span class="eyebrow">Mesaje si impresii</span>
        <h2>Comunitatea CaT</h2>
      </div>
    </div>
    <div class="grid-2">
      <section class="panel">
        <h3>Feed recent</h3>
        <div id="messageFeed" class="feed-list"></div>
      </section>

      <form class="panel" id="messageForm" enctype="multipart/form-data">
        <h3>Publica impresie</h3>
        <label class="field"><span>Camping</span><select name="camping_id" id="messageCampingSelect"></select></label>
        <label class="field"><span>Mesaj</span><textarea name="content" rows="5" required></textarea></label>
        <label class="field"><span>Multimedia</span><input type="file" name="media" accept="image/*,audio/*,video/*"></label>
        <button class="btn btn-primary" type="submit">Publica</button>
      </form>
    </div>
  </div>
</section>
