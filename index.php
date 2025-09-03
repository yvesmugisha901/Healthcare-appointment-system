<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MedConnect | Healthcare Appointment System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --primary: #2a9d8f;
  --primary-dark: #1d7870;
  --primary-light: #7fcdc3;
  --secondary: #e76f51;
  --neutral-dark: #264653;
  --neutral-medium: #6c757d;
  --neutral-light: #f8f9fa;
  --accent: #f4a261;
  --white: #ffffff;
  --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
  --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
  --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
  --transition: all 0.3s ease;
  --radius: 10px;
  --radius-lg: 16px;
}

* { 
  margin:0; 
  padding:0; 
  box-sizing:border-box; 
}

body {
  font-family: 'Inter', sans-serif;
  background: var(--neutral-light);
  color: var(--neutral-dark);
  line-height:1.6;
  display:flex;
  flex-direction:column;
  min-height:100vh;
}

/* Header */
header {
  background: var(--white);
  color: var(--neutral-dark);
  padding: 1.2rem 1.5rem;
  text-align:center;
  position: relative;
  box-shadow: var(--shadow-sm);
  border-bottom: 1px solid rgba(0,0,0,0.05);
}
header h1 { 
  font-size:1.9rem; 
  font-weight:700; 
  margin-bottom:0.3rem; 
  letter-spacing: -0.5px;
  color: var(--primary-dark);
}
header p { 
  font-size:0.95rem; 
  color: var(--neutral-medium);
  font-weight: 400;
}

/* Navigation */
nav {
  display: flex;
  justify-content: center;
  padding: 0.8rem;
  margin-top: 0.5rem;
}
nav a {
  color: var(--neutral-medium);
  text-decoration: none;
  padding: 0.5rem 1rem;
  font-size: 0.95rem;
  transition: var(--transition);
  border-radius: var(--radius);
  font-weight: 500;
}
nav a:hover, nav a.active {
  color: var(--primary);
  background: rgba(42, 157, 143, 0.08);
}

/* Hero */
.hero {
  display:flex;
  align-items:center;
  justify-content:center;
  flex-wrap:wrap;
  max-width:1200px;
  margin:3rem auto;
  padding:0 1.5rem;
  gap:3rem;
}
.hero-text { 
  flex:1; 
  min-width: 300px;
}
.hero-text h2 {
  font-size:2.4rem;
  color: var(--neutral-dark);
  margin-bottom:1.2rem;
  line-height: 1.2;
  font-weight: 700;
}
.hero-text h2 span {
  color: var(--primary);
}
.hero-text p { 
  font-size:1.1rem; 
  color: var(--neutral-medium); 
  margin-bottom:2.5rem;
  font-weight: 400;
}
.btn-group { 
  display:flex; 
  gap:1rem; 
  flex-wrap:wrap; 
  margin-bottom: 1.5rem;
}

/* Buttons */
.btn {
  padding:0.9rem 1.8rem;
  border-radius: var(--radius);
  font-weight:600;
  text-decoration:none;
  transition: var(--transition);
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  border: none;
  cursor: pointer;
  font-family: 'Inter', sans-serif;
  font-size: 1rem;
  box-shadow: var(--shadow-sm);
}
.btn-primary {
  background: var(--primary);
  color: var(--white);
}
.btn-primary:hover {
  background: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}
.btn-secondary {
  background: var(--white);
  color: var(--primary);
  border: 1px solid var(--primary-light);
}
.btn-secondary:hover {
  background: rgba(42, 157, 143, 0.1);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

/* Hero Image */
.hero-image { 
  flex:1; 
  text-align:center;
  position: relative;
}
.hero-image img {
  width:100%;
  max-width:450px;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  transition: transform 0.5s ease;
}
.hero-image:hover img { 
  transform: scale(1.03); 
}

/* Trust badges */
.trust-badges {
  display: flex;
  gap: 1.5rem;
  margin-top: 2rem;
  flex-wrap: wrap;
}
.trust-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.85rem;
  color: var(--neutral-medium);
  padding: 0.5rem 1rem;
  background: var(--white);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
}
.trust-badge i {
  color: var(--primary);
}

