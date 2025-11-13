(function(){
    'use strict';

    function onReady(fn){
        if (document.readyState !== 'loading') return fn();
        document.addEventListener('DOMContentLoaded', fn);
    }

    function fetchFragment(url, el){
        el.setAttribute('aria-busy', 'true');
        return fetch(url, { credentials: 'same-origin' })
            .then(function(resp){
                if (!resp.ok) throw new Error('Network response was not ok: ' + resp.status);
                return resp.text();
            })
            .then(function(html){
                el.innerHTML = html;
                el.dataset.loaded = 'true';
                el.setAttribute('aria-busy', 'false');
                el.classList.add('loaded-fragment');
            })
            .catch(function(err){
                console.error('Failed to load fragment:', url, err);
                el.setAttribute('aria-busy', 'false');
                el.classList.add('fragment-load-error');
                el.innerHTML = '<div class="p-4 bg-red-50 border border-red-100 rounded text-sm">İçerik yüklenemedi. Lütfen sayfayı yenileyin.</div>';
            });
    }

    onReady(function(){
        if (!('IntersectionObserver' in window)) {
            // Fallback: load all fragments immediately
            document.querySelectorAll('[data-load-url]').forEach(function(el){
                var url = el.getAttribute('data-load-url');
                if (url && !el.dataset.loaded) fetchFragment(url, el);
            });
            return;
        }

        var observer = new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if (!entry.isIntersecting) return;
                var el = entry.target;
                if (el.dataset.loaded) {
                    observer.unobserve(el);
                    return;
                }
                var url = el.getAttribute('data-load-url');
                if (!url) return;
                // Load and then unobserve
                fetchFragment(url, el).then(function(){
                    observer.unobserve(el);
                });
            });
        }, {
            root: null,
            rootMargin: '150px 0px',
            threshold: 0.1
        });

        document.querySelectorAll('[data-load-url]').forEach(function(el){
            // mark as interactive region
            el.setAttribute('role', el.getAttribute('role') || 'region');
            el.setAttribute('aria-busy', 'false');
            observer.observe(el);
        });
    });
})();
