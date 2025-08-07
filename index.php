<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Healthcare Appointment System</title>
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --primary: #0066cc;
      --primary-dark: #004080;
      --secondary: #0070f3;
      --text: #222;
      --text-light: #444;
      --bg: #f9fafb;
      --white: #fff;
      --shadow: 0 3px 8px rgba(0,0,0,0.12);
      --transition: all 0.3s ease;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.6;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    /* Improved Header */
    header {
      background: linear-gradient(90deg, var(--primary), var(--primary-dark));
      padding: 1rem 1.5rem;
      box-shadow: var(--shadow);
      color: var(--white);
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    header::after {
      content: "";
      background: url('images/doctor.jpg') center/cover no-repeat;
      opacity: 0.15;
      position: absolute;
      inset: 0;
      z-index: 0;
    }

    .header-content {
      max-width: 1200px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }

    .logo {
      font-size: 1.8rem;
      font-weight: 700;
      letter-spacing: 0.5px;
      margin-bottom: 0.5rem;
    }

    .tagline {
      font-size: 1rem;
      font-weight: 400;
      opacity: 0.9;
    }

    /* Main Content */
    main {
      flex: 1;
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 1.5rem;
    }

    .hero {
      display: flex;
      align-items: center;
      gap: 3rem;
      margin-bottom: 3rem;
    }

    .hero-text {
      flex: 1;
    }

    .hero-text h2 {
      font-size: 2.2rem;
      margin-bottom: 1rem;
      color: var(--primary-dark);
    }

    .hero-text p {
      font-size: 1.1rem;
      color: var(--text-light);
      margin-bottom: 2rem;
    }

    .hero-image {
      flex: 1;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .hero-image img {
      width: 100%;
      height: auto;
      display: block;
      transition: var(--transition);
    }

    .hero-image:hover img {
      transform: scale(1.02);
    }

    /* Buttons */
    .btn-group {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.8rem 1.8rem;
      font-size: 1rem;
      font-weight: 600;
      border-radius: 8px;
      text-decoration: none;
      transition: var(--transition);
      cursor: pointer;
    }

    .btn-primary {
      background: var(--secondary);
      color: var(--white);
      box-shadow: 0 4px 12px rgba(0,112,243,0.3);
    }

    .btn-primary:hover {
      background: #005bb5;
      transform: translateY(-2px);
    }

    .btn-outline {
      border: 2px solid var(--secondary);
      color: var(--secondary);
      background: transparent;
    }

    .btn-outline:hover {
      background: rgba(0, 112, 243, 0.1);
    }

    /* Features Section */
    .features {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
      margin: 3rem 0;
    }

    .feature-card {
      background: var(--white);
      padding: 1.5rem;
      border-radius: 10px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
      transition: var(--transition);
    }

    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    .feature-icon {
      font-size: 2rem;
      color: var(--primary);
      margin-bottom: 1rem;
    }

    .feature-card h3 {
      margin-bottom: 0.5rem;
    }

    /* Counter */
    #live-counter {
      text-align: center;
      margin: 2rem 0;
      font-size: 1.1rem;
    }

    #appointment-count {
      font-weight: 700;
      color: var(--primary);
      font-size: 1.3rem;
    }

    /* Footer */
    footer {
      background: #2d3748;
      color: var(--white);
      padding: 2rem 1.5rem;
      text-align: center;
      margin-top: 3rem;
    }

    .footer-content {
      max-width: 1200px;
      margin: 0 auto;
    }

    .footer-links {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .footer-links a {
      color: #a0aec0;
      text-decoration: none;
      transition: color 0.2s;
    }

    .footer-links a:hover {
      color: var(--white);
    }

    .copyright {
      font-size: 0.9rem;
      color: #cbd5e0;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .hero {
        flex-direction: column;
      }

      .hero-text,
      .hero-image {
        width: 100%;
      }

      .btn-group {
        justify-content: center;
      }
    }

    @media (max-width: 480px) {
      .logo {
        font-size: 1.5rem;
      }

      .hero-text h2 {
        font-size: 1.8rem;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="header-content">
      <div class="logo">Healthcare Appointment System</div>
      <div class="tagline">Your health, our commitment</div>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="hero-text">
        <h2>Your Health, Our Priority</h2>
        <p>
          Easily schedule and manage your healthcare appointments with trusted specialists. 
          Experience a seamless, secure platform built for your well-being.
        </p>
        <div class="btn-group">
          <a href="login.php" class="btn btn-primary">
            <i class="fas fa-sign-in-alt"></i> Login
          </a>
          <a href="register.php" class="btn btn-outline">
            <i class="fas fa-user-plus"></i> Register
          </a>
        </div>
      </div>
      <div class="hero-image">
        <img src="images/doctor.jpg." alt="Doctor and patient discussing" loading="lazy" />
      </div>
    </section>

    <section class="features">
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-calendar-check"></i>
        </div>
        <h3>Easy Scheduling</h3>
        <p>Book appointments anytime with our intuitive online system.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-bell"></i>
        </div>
        <h3>Smart Reminders</h3>
        <p>Never miss an appointment with our notification system.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">
          <i class="fas fa-shield-alt"></i>
        </div>
        <h3>Secure Platform</h3>
        <p>Your health data is protected with advanced security measures.</p>
      </div>
    </section>

    <div id="live-counter">
      <p><span id="appointment-count">0</span> appointments booked today</p>
    </div>
  </main>

  <footer>
    <div class="footer-content">
      <div class="footer-links">
        <a href="#">About Us</a>
        <a href="#">Services</a>
        <a href="#">Privacy Policy</a>
        <a href="#">Contact</a>
      </div>
      <p class="copyright">&copy; 2025 Healthcare Appointment System. All rights reserved.</p>
    </div>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Animated counter
      const counter = document.getElementById('appointment-count');
      const target = Math.floor(Math.random() * 100) + 50;
      const duration = 2000;
      const increment = target / (duration / 16);
      let current = 0;

      const animateCounter = () => {
        current += increment;
        if (current < target) {
          counter.textContent = Math.floor(current);
          requestAnimationFrame(animateCounter);
        } else {
          counter.textContent = target;
        }
      };

      animateCounter();

      // Smooth scroll for anchor links
      document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener('click', function (e) {
          e.preventDefault();
          const target = document.querySelector(this.getAttribute('href'));
          if (target) {
            target.scrollIntoView({
              behavior: 'smooth',
            });
          }
        });
      });

      // Time-based greeting
      const hour = new Date().getHours();
      const greeting = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';
      console.log(`${greeting}! Ready to book your appointment?`);
    });
  </script>
</body>
</html>
