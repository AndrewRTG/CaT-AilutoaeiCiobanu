<section class="page active">
  <div class="shell">
    <div class="section-head">
      <div>
        <span class="eyebrow">Contul tau</span>
        <h2>Autentificare</h2>
      </div>
    </div>

    <div class="auth-error" id="oauthError"></div>
    <div class="auth-box">
      <div class="auth-card">
        <h3>Alege o metoda de autentificare</h3>
        <div class="oauth-options">
         <a class="btn oauth-btn" href="auth/github.php">Continua cu GitHub</a>
          <a class="btn oauth-btn" href="auth/login.php?provider=google">Continua cu Google</a>
          <a class="btn oauth-btn" href="auth/login.php?provider=osm">Continua cu contul hartii</a>
        </div>
      </div>
      <div class="auth-card">
        <h3>Conturi pentru testare</h3>
        <div class="oauth-options">
          <a class="btn btn-soft" href="auth/login.php?provider=demo_user">Demo membru</a>
          <a class="btn btn-primary" href="auth/login.php?provider=demo_admin">Demo admin</a>
        </div>
      </div>
    </div>
  </div>
</section>
