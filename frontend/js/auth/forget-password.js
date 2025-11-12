// External script for forget_password page
// Wrapped in DOMContentLoaded and uses null checks to avoid runtime errors

document.addEventListener('DOMContentLoaded', function () {
  const safeGet = id => document.getElementById(id) || null;
  let currentTab = 'email';

  function setActiveTabClasses(tab) {
    const emailTab = safeGet('emailTab');
    const phoneTab = safeGet('phoneTab');
    if (!emailTab || !phoneTab) return;

    if (tab === 'email') {
      emailTab.classList.add('tab-active');
      emailTab.classList.remove('tab-inactive');
      phoneTab.classList.add('tab-inactive');
      phoneTab.classList.remove('tab-active');
    } else {
      phoneTab.classList.add('tab-active');
      phoneTab.classList.remove('tab-inactive');
      emailTab.classList.add('tab-inactive');
      emailTab.classList.remove('tab-active');
    }
  }

  function switchTab(tab) {
    currentTab = tab;
    setActiveTabClasses(tab);

    const emailContent = safeGet('emailContent');
    const phoneContent = safeGet('phoneContent');
    const emailInput = safeGet('emailInput');
    const phoneInput = safeGet('phoneInput');
    const submitBtn = safeGet('submitBtn');

    if (tab === 'email') {
      if (emailContent) emailContent.classList.remove('hidden');
      if (phoneContent) phoneContent.classList.add('hidden');
      if (emailInput) emailInput.required = true;
      if (phoneInput) phoneInput.required = false;
      if (submitBtn) submitBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Sıfırlama Bağlantısı Gönder';
    } else {
      if (phoneContent) phoneContent.classList.remove('hidden');
      if (emailContent) emailContent.classList.add('hidden');
      if (phoneInput) phoneInput.required = true;
      if (emailInput) emailInput.required = false;
      if (submitBtn) submitBtn.innerHTML = '<i class="fas fa-sms mr-2"></i>Doğrulama Kodu Gönder';
    }
  }

  // Expose switchTab globally if templates call it inline
  window.switchTab = switchTab;

  // Attach tab click handlers
  const emailTabBtn = safeGet('emailTab');
  const phoneTabBtn = safeGet('phoneTab');
  if (emailTabBtn) emailTabBtn.addEventListener('click', () => switchTab('email'));
  if (phoneTabBtn) phoneTabBtn.addEventListener('click', () => switchTab('phone'));

  // Form submission handling (null-safe)
  const resetForm = safeGet('resetForm');
  if (resetForm) {
    resetForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const submitBtn = safeGet('submitBtn');
      const originalText = submitBtn ? submitBtn.innerHTML : '';
      if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Gönderiliyor...';
        submitBtn.disabled = true;
      }

      // Simulate async behaviour (replace with real AJAX/fetch as needed)
      setTimeout(() => {
        showMessage('success', currentTab === 'email'
          ? 'Sıfırlama bağlantısı e-posta adresinize gönderildi. Lütfen gelen kutunuzu kontrol edin.'
          : 'Doğrulama kodu telefon numaranıza SMS olarak gönderildi.');

        if (submitBtn) {
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        }
      }, 1200);
    });
  }

  function showMessage(type, message) {
    const messageContainer = safeGet('messageContainer');
    const successMessage = safeGet('successMessage');
    const errorMessage = safeGet('errorMessage');
    const successText = safeGet('successText');
    const errorText = safeGet('errorText');

    if (successMessage) successMessage.classList.add('hidden');
    if (errorMessage) errorMessage.classList.add('hidden');
    if (messageContainer) messageContainer.classList.add('hidden');

    if (type === 'success') {
      if (successText) successText.textContent = message;
      if (successMessage) successMessage.classList.remove('hidden');
      if (messageContainer) messageContainer.classList.remove('hidden');
    } else {
      if (errorText) errorText.textContent = message;
      if (errorMessage) errorMessage.classList.remove('hidden');
      if (messageContainer) messageContainer.classList.remove('hidden');
    }
  }

  // Focus animations for inputs
  const inputs = document.querySelectorAll('input');
  if (inputs && inputs.length) {
    inputs.forEach(input => {
      input.addEventListener('focus', function () {
        this.style.transform = 'scale(1.02)';
        this.style.boxShadow = '0 0 20px rgba(102, 126, 234, 0.25)';
      });
      input.addEventListener('blur', function () {
        this.style.transform = 'scale(1)';
        this.style.boxShadow = 'none';
      });
    });
  }

  // Back-to-top safe handler example (if present)
  const backToTop = safeGet('backToTop');
  if (backToTop) {
    backToTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // Initialize default tab state
  try { switchTab('email'); } catch (e) { /* ignore if DOM not fully matching */ }
});
