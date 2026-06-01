<section class="page active">
  <div class="shell">
    <div class="section-head">
      <div>
        <span class="eyebrow">Contul tau</span>
        <h2>Login si register</h2>
        <p>Intra cu email si parola sau foloseste GitHub OAuth.</p>
      </div>
    </div>

    <div class="auth-error" id="oauthError"></div>

    <div class="auth-box">
      <div class="auth-card">
        <h3>Login</h3>
        <form id="loginForm" class="form-grid">
          <div class="field wide">
            <label>Email</label>
            <input type="email" name="email" required>
          </div>

          <div class="field wide">
            <label>Parola</label>
            <input type="password" name="password" required>
          </div>

          <button class="btn btn-primary wide" type="submit">Intra in cont</button>
        </form>
      </div>

      <div class="auth-card">
        <h3>Register</h3>
        <form id="registerForm" class="form-grid">
          <div class="field wide">
            <label>Nume</label>
            <input type="text" name="name" required>
          </div>

          <div class="field wide">
            <label>Email</label>
            <input type="email" name="email" required>
          </div>

          <div class="field wide">
            <label>Parola</label>
            <input type="password" name="password" minlength="8" required>
          </div>

          <div class="field wide">
            <label>Confirma parola</label>
            <input type="password" name="confirm_password" minlength="8" required>
          </div>

          <button class="btn btn-primary wide" type="submit">Creeaza cont</button>
        </form>
      </div>

      <div class="auth-card">
        <h3>OAuth</h3>
        <p class="muted">Autentificare externa prin GitHub OAuth.</p>

        <div class="oauth-options">
          <a class="btn oauth-btn" href="auth/github.php">Continua cu GitHub</a>
        </div>
      </div>
    </div>
  </div>
</section>