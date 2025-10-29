(function () {
  'use strict';

  // Delegated handler: intercept "Rezervasyon Yap" clicks across dashboard listings.
  // Matches: elements with data-book-carwash, .book-btn, or buttons/links that include ?carwash= in href.
  function findBookTrigger(el) {
    return el.closest('[data-book-carwash], .book-btn, [data-booking], [data-action="book"]');
  }

  function parseCarwashIdFromHref(href) {
    if (!href) return null;
    try {
      const url = new URL(href, window.location.origin);
      return url.searchParams.get('carwash') || url.searchParams.get('id') || null;
    } catch (e) {
      return null;
    }
  }

  document.addEventListener('click', function (evt) {
    const trigger = findBookTrigger(evt.target);
    if (!trigger) return;

    // Avoid interfering with non-dashboard pages if user intentionally navigates
    // The handler will try to open embedded form only if the embedded form element exists.
    const embeddedFormExists = !!document.getElementById('newReservationForm') || !!document.getElementById('embeddedBooking') || !!document.getElementById('bookingForm');
    if (!embeddedFormExists) return; // allow normal navigation

    evt.preventDefault();

    // Determine carwash id and name from data- attributes or href
    let carwashId = trigger.dataset.bookCarwash || trigger.dataset.carwashId || trigger.dataset.id || null;
    let carwashName = trigger.dataset.carwashName || trigger.dataset.name || trigger.getAttribute('aria-label') || '';

    if (!carwashId) {
      const href = trigger.getAttribute('href') || trigger.getAttribute('data-href') || '';
      carwashId = parseCarwashIdFromHref(href);
    }
    if (!carwashId) {
      console.warn('embedded-booking: could not determine carwash id from clicked element');
      return;
    }
    carwashId = Number(carwashId);

    // If dashboard-level helper exists, prefer it (keeps existing behavior)
    if (typeof window.selectCarWashForReservation === 'function') {
      try {
        window.selectCarWashForReservation(evt, carwashId, carwashName);
        return;
      } catch (e) {
        // fallback if helper throws
        console.error('embedded-booking: selectCarWashForReservation failed, falling back', e);
      }
    }

    // Fallback: try to find carwash record from global arrays produced by the page
    let carwashRecord = null;
    if (Array.isArray(window.allCarWashes)) {
      carwashRecord = window.allCarWashes.find(c => Number(c.id) === carwashId) || null;
    }
    if (!carwashRecord && Array.isArray(window.carwashes)) {
      carwashRecord = window.carwashes.find(c => Number(c.id) === carwashId) || null;
    }

    // Populate city and district selects if data is available
    const cityValue = carwashRecord ? (carwashRecord.city || carwashRecord.town || '') : '';
    const districtValue = carwashRecord ? (carwashRecord.district || carwashRecord.region || '') : '';

    // Fill city selector(s)
    const citySel = document.getElementById('cityFilter') || document.querySelector('select[name="city"]');
    if (citySel && cityValue) {
      // if option doesn't exist, add it
      if (![...citySel.options].some(o => o.value === cityValue)) {
        const opt = document.createElement('option');
        opt.value = cityValue; opt.textContent = cityValue;
        citySel.appendChild(opt);
      }
      citySel.value = cityValue;
      citySel.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Fill district selector(s)
    const districtSel = document.getElementById('districtFilter') || document.querySelector('select[name="district"]');
    if (districtSel && districtValue) {
      if (![...districtSel.options].some(o => o.value === districtValue)) {
        const opt = document.createElement('option');
        opt.value = districtValue; opt.textContent = districtValue;
        districtSel.appendChild(opt);
      }
      districtSel.value = districtValue;
      districtSel.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Ensure the carwash/location select exists and set value
    const locationSelect = document.getElementById('location') || document.querySelector('select[name="carwash_id"]') || document.querySelector('select[name="location"]');
    if (locationSelect) {
      // create option if missing
      if (![...locationSelect.options].some(o => Number(o.value) === carwashId)) {
        const opt = document.createElement('option');
        opt.value = String(carwashId);
        opt.textContent = (carwashRecord && (carwashRecord.business_name || carwashRecord.name)) || carwashName || ('Carwash ' + carwashId);
        locationSelect.appendChild(opt);
      }
      locationSelect.value = String(carwashId);
      locationSelect.dispatchEvent(new Event('change', { bubbles: true }));

      // If page provides a loadServicesForCarwash function, call it so services populate for that carwash
      if (typeof window.loadServicesForCarwash === 'function') {
        try { window.loadServicesForCarwash(carwashId); } catch (e) { /* ignore */ }
      }
    }

    // Reveal the embedded booking form
    if (typeof window.showNewReservationForm === 'function') {
      try {
        window.showNewReservationForm();
      } catch (e) {
        const nf = document.getElementById('newReservationForm');
        if (nf) nf.classList.remove('hidden');
      }
    } else {
      const nf = document.getElementById('newReservationForm') || document.getElementById('embeddedBooking') || document.getElementById('bookingForm');
      if (nf) {
        nf.classList.remove('hidden');
        nf.scrollIntoView({ behavior: 'smooth' });
      }
    }

    // Optionally focus first input
    setTimeout(() => {
      const focusEl = document.querySelector('#newReservationForm input, #newReservationForm select, #embeddedBooking input, #embeddedBooking select');
      if (focusEl) focusEl.focus();
    }, 200);
  }, false);

})();