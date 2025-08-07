// admindash.js

document.addEventListener('DOMContentLoaded', () => {
  console.log("Welcome to the Admin Dashboard!");

  // Animate cards on hover with a smooth scale effect
  const cards = document.querySelectorAll('.card');

  cards.forEach(card => {
    card.addEventListener('mouseenter', () => {
      card.style.transform = 'translateY(-8px) scale(1.05)';
      card.style.boxShadow = '0 8px 20px rgba(0,0,0,0.15)';
    });

    card.addEventListener('mouseleave', () => {
      card.style.transform = 'translateY(0) scale(1)';
      card.style.boxShadow = '0 4px 12px rgba(0,0,0,0.08)';
    });
  });
});