/* Features */
.features {
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(280px,1fr));
  gap:2rem;
  max-width:1200px;
  margin: 4rem auto;
  padding:0 1.5rem;
}
.section-header {
  text-align: center;
  grid-column: 1 / -1;
  margin-bottom: 2rem;
}
.section-header h2 {
  font-size: 2.2rem;
  color: var(--neutral-dark);
  margin-bottom: 0.8rem;
  font-weight: 700;
}
.section-header p {
  color: var(--neutral-medium);
  max-width: 600px;
  margin: 0 auto;
  font-size: 1.1rem;
}
.feature-card {
  background: var(--white);
  padding:2.5rem 1.8rem;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  text-align:center;
  transition: var(--transition);
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(0,0,0,0.03);
}
.feature-card:hover {
  transform: translateY(-8px);
  box-shadow: var(--shadow-lg);
}
.feature-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 4px;
  background: linear-gradient(90deg, var(--primary), var(--secondary));
  opacity: 0.8;
}
.feature-icon { 
  font-size:2.5rem; 
  color: var(--primary); 
  margin-bottom:1.5rem; 
  display: inline-flex;
  justify-content: center;
  align-items: center;
  width: 70px;
  height: 70px;
  border-radius: 50%;
  background: rgba(42, 157, 143, 0.1);
}
.feature-card h3 {
  font-size: 1.3rem;
  margin-bottom: 1rem;
  color: var(--neutral-dark);
}
.feature-card p {
  color: var(--neutral-medium);
  font-size: 0.95rem;
}

/* Stats section */
.stats {
  background: linear-gradient(135deg, var(--neutral-dark) 0%, #2a5368 100%);
  color: white;
  padding: 4rem 1.5rem;
  margin: 4rem 0;
}
.stats-container {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 2rem;
  text-align: center;
}
.stat-item {
  padding: 1.5rem;
}
.stat-number {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: var(--primary-light);
}
.stat-label {
  font-size: 1rem;
  opacity: 0.9;
  font-weight: 400;
}

/* Testimonials */
.testimonials {
  max-width: 1200px;
  margin: 4rem auto;
  padding: 0 1.5rem;
}
.testimonial-card {
  background: var(--white);
  padding: 2rem;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  margin: 1rem;
  position: relative;
}
.testimonial-card::before {
  content: '"';
  position: absolute;
  top: 1rem;
  left: 1.5rem;
  font-size: 4rem;
  color: var(--primary-light);
  opacity: 0.2;
  font-family: Georgia, serif;
}
.testimonial-content {
  position: relative;
  z-index: 1;
}
.testimonial-text {
  font-style: italic;
  color: var(--neutral-medium);
  margin-bottom: 1.5rem;
  line-height: 1.7;
}
.testimonial-author {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.author-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: var(--primary-light);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--white);
  font-weight: 600;
  font-size: 1.2rem;
}
.author-details h4 {
  color: var(--neutral-dark);
  margin-bottom: 0.2rem;
}
.author-details p {
  color: var(--neutral-medium);
  font-size: 0.9rem;
}

/* Footer */
footer {
  background: var(--neutral-dark);
  color: var(--white);
  padding:3rem 1.5rem 1.5rem;
  text-align:center;
  margin-top:auto;
}
.footer-content {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  text-align: left;
  margin-bottom: 2rem;
}
.footer-column h3 {
  font-size: 1.2rem;
  margin-bottom: 1.2rem;
  color: var(--white);
  position: relative;
  display: inline-block;
}
.footer-column h3::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: -8px;
  width: 40px;
  height: 2px;
  background: var(--primary);
}
.footer-column p {
  color: #9ca3af;
  margin-bottom: 1rem;
  font-size: 0.95rem;
}
.footer-links {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
}
.footer-links a {
  color: #9ca3af;
  text-decoration:none;
  transition: color 0.2s;
  font-size: 0.95rem;
}
.footer-links a:hover { 
  color: var(--primary-light); 
  padding-left: 5px;
}
.social-links {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}
.social-links a {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  transition: var(--transition);
}
.social-links a:hover {
  background: var(--primary);
  transform: translateY(-3px);
}
.footer-bottom {
  border-top: 1px solid #374151;
  padding-top: 1.5rem;
  margin-top: 2rem;
}
.footer-bottom p { 
  font-size:0.9rem; 
  color:#9ca3af; 
}

/* Responsive */
@media (max-width:768px) { 
  .hero { 
    flex-direction:column; 
    text-align: center;
    gap: 2rem;
  } 
  .hero-text, .hero-image { 
    width:100%; 
  } 
  .btn-group {
    justify-content: center;
  }
  .trust-badges {
    justify-content: center;
  }
  .section-header h2 {
    font-size: 1.8rem;
  }
}
@media (max-width:480px) { 
  header h1 { font-size:1.6rem; } 
  .hero-text h2 { font-size:1.9rem; } 
  .btn {
    padding: 0.8rem 1.5rem;
    font-size: 0.9rem;
    width: 100%;
    justify-content: center;
  }
  .btn-group {
    flex-direction: column;
  }
}
</style>
</head>
<body>


