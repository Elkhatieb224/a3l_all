{{-- جلب «دولة - محافظة - منطقة» من Nominatim للبطاقات وصفحة الإعلان (مرة واحدة لكل الصفحة) --}}
@once
@push('scripts')
<script>
(function () {
    'use strict';
    window.A3lNominatim = window.A3lNominatim || {};
    var cache = window.A3lNominatim.cache = window.A3lNominatim.cache || {};
    var pending = window.A3lNominatim.pending = window.A3lNominatim.pending || {};

    function cacheKey(lat, lng) {
        return lat.toFixed(4) + '|' + lng.toFixed(4) + '|' + (document.documentElement.lang || 'ar');
    }

    function formatAddressLine(addr) {
        if (!addr || typeof addr !== 'object') return '';
        var order = ['country', 'state', 'region', 'province', 'county', 'city', 'town', 'village', 'suburb', 'neighbourhood'];
        var parts = [];
        for (var i = 0; i < order.length; i++) {
            var v = addr[order[i]];
            if (!v || typeof v !== 'string') continue;
            v = v.trim();
            if (!v) continue;
            var dup = false;
            for (var j = 0; j < parts.length; j++) {
                if (parts[j].toLowerCase() === v.toLowerCase()) { dup = true; break; }
            }
            if (dup) continue;
            parts.push(v);
            if (parts.length >= 3) break;
        }
        return parts.join(' - ');
    }

    window.A3lNominatim.reverseLine = function (lat, lng, done) {
        var la = parseFloat(lat);
        var ln = parseFloat(lng);
        if (!isFinite(la) || !isFinite(ln)) { done(''); return; }
        var k = cacheKey(la, ln);
        if (Object.prototype.hasOwnProperty.call(cache, k)) { done(cache[k]); return; }
        if (pending[k]) {
            pending[k].push(done);
            return;
        }
        pending[k] = [done];
        var lang = document.documentElement.lang || 'ar';
        fetch('https://nominatim.openstreetmap.org/reverse?lat=' + la + '&lon=' + ln + '&format=json&accept-language=' + encodeURIComponent(lang), {
            headers: { 'Accept': 'application/json' },
            referrerPolicy: 'no-referrer-when-downgrade',
        })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(); })
            .then(function (data) {
                var line = formatAddressLine(data && data.address);
                if (!line && data && data.display_name) {
                    line = data.display_name.split(',').map(function (s) { return s.trim(); }).filter(Boolean).slice(0, 3).join(' - ');
                }
                return line || '';
            })
            .catch(function () { return ''; })
            .then(function (line) {
                if (line) cache[k] = line;
                var cbs = pending[k];
                delete pending[k];
                cbs.forEach(function (fn) { fn(line); });
            });
    };

    function fillCard(el) {
        var lat = parseFloat(el.getAttribute('data-lat'));
        var lng = parseFloat(el.getAttribute('data-lng'));
        var textEl = el.querySelector('.js-ad-card-loc-text');
        var fb = el.getAttribute('data-fallback') || '';
        if (!textEl || !isFinite(lat) || !isFinite(lng)) return;
        window.A3lNominatim.reverseLine(lat, lng, function (line) {
            textEl.textContent = line || fb;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-ad-card-loc').forEach(fillCard);
        var showLine = document.getElementById('ad-show-reverse-geocode-line');
        if (showLine) {
            var slat = parseFloat(showLine.getAttribute('data-lat'));
            var slng = parseFloat(showLine.getAttribute('data-lng'));
            var fb = showLine.getAttribute('data-fallback') || '';
            var resolving = showLine.getAttribute('data-resolving') || '';
            showLine.textContent = resolving;
            if (isFinite(slat) && isFinite(slng)) {
                window.A3lNominatim.reverseLine(slat, slng, function (line) {
                    showLine.textContent = line || fb;
                });
            }
        }
    });
})();
</script>
@endpush
@endonce
