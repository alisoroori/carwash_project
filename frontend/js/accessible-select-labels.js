/* Runtime accessibility helper for <select> elements
   - Ensures each <select> has an accessible name (title + aria-label) when missing.
   - Does NOT overwrite existing title, aria-label, or aria-labelledby attributes.
   - Tries to infer label from: <label for=...>, wrapping <label>, previous sibling label/text, or name/id.
*/
(function(){
  'use strict';

  function inferFromIdOrName(str){
    if(!str) return null;
    // convert snake-case/camelCase/kebab to words
    var s = String(str).replace(/[-_]+/g,' ').replace(/([a-z])([A-Z])/g,'$1 $2');
    s = s.replace(/\b([a-z])/g, function(m){ return m.toUpperCase(); });
    return s;
  }

  function getLabelText(select){
    // 1. label[for=id]
    if(select.id){
      var lbl = document.querySelector('label[for="' + CSS.escape(select.id) + '"]');
      if(lbl && lbl.textContent && lbl.textContent.trim().length>0) return lbl.textContent.trim();
    }

    // 2. wrapping label
    var wrap = select.closest('label');
    if(wrap && wrap.textContent && wrap.textContent.trim().length>0){
      // exclude text of the select itself (unlikely) - return trimmed
      return wrap.textContent.trim();
    }

    // 3. previous element sibling that is a label or has .label/.field-label
    var prev = select.previousElementSibling;
    if(prev){
      if(prev.tagName.toLowerCase() === 'label' && prev.textContent.trim()) return prev.textContent.trim();
      if(/label|field-label|control-label/.test(prev.className) && prev.textContent.trim()) return prev.textContent.trim();
    }

    // 4. look upwards for a .form-group or .field with a label inside
    var ancestor = select.closest('.form-group, .field, .form-row');
    if(ancestor){
      var lbl2 = ancestor.querySelector('label');
      if(lbl2 && lbl2.textContent.trim()) return lbl2.textContent.trim();
    }

    // 5. infer from name or id
    var name = select.getAttribute('name') || select.id || '';
    if(name) return inferFromIdOrName(name);

    return null;
  }

  function ensureSelectLabels(){
    try{
      document.querySelectorAll('select').forEach(function(sel){
        if(sel.hasAttribute('title') || sel.hasAttribute('aria-label') || sel.hasAttribute('aria-labelledby')) return;
        var label = getLabelText(sel);
        if(!label) label = 'Seçim'; // generic Turkish word for choice
        sel.setAttribute('title', label);
        sel.setAttribute('aria-label', label);
      });
    }catch(e){
      console.warn('accessible-select-labels error', e && e.message);
    }
  }

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ensureSelectLabels);
  else ensureSelectLabels();

})();
