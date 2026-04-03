<?php

session_start();


function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=1200">
  <title>Supershop - Desktop UI</title>

  <link rel="stylesheet" href="./css/swap.css">
</head>
<body>
  <div class="container">

 
    <div class="card topbar">
      <div class="brand">Supershop</div>
      <div class="top-actions">
        <a class="btn-icon" title="Login" href="/Supershop_webtech_project/php/login.php" style="text-decoration:none;">
  <div class="emoji">🔑</div>


        </a>
      </div>
    </div>

    <section class="deals">
      <div class="container">
        <header class="deals-head">
          <h2>Great Deals</h2>
          <div class="promo-chip">Today Only</div>
        </header>

        <div class="deal-slider" id="dealSlider">
     
          <article class="slide is-active">
            <img src="./img/tomato.jpg" alt="Tomato Offer">
            <div class="overlay">
              <p class="eyebrow">App Exclusive</p>
              <h3>First Order <span class="off">10% OFF</span></h3>
              <p class="sub">Use code: <strong>WC10</strong></p>
              <a href="#" class="cta">Shop Now</a>
            </div>
          </article>

          <article class="slide">
            <img src="./img/mango-still-life.jpg" alt="Mango Offer">
            <div class="overlay">
              <p class="eyebrow">Soft Drinks</p>
              <h3>Buy 2 Get 1 <span class="off">FREE</span></h3>
              <p class="sub">Selected brands only</p>
              <a href="#" class="cta">Grab Offer</a>
            </div>
          </article>

          <article class="slide">
            <img src="./img/chicken.jpg" alt="Chicken Offer">
            <div class="overlay">
              <p class="eyebrow">Breakfast Essentials</p>
              <h3><span class="off">Up to 30% OFF</span></h3>
              <p class="sub">Eggs • Bread • Spreads</p>
              <a href="#" class="cta">Save More</a>
            </div>
          </article>

        
          <button class="nav prev" aria-label="Previous slide">‹</button>
          <button class="nav next" aria-label="Next slide">›</button>
          <div class="dots" aria-label="Slide indicators"></div>
        </div>
      </div>
    </section>
  

  </div>

  <script>
    (function () {
      const slider = document.getElementById('dealSlider');
      if (!slider) return;

      const slides = Array.from(slider.querySelectorAll('.slide'));
      const prevBtn = slider.querySelector('.prev');
      const nextBtn = slider.querySelector('.next');
      const dotsWrap = slider.querySelector('.dots');

      let index = slides.findIndex(s => s.classList.contains('is-active'));
      if (index < 0) index = 0;

      dotsWrap.innerHTML = '';
      slides.forEach((_, i) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'dot' + (i === index ? ' active' : '');
        b.setAttribute('aria-label', 'Go to slide ' + (i + 1));
        b.addEventListener('click', () => go(i));
        dotsWrap.appendChild(b);
      });
      const dots = Array.from(dotsWrap.children);

      function go(i) {
        slides[index].classList.remove('is-active');
        dots[index].classList.remove('active');
        index = (i + slides.length) % slides.length;
        slides[index].classList.add('is-active');
        dots[index].classList.add('active');
      }

      function next() { go(index + 1); }
      function prev() { go(index - 1); }

      nextBtn.addEventListener('click', next);
      prevBtn.addEventListener('click', prev);

     
      let timer = setInterval(next, 5000);
      slider.addEventListener('mouseenter', () => clearInterval(timer));
      slider.addEventListener('mouseleave', () => (timer = setInterval(next, 5000)));
    })();
  </script>
</body>
</html>
