<?php
// Banner slider logic
$banners = $pdo->query("SELECT * FROM banners WHERE status = 1 ORDER BY order_num ASC")->fetchAll(PDO::FETCH_ASSOC);
$slider_speed = (int)($site_settings['banner_slider_speed'] ?? 5) * 1000;
$slider_auto = ($site_settings['banner_slider_auto'] ?? '1') == '1';

if (empty($banners)) return;
?>

<div class="banner-slider-container">
    <div class="banner-slider" id="mainBannerSlider">
        <?php foreach($banners as $index => $banner): ?>
            <div class="banner-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                <a href="<?php echo !empty($banner['link_url']) ? htmlspecialchars($banner['link_url']) : 'javascript:void(0)'; ?>" 
                   <?php echo !empty($banner['link_url']) ? 'target="_blank"' : ''; ?> class="banner-link">
                    <img src="<?php echo htmlspecialchars($banner['image_pc']); ?>" class="banner-img pc-banner" alt="Banner PC">
                    <img src="<?php echo htmlspecialchars($banner['image_mobile']); ?>" class="banner-img mobile-banner" alt="Banner Mobile">
                    <div class="banner-overlay"></div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if(count($banners) > 1): ?>
    <div class="slider-dots">
        <?php foreach($banners as $index => $banner): ?>
            <div class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)"></div>
        <?php endforeach; ?>
    </div>
    <button class="slider-nav prev" onclick="prevSlide()"><i class="fas fa-chevron-left"></i></button>
    <button class="slider-nav next" onclick="nextSlide()"><i class="fas fa-chevron-right"></i></button>
    <?php endif; ?>
</div>

<style>
.banner-slider-container {
    position: relative;
    width: 100%;
    margin-bottom: 2rem;
    border-radius: 25px;
    overflow: hidden;
    aspect-ratio: 1920 / 500;
    background: #111;
    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
}

@media (max-width: 768px) {
    .banner-slider-container {
        aspect-ratio: 800 / 400;
        border-radius: 15px;
    }
}

.banner-slider {
    width: 100%;
    height: 100%;
    position: relative;
}

.banner-slide {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    opacity: 0;
    transition: opacity 1s ease-in-out, transform 1s ease-in-out;
    transform: scale(1.05);
    visibility: hidden;
}

.banner-slide.active {
    opacity: 1;
    transform: scale(1);
    visibility: visible;
    z-index: 5;
}

.banner-link { display: block; width: 100%; height: 100%; position: relative; }
.banner-img { width: 100%; height: 100%; object-fit: cover; display: block; }

.pc-banner { display: block; }
.mobile-banner { display: none; }

@media (max-width: 768px) {
    .pc-banner { display: none; }
    .mobile-banner { display: block; }
}

.banner-overlay {
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.4) 100%);
    pointer-events: none;
}

.slider-nav {
    position: absolute;
    top: 50%; transform: translateY(-50%);
    background: rgba(0,0,0,0.3);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
    color: white;
    width: 50px; height: 50px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: 0.3s;
    opacity: 0;
}

.banner-slider-container:hover .slider-nav { opacity: 1; }
.slider-nav:hover { background: var(--primary-red); border-color: var(--primary-red); }

.slider-nav.prev { left: 20px; }
.slider-nav.next { right: 20px; }

.slider-dots {
    position: absolute;
    bottom: 20px; left: 50%; transform: translateX(-50%);
    display: flex; gap: 10px; z-index: 10;
}

.dot {
    width: 12px; height: 12px;
    background: rgba(255,255,255,0.3);
    border-radius: 50%;
    cursor: pointer;
    transition: 0.3s;
}

.dot.active {
    background: var(--primary-red);
    width: 30px;
    border-radius: 10px;
}

@media (max-width: 768px) {
    .slider-nav { display: none; }
    .slider-dots { bottom: 10px; }
    .dot { width: 8px; height: 8px; }
    .dot.active { width: 20px; }
}
</style>

<script>
let currentSlide = 0;
const slides = document.querySelectorAll('.banner-slide');
const dots = document.querySelectorAll('.dot');
const totalSlides = slides.length;
const autoSlide = <?php echo $slider_auto ? 'true' : 'false'; ?>;
const slideInterval = <?php echo $slider_speed; ?>;
let slideTimer;

function showSlide(index) {
    slides.forEach(s => s.classList.remove('active'));
    dots.forEach(d => d.classList.remove('active'));
    
    currentSlide = (index + totalSlides) % totalSlides;
    
    slides[currentSlide].classList.add('active');
    if (dots[currentSlide]) dots[currentSlide].classList.add('active');
}

function nextSlide() {
    showSlide(currentSlide + 1);
    resetTimer();
}

function prevSlide() {
    showSlide(currentSlide - 1);
    resetTimer();
}

function goToSlide(index) {
    showSlide(index);
    resetTimer();
}

function resetTimer() {
    if (autoSlide) {
        clearInterval(slideTimer);
        slideTimer = setInterval(nextSlide, slideInterval);
    }
}

if (autoSlide) {
    slideTimer = setInterval(nextSlide, slideInterval);
}
</script>
