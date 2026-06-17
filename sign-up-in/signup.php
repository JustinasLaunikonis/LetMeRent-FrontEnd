<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LetMeRent - Sign up</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg?v=2" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../styles.css"/>
    <link rel="stylesheet" href="signupin.css" />
  </head>
  <body>
    <nav class="nav">
    <a class="nav-logo" href="../index.php">
      <div class="logo-icon">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
          <!-- LetMeRent Logo -->
          <path d="M2 8L9 2L16 8V16H11V12H7V16H2V8Z" fill="white"/>
        </svg>
      </div>
      <p>LetMeRent</p>
    </a>
    <div class="right-nav">Already have an account?<a href="./signin.php" class="gap">Sign in</a></div>
  </nav>

      <main class="content">
        <section class="hero-panel">
          <div>
            <h1>Find student<br />housing <span>faster.</span></h1>
            <p>Search apartments from multiple housing platforms in one place and receive instant notifications.</p>
          </div>

          <article class="listing-card">
            <div class="listing-photo">
              <img class="listing-image" src="../img/apartment.png" alt="Apartment interior" />
            </div>
          </article>
        </section>

        <section class="auth-panel">
          <div class="auth-card">
            <p class="eyebrow">Welcome to LetMeRent</p>
            <h2>Sign up to LetMeRent</h2>

            <form class="auth-form" action="./register.php" method="post" data-auth-form>
              <label>
                <span>Username</span>
                <input type="text" name="username" placeholder="Enter your username" required />
              </label>

              <label>
                <span>Email address</span>
                <input type="email" name="email" placeholder="Enter your email" required />
              </label>

              <label>
                <span>Password</span>
                <input type="password" name="password" placeholder="Enter your password" required />
              </label>

              <button type="submit">Sign up</button>
              <p class="form-message" data-auth-message role="status"></p>

              <p class="bottom-text">
                <span>Already have an account?</span>
                <a href="./signin.php">Sign in</a>
              </p>
            </form>
          </div>
        </section>
      </main>
      <script src="./auth.js"></script>
  </body>
</html>
