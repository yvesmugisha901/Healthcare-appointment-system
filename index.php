<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Healthcare Appointment System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --primary: #0066cc;
  --primary-dark: #004080;
  --secondary: #0070f3;
  --text: #222;
  --text-light: #555;
  --bg: #f9fafb;
  --white: #fff;
  --shadow: 0 3px 8px rgba(0,0,0,0.12);
  --transition: all 0.3s ease;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  font-family: 'Poppins', sans-serif;
  background: var(--bg);
  color: var(--text);
  line-height: 1.6;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* Header */
header {
  background: linear-gradient(90deg, var(--primary), var(--primary-dark));
  color: var(--white);
  padding: 3rem 1.5rem;
  text-align: center;
}
header h1 { font-size: 2.2rem; font-weight: 700; margin-bottom: 0.5rem; }
header p { font-size: 1.1rem; opacity: 0.9; }

/* Hero */
.hero {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  max-width: 1200px;
  margin: 2rem auto 3rem;
  padding: 0 1.5rem;
  gap: 2rem;
}
.hero-text { flex: 1; text-align: center; }
.hero-text h2 {
  font-size: 2rem;
  color: var(--primary-dark);
  margin-bottom: 1rem;
}
.hero-text p { font-size: 1.1rem; color: var(--text-light); margin-bottom: 2rem; }
.btn-group { display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; }
.btn {
  padding: 0.8rem 1.6rem;
  border-radius: 8px;
  font-weight: 600;
  text-decoration: none;
  transition: var(--transition);
}
.btn-primary { background: var(--secondary); color: var(--white); }
.btn-primary:hover { background: #005bb5; transform: translateY(-2px); }
.btn-outline { border: 2px solid var(--secondary); color: var(--secondary); }
.btn-outline:hover { background: rgba(0,112,243,0.1); }

/* Hero Image */
.hero-image { flex: 1; text-align: center; }
.hero-image img {
  width: 100%;
  max-width: 400px;
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.1);
  transition: transform 0.3s ease;
}
.hero-image:hover img { transform: scale(1.03); }

/* Features */
.features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  max-width: 1200px;
  margin: 3rem auto;
  padding: 0 1.5rem;
}
.feature-card {
  background: var(--white);
  padding: 2rem 1.5rem;
  border-radius: 12px;
  box-shadow: var(--shadow);
  text-align: center;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.feature-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
.feature-icon { font-size: 2rem; color: var(--primary); margin-bottom: 1rem; }

/* Footer */
footer {
  background: #2d3748;
  color: var(--white);
  padding: 2rem 1.5rem;
  text-align: center;
  margin-top: auto;
}
.footer-links {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 1.5rem;
  margin-bottom: 1rem;
}
.footer-links a { color: #a0aec0; text-decoration: none; transition: color 0.2s; }
.footer-links a:hover { color: var(--white); }
footer p { font-size: 0.9rem; color: #cbd5e0; }

/* Responsive */
@media (max-width: 768px) { .hero { flex-direction: column; } .hero-text, .hero-image { width: 100%; } }
@media (max-width: 480px) { header h1 { font-size: 1.8rem; } .hero-text h2 { font-size: 1.6rem; } }
</style>
</head>
<body>

<!-- Header -->
<header>
  <h1>Healthcare Appointment System</h1>
  <p>Your health, our commitment</p>
</header>

<!-- Hero Section -->
<section class="hero">
  <div class="hero-text">
    <h2>Secure & Seamless Appointments</h2>
    <p>Easily schedule and manage your healthcare appointments with trusted specialists. Experience a seamless, secure platform built for your well-being.</p>
    <div class="btn-group">
      <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Login</a>
      <a href="register.php" class="btn btn-outline"><i class="fas fa-user-plus"></i> Register</a>
    </div>
  </div>
  <div class="hero-image">
    <img src="images/doctor.jpg" alt="Doctor with patient">
  </div>
</section>

<!-- Features Section -->
<section class="features">
  <div class="feature-card">
    <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
    <h3>Easy Scheduling</h3>
    <p>Book appointments anytime with our intuitive online system.</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon"><i class="fas fa-bell"></i></div>
    <h3>Smart Reminders</h3>
    <p>Never miss an appointment with our notification system.</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
    <h3>Secure Platform</h3>
    <p>Your health data is protected with advanced security measures.</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon"><i class="fas fa-user-md"></i></div>
    <h3>Expert Doctors</h3>
    <p>Connect with certified professionals specialized in your needs.</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon"><i class="fas fa-hospital"></i></div>
    <h3>Multiple Clinics</h3>
    <p>Access a variety of clinics and healthcare centers nationwide.</p>
  </div>
</section>

<!-- Footer -->
<footer>
  <div class="footer-links">
    <a href="#">About Us</a>
    <a href="#">Services</a>
    <a href="#">Privacy Policy</a>
    <a href="#">Contact</a>
  </div>
  <p>&copy; 2025 Healthcare Appointment System. All rights reserved.</p>
</footer>

</body>
</html>
