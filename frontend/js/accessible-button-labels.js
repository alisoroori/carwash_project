/* Runtime accessibility patch
   Adds title and aria-label to icon-only <button> elements when missing.
   - Does NOT overwrite existing title or aria-label attributes.
   - Infers labels from common icon class names (Font Awesome) with Turkish defaults.
   - Safe: runs on DOMContentLoaded and only updates buttons with no visible text.
*/
(function(){
  'use strict';

  function inferLabelFromIcon(icon){
    var cls = (icon && icon.className) ? icon.className : '';
    var m = cls.match(/fa-(\w[\w-]*)/i) || cls.match(/icon-(\w[\w-]*)/i);
    var key = m ? m[1].toLowerCase() : null;
    var map = {
      'edit': 'Düzenle', 'pencil-alt': 'Düzenle', 'pencil': 'Düzenle',
      'eye': 'Görüntüle', 'eye-slash': 'Görüntüle',
      'trash': 'Sil', 'trash-alt': 'Sil', 'times': 'Sil',
      'check': 'Onayla', 'check-circle': 'Onayla',
      'chevron-left': 'Önceki', 'chevron-right': 'Sonraki', 'chevron-up': 'Başa Dön', 'chevron-down': 'Aşağı',
      'bars': 'Menüyü Aç', 'sign-in-alt': 'Giriş', 'sign-out-alt': 'Çıkış',
      'download': 'İndir', 'print': 'Yazdır', 'envelope': 'İleti Gönder',
      'star': 'Favori', 'heart': 'Favori', 'eye': 'Görüntüle'
    };
    if(key && map[key]) return map[key];
    if(key) return key.replace(/[-_]/g,' ').replace(/\b\w/g,function(s){return s.toUpperCase();});
    return 'İşlem';
  }

  function isIconOnlyButton(btn){
    // Has visible text?
    var text = (btn.textContent || '').trim();
    if(text.length>0) return false;
    // If button has children and all are <i> or <svg> (or visually-hidden spans), treat as icon-only
    var children = Array.prototype.slice.call(btn.children || []);
    if(children.length===0) return false; // no children and no text -> skip
    return children.every(function(ch){
      var t = ch.tagName.toLowerCase();
      if(t==='i' || t==='svg') return true;
      if(t==='span' && /sr-only|visually-hidden/.test(ch.className)) return true;
      return false;
    });
  }

  function ensureAccessibleLabels(){
    try{
      document.querySelectorAll('button').forEach(function(btn){
        if(btn.hasAttribute('title') || btn.hasAttribute('aria-label')) return; // don't overwrite
        if(!isIconOnlyButton(btn)) return;
        var firstIcon = btn.querySelector('i, svg, .fa, .fas, [class*="fa-"]');
        var label = null;
        if(firstIcon) label = inferLabelFromIcon(firstIcon);
        if(!label) label = 'İşlem';
        btn.setAttribute('title', label);
        btn.setAttribute('aria-label', label);
      });
    }catch(e){
      // Non-fatal
      console.warn('accessible-button-labels failed:', e && e.message);
    }
  }

  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ensureAccessibleLabels);
  else ensureAccessibleLabels();
})();
