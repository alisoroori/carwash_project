<?php
declare(strict_types=1);

namespace App\Classes;
?>
<script>
  // Responsive header/index JavaScript moved into a pure HTML/JS section.
  // This file previously contained stray PHP declarations at the top; closing
  // the PHP block keeps the original JS intact while making the file parse.

  // Update active nav links by href
  function updateActiveNavLink(activeHref) {
    document.querySelectorAll('.nav-link, .mobile-nav-link').forEach(link => {
      link.classList.remove('active');
    });

    // Add active class to current nav links
    document.querySelectorAll(`a[href="${activeHref}"]`).forEach(link => {
      if (link.classList.contains('nav-link') || link.classList.contains('mobile-nav-link')) {
        link.classList.add('active');
      }
    });
  }

  // Intersection Observer for automatic active section detection with responsive margins
  function createIntersectionObserver() {
    const observerOptions = {
      root: null,
      rootMargin: isMobile ? '-64px 0px -50% 0px' : '-80px 0px -50% 0px',
      threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const targetId = '#' + entry.target.id;
          updateActiveNavLink(targetId);
        }
      });
    }, observerOptions);

    // Observe all sections
    const sections = document.querySelectorAll('#home, #services, #about, #contact');
    sections.forEach(section => {
      observer.observe(section);
    });

    return observer;
  }

  // Touch gesture support for mobile menu
  let touchStartY = 0;
  let touchEndY = 0;

  if (touchDevice) {
    document.addEventListener('touchstart', function(event) {
      touchStartY = event.changedTouches[0].screenY;
    }, { passive: true });

    document.addEventListener('touchend', function(event) {
      touchEndY = event.changedTouches[0].screenY;

      const menu = document.getElementById('mobileMenu');
      if (menu && menu.classList.contains('active')) {
        // Swipe up to close menu
        if (touchStartY - touchEndY > 50) {
          toggleMobileMenu();
        }
      }
    }, { passive: true });
  }

  // Add loading states to buttons with responsive feedback
  document.querySelectorAll('.cta-button, .secondary-button').forEach(button => {
    button.addEventListener('click', function() {
      if (this.href && !this.href.includes('#')) {
        const originalContent = this.innerHTML;
        const spinner = '<i class="fas fa-spinner fa-spin mr-2"></i>';
        const loadingText = isMobile ? 'Yükleniyor...' : 'Yükleniyor...';

        this.innerHTML = spinner + loadingText;
        this.style.pointerEvents = 'none';
        this.style.opacity = '0.8';

        // Restore after a short delay if still on page
        setTimeout(() => {
          this.innerHTML = originalContent;
          this.style.pointerEvents = '';
          this.style.opacity = '';
        }, 3000);
      }
    });
  });

  // User menu dropdown behavior for touch devices
  if (touchDevice) {
    const userMenuButton = document.querySelector('.user-menu-button');
    const userMenu = document.querySelector('.user-menu');

    if (userMenuButton && userMenu) {
      userMenuButton.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        userMenu.classList.toggle('active');
      });

      document.addEventListener('click', function(event) {
        if (!userMenu.contains(event.target)) {
          userMenu.classList.remove('active');
        }
      });
    }
  } else {
    // For desktop devices, prevent default button behavior
    const userMenuButton = document.querySelector('.user-menu-button');
    if (userMenuButton) {
      userMenuButton.addEventListener('click', function(e) {
        e.preventDefault();
      });
    }
  }

  // Initialize on DOM content loaded
  document.addEventListener('DOMContentLoaded', function() {
    updateResponsiveVariables();
    createIntersectionObserver();

    // Add enhanced touch feedback for mobile
    if (touchDevice) {
      document.querySelectorAll('.nav-link, .mobile-nav-link, .cta-button, .secondary-button').forEach(element => {
        element.addEventListener('touchstart', function() {
          this.style.transform = 'scale(0.98)';
        }, { passive: true });

        element.addEventListener('touchend', function() {
          setTimeout(() => {
            this.style.transform = '';
          }, 150);
        }, { passive: true });
      });
    }

    console.log('CarWash Responsive Index Header loaded successfully!');
    console.log('Device type:', isMobile ? 'Mobile' : isTablet ? 'Tablet' : 'Desktop');
    console.log('Touch device:', touchDevice);
  });

  // Prevent zoom on double tap for better mobile UX
  if (touchDevice) {
    let lastTouchEnd = 0;
    document.addEventListener('touchend', function (event) {
      const now = (new Date()).getTime();
      if (now - lastTouchEnd <= 300) {
        event.preventDefault();
      }
      lastTouchEnd = now;
    }, false);
  }

  // Performance optimization: Debounced scroll handler
  let scrollTimeout;
  let lastScrollY = window.scrollY;

  window.addEventListener('scroll', function() {
    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(function() {
      if (Math.abs(window.scrollY - lastScrollY) > 5) {
        handleHeaderScroll();
        lastScrollY = window.scrollY;
      }
    }, 10);
  }, { passive: true });
</script>
