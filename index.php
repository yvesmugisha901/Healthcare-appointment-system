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
  --primary: #1e3a8a;
  --primary-light: #3b82f6;
  --secondary: #10b981;
  --text: #1f2937;
  --text-light: #4b5563;
  --bg: linear-gradient(135deg, #e0f2fe, #f3f4f6); /* gradient soft background */
  --white: #fff;
  --shadow: 0 5px 15px rgba(0,0,0,0.12);
  --transition: all 0.3s ease;
}

* { margin:0; padding:0; box-sizing:border-box; }

body {
  font-family:'Poppins', sans-serif;
  background: var(--bg);
  color: var(--text);
  line-height:1.6;
  display:flex;
  flex-direction:column;
  min-height:100vh;
}

/* Header */
header {
  background: linear-gradient(90deg, var(--primary), var(--primary-light));
  color: var(--white);
  padding: 1.5rem 1.5rem; /* reduced padding */
  text-align:center;

}
header h1 { font-size:1.9rem; font-weight:700; margin-bottom:0.3rem; }
header p { font-size:0.95rem; opacity:0.9; }

/* Hero */
.hero {
  display:flex;
  align-items:center;
  justify-content:center;
  flex-wrap:wrap;
  max-width:1200px;
  margin:2rem auto 3rem;
  padding:0 1.5rem;
  gap:2rem;
}
.hero-text { flex:1; text-align:center; }
.hero-text h2 {
  font-size:1.8rem;
  color: var(--primary);
  margin-bottom:1rem;
}
.hero-text p { font-size:1.05rem; color: var(--text-light); margin-bottom:2rem; }
.btn-group { display:flex; justify-content:center; gap:1rem; flex-wrap:wrap; }

/* Buttons */
.btn {
  padding:0.8rem 1.6rem;
  border-radius:8px;
  font-weight:600;
  text-decoration:none;
  transition: var(--transition);
}
.btn-primary {
  background: linear-gradient(135deg, #4facfe, #00f2fe); /* blue gradient like login */
  color: var(--white);
}
.btn-primary:hover {
  background: linear-gradient(135deg, #00f2fe, #4facfe);
  transform: translateY(-3px);
}
.btn-register {
  background: linear-gradient(135deg, #10b981, #06b6d4); /* teal/cyan gradient */
  color: var(--white);
}
.btn-register:hover {
  background: linear-gradient(135deg, #06b6d4, #10b981);
  transform: translateY(-3px);
}

/* Hero Image */
.hero-image { flex:1; text-align:center; }
.hero-image img {
  width:100%;
  max-width:400px;
  border-radius:12px;
  box-shadow:0 12px 30px rgba(0,0,0,0.12);
  transition: transform 0.3s ease;
}
.hero-image:hover img { transform: scale(1.03); }

/* Features */
.features {
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(280px,1fr));
  gap:1.5rem;
  max-width:1200px;
  margin:3rem auto;
  padding:0 1.5rem;
}
.feature-card {
  background: #fefefe; /* slightly off-white */
  padding:2rem 1.5rem;
  border-radius:12px;
  box-shadow: var(--shadow);
  text-align:center;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.feature-card:hover {
  transform: translateY(-5px);
  box-shadow:0 12px 25px rgba(0,0,0,0.15);
}
.feature-icon { font-size:2rem; color: var(--primary); margin-bottom:1rem; }

/* Footer */
footer {
  background: #1f2937;
  color: var(--white);
  padding:1.5rem;
  text-align:center;
  margin-top:auto;
  border-top-left-radius: 12px;
  border-top-right-radius: 12px;
}
.footer-links {
  display:flex;
  justify-content:center;
  flex-wrap:wrap;
  gap:1.5rem;
  margin-bottom:1rem;
}
.footer-links a {
  color: #9ca3af;
  text-decoration:none;
  transition: color 0.2s;
}
.footer-links a:hover { color: var(--white); }
footer p { font-size:0.9rem; color:#d1d5db; }

/* Responsive */
@media (max-width:768px) { .hero { flex-direction:column; } .hero-text, .hero-image { width:100%; } }
@media (max-width:480px) { header h1 { font-size:1.6rem; } .hero-text h2 { font-size:1.4rem; } }
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
      <a href="register.php" class="btn btn-register"><i class="fas fa-user-plus"></i> Register</a>
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
