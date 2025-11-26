<?php
// Profile header fragment - shared between Customer and Seller dashboards
// Expects variables in scope: $user_name, $user_email, $profile_src, $home_url, $logout_url
?>

<!-- Profile Header Fragment -->
<div x-data="{ open: false }" class="relative" x-cloak>
    <button @click="open = !open" @keydown.escape="open = false" @click.away="open = false"
        class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none"
        :aria-expanded="open.toString()" aria-haspopup="true">

        <div id="headerProfileContainer" class="rounded-full overflow-hidden shadow-sm flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600" style="width:44px;height:44px;">
            <img id="userAvatarTop" src="<?php echo htmlspecialchars($profile_src); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="object-cover w-full h-full" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
            <div id="userAvatarFallback" class="text-white font-semibold text-sm" style="display:none; align-items:center; justify-content:center; width:100%; height:100%;">
                <?php echo strtoupper(substr($user_name,0,1)); ?>
            </div>
        </div>

        <span class="hidden md:block text-sm font-medium text-gray-700 max-w-[150px] truncate"><?php echo htmlspecialchars($user_name); ?></span>
        <i class="fas fa-chevron-down text-xs text-gray-400 hidden md:block"></i>
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95 transform -translate-y-2"
         x-transition:enter-end="opacity-100 scale-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl ring-1 ring-black ring-opacity-5 overflow-hidden z-50"
         style="display:none;">
        <div class="px-4 py-3 bg-gradient-to-r from-blue-50 to-purple-50 border-b border-gray-200">
            <p class="text-sm font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($user_name); ?></p>
            <p class="text-xs text-gray-600 truncate"><?php echo htmlspecialchars($user_email); ?></p>
        </div>

        <div class="py-2">
            <a href="#profile" @click="open = false; if(typeof window !== 'undefined' && window.document){ try{ document.body.__x && (document.body.__x.$data.currentSection = 'profile'); }catch(e){} }" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                <i class="fas fa-user-circle w-5 text-blue-600 mr-3"></i>
                <span>Profil</span>
            </a>
            <a href="<?php echo htmlspecialchars($home_url); ?>" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                <i class="fas fa-home w-5 text-blue-600 mr-3"></i>
                <span>Ana Sayfa</span>
            </a>
        </div>

        <div class="border-t border-gray-200">
            <a href="<?php echo htmlspecialchars($logout_url); ?>" class="flex items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors font-medium">
                <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                <span>Çıkış Yap</span>
            </a>
        </div>
    </div>

    <script>
    // Fallback toggling for pages without Alpine.js
    (function(){
        if (typeof Alpine !== 'undefined') return; // Alpine present - Alpine handles toggle
        // Find the root of this fragment
        var root = (function(el){ return el && el.parentElement ? el.parentElement : document.body; })(document.currentScript ? document.currentScript.parentElement : null);
        // If multiple fragments exist, the below will attach to all
        var roots = document.querySelectorAll('[x-cloak]');
        roots.forEach(function(r){
            var btn = r.querySelector('button[aria-haspopup]');
            var menu = r.querySelector('[x-show]');
            if (!btn || !menu) return;
            btn.addEventListener('click', function(ev){ ev.stopPropagation(); var sh = getComputedStyle(menu).display !== 'none'; if(sh){ menu.style.display='none'; btn.setAttribute('aria-expanded','false'); } else { menu.style.display='block'; btn.setAttribute('aria-expanded','true'); } });
            document.addEventListener('click', function(evt){ if (!r.contains(evt.target)){ menu.style.display='none'; btn.setAttribute('aria-expanded','false'); } });
            document.addEventListener('keydown', function(evt){ if (evt.key === 'Escape'){ menu.style.display='none'; btn.setAttribute('aria-expanded','false'); } });
        });
    })();
    </script>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    try {
        var img = document.getElementById('userAvatarTop');
        if (!img) return;
        var src = img.getAttribute('src') || img.src || '';
        var isPlaceholder = src.indexOf('default-avatar') !== -1 || src.indexOf('placeholder') !== -1 || src.trim() === '';
        if (isPlaceholder && typeof window.getCanonicalProfileImage === 'function') {
            img.src = window.getCanonicalProfileImage();
        }
    } catch(e) { /* ignore */ }
});
</script>
