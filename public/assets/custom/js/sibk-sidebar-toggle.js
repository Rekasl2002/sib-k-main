/**
 * File Path: public/assets/custom/js/sibk-sidebar-toggle.js
 * Sidebar: desktop hide total, mobile offcanvas
 */
(function () {
  'use strict';

  const body = document.body;
  const sidebar = document.querySelector('.vertical-menu.sibk-sidebar, .vertical-menu');
  if (!sidebar) return;

  // cari tombol hamburger yang umum dipakai Qovex/Skote juga
  const toggleBtn = document.querySelector(
    '#vertical-menu-btn, #sibk-sidebar-toggle, [data-sibk-sidebar-toggle], .sibk-sidebar-toggle'
  );

  // pastikan backdrop ada
  let backdrop = document.querySelector('.sibk-sidebar-backdrop');
  if (!backdrop) {
    backdrop = document.createElement('div');
    backdrop.className = 'sibk-sidebar-backdrop';
    document.body.appendChild(backdrop);
  }

  const mq = window.matchMedia('(max-width: 991.98px)');
  const isMobile = () => mq.matches;

  const openMobile = () => {
    body.classList.add('sibk-sidebar-open');
    body.classList.add('sidebar-enable'); // kompat dengan Qovex mobile
    sidebar.classList.add('show');
  };

  const closeMobile = () => {
    body.classList.remove('sibk-sidebar-open');
    body.classList.remove('sidebar-enable');
    sidebar.classList.remove('show');
  };

  const toggleDesktop = () => {
    body.classList.toggle('sibk-sidebar-hidden');
  };

  const toggle = () => {
    if (isMobile()) {
      const opened = body.classList.contains('sibk-sidebar-open') || body.classList.contains('sidebar-enable');
      opened ? closeMobile() : openMobile();
    } else {
      // bersihkan state mobile kalau pindah ke desktop
      closeMobile();
      toggleDesktop();
    }
  };

  // Tangkap klik lebih dulu supaya handler template tidak “makan” event-nya
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
      toggle();
    }, true); // capture = true
  }

  // klik backdrop menutup
  backdrop.addEventListener('click', function () {
    if (isMobile()) closeMobile();
  });

  // ESC menutup
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && isMobile()) closeMobile();
  });

  // saat breakpoint berubah, rapikan state biar tidak nyangkut
  const normalize = () => {
    if (isMobile()) {
      body.classList.remove('sibk-sidebar-hidden'); // desktop state jangan kebawa ke mobile
      closeMobile(); // mobile default tertutup
    } else {
      closeMobile();
    }
  };

  if (mq.addEventListener) mq.addEventListener('change', normalize);
  else mq.addListener(normalize);

  normalize();
})();