<header>
  <h1>MedConnect</h1>
  <p>Premium Healthcare Appointment System</p>


<!-- Hero Section -->
<section class="hero">
  <div class="hero-text">
    <h2>Experience <span>Premium Healthcare</span> on Your Terms</h2>
    <p>Book appointments with top healthcare specialists through our sophisticated platform designed for  patients who value both quality and convenience.</p>
    <div class="btn-group">
      <a href="login.php" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i>  Login</a>
      <a href="register.php" class="btn btn-secondary"><i class="fas fa-user-plus"></i> New Registration</a>
    </div>
    <div class="trust-badges">
      <div class="trust-badge">
        <i class="fas fa-shield-alt"></i>
        <span>HIPAA Compliant</span>
      </div>
      <div class="trust-badge">
        <i class="fas fa-user-md"></i>
        <span>Board-Certified Providers</span>
      </div>
      <div class="trust-badge">
        <i class="fas fa-clock"></i>
        <span>Same-Day Appointments</span>
      </div>
    </div>
  </div>
<div class="hero-image">
    <img src="images/doctor.jpg" alt="Modern healthcare appointment booking">
</div>

</section>


<!-- Features Section -->
<section class="features">
  <div class="section-header">
    <h2>Our Premium Services</h2>
    <p>Designed for discerning patients who expect the best healthcare experience</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
    <h3>Flexible Scheduling</h3>
    <p>Book appointments at your convenience with our intuitive booking system.</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon"><i class="fas fa-video"></i></div>
    <h3>Virtual Consultations</h3>
    <p>Connect with healthcare providers from the comfort of your home.</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon"><i class="fas fa-file-medical"></i></div>
    <h3>Digital Health Records</h3>
    <p>Access your medical history and appointment records anytime, anywhere.</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon"><i class="fas fa-bell"></i></div>
    <h3>Smart Reminders</h3>
    <p>Receive personalized notifications for appointments and follow-ups.</p>
  </div>
  <div class="feature-card">
    <div class="feature-icon"><i class="fas fa-stethoscope"></i></div>
    <h3>Specialist Access</h3>
    <p>Connect with top specialists across various medical disciplines.</p>
    </div>
  <div class="feature-card">
    <div class="feature-icon"><i class="fas fa-prescription"></i></div>
    <h3>E-Prescriptions</h3>
    <p>Receive digital prescriptions sent directly to your pharmacy of choice.</p>
  </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials">
  <div class="section-header">
    <h2>Patient Experiences</h2>
    <p>Hear what our patients have to say about our service</p>
  </div>
  <div class="testimonial-card">
    <div class="testimonial-content">
      <p class="testimonial-text">MedConnect has transformed how I manage my family's healthcare. The interface is intuitive, and I can book appointments with specialists in minutes rather than spending hours on the phone.</p>
      <div class="testimonial-author">
        <div class="author-avatar">S</div>
        <div class="author-details">
          <h4>Sarah Johnson</h4>
          <p>Patient since 2022</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer>
  <div class="footer-content">
    <div class="footer-column">
      <h3>MedConnect</h3>
      <p>Premium healthcare appointment system designed for modern patients who value both quality and convenience.</p>
      <div class="social-links">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-linkedin-in"></i></a>
      </div>
    </div>
    <div class="footer-column">
      <h3>Quick Links</h3>
      <div class="footer-links">
        <a href="#">Find a Doctor</a>
        <a href="#">Book Appointment</a>
        <a href="#">Services</a>
        <a href="#">FAQ</a>
        <a href="#">Testimonials</a>
      </div>
    </div>
    <div class="footer-column">
      <h3>Specialties</h3>
      <div class="footer-links">
        <a href="#">Cardiology</a>
        <a href="#">Dermatology</a>
        <a href="#">Orthopedics</a>
        <a href="#">Pediatrics</a>
        <a href="#">Neurology</a>
      </div>
    </div>
    <div class="footer-column">
      <h3>Contact Us</h3>
      <div class="footer-links">
        <a href="#"><i class="fas fa-map-marker-alt"></i> 123 Healthcare Ave, Medical Center</a>
        <a href="#"><i class="fas fa-phone"></i> (555) 123-4567</a>
        <a href="#"><i class="fas fa-envelope"></i> info@medconnect.com</a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; 2023 MedConnect Healthcare System. All rights reserved.</p>
  </div>
</footer>

</body>
</html>