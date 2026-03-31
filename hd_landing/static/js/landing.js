/**
 * 艺为微信大屏互动 - 官网落地页脚本
 */
(function() {
    'use strict';

    // 移动端菜单切换
    const navToggle = document.getElementById('navToggle');
    const navbar = document.getElementById('navbar');
    if (navToggle) {
        navToggle.addEventListener('click', function() {
            navbar.classList.toggle('open');
        });
    }

    // 滚动时导航栏样式
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
        } else {
            navbar.style.boxShadow = '0 1px 10px rgba(0,0,0,0.05)';
        }
    });

    // 导航链接高亮
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.section, .hero');

    function updateActiveLink() {
        let current = '';
        sections.forEach(function(section) {
            const top = section.offsetTop - 100;
            if (window.scrollY >= top) {
                current = section.getAttribute('id');
            }
        });
        navLinks.forEach(function(link) {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    }

    window.addEventListener('scroll', updateActiveLink);

    // 背景粒子动画（简化版）
    const particlesEl = document.getElementById('particles');
    if (particlesEl) {
        var canvas = document.createElement('canvas');
        canvas.style.cssText = 'position:absolute;inset:0;width:100%;height:100%';
        particlesEl.appendChild(canvas);
        var ctx = canvas.getContext('2d');
        var particles = [];
        var w, h;

        function resize() {
            w = canvas.width = particlesEl.offsetWidth;
            h = canvas.height = particlesEl.offsetHeight;
        }
        resize();
        window.addEventListener('resize', resize);

        for (var i = 0; i < 60; i++) {
            particles.push({
                x: Math.random() * w,
                y: Math.random() * h,
                r: Math.random() * 2 + 1,
                dx: (Math.random() - 0.5) * 0.5,
                dy: (Math.random() - 0.5) * 0.5,
                a: Math.random() * 0.4 + 0.1
            });
        }

        function drawParticles() {
            ctx.clearRect(0, 0, w, h);
            for (var i = 0; i < particles.length; i++) {
                var p = particles[i];
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(165,180,252,' + p.a + ')';
                ctx.fill();
                p.x += p.dx;
                p.y += p.dy;
                if (p.x < 0 || p.x > w) p.dx = -p.dx;
                if (p.y < 0 || p.y > h) p.dy = -p.dy;
            }

            // 绘制连线
            for (var i = 0; i < particles.length; i++) {
                for (var j = i + 1; j < particles.length; j++) {
                    var dx = particles[i].x - particles[j].x;
                    var dy = particles[i].y - particles[j].y;
                    var dist = Math.sqrt(dx * dx + dy * dy);
                    if (dist < 120) {
                        ctx.beginPath();
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.strokeStyle = 'rgba(165,180,252,' + (0.15 * (1 - dist / 120)) + ')';
                        ctx.lineWidth = 0.5;
                        ctx.stroke();
                    }
                }
            }

            requestAnimationFrame(drawParticles);
        }
        drawParticles();
    }

    // 滚动动画（IntersectionObserver）
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.feature-card, .advantage-card, .scene-card, .pricing-card, .case-card, .step').forEach(function(el) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    }

    // 数字滚动动画
    function animateCounter(el, target) {
        var current = 0;
        var increment = Math.ceil(target / 60);
        var timer = setInterval(function() {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            el.textContent = current.toLocaleString() + '+';
        }, 20);
    }

    if ('IntersectionObserver' in window) {
        var statsObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var nums = entry.target.querySelectorAll('.stat-num');
                    nums.forEach(function(num) {
                        var text = num.textContent.replace(/[^0-9]/g, '');
                        var target = parseInt(text) || 0;
                        if (target > 0) animateCounter(num, target);
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        var statsSection = document.querySelector('.hero-stats');
        if (statsSection) statsObserver.observe(statsSection);
    }
})();
