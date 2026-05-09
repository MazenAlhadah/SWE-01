/* UC-06 — Capacity alert AJAX polling. This is the ONLY file that touches AJAX. */

(function () {
    var POLL_INTERVAL = 30000; /* 30 seconds */
    var endpoint      = 'index.php?page=alerts&action=capacity';

    function checkCapacity() {
        fetch(endpoint)
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (!json.success) return;

                var banner = document.getElementById('capacity-alert-banner');
                var detail = document.getElementById('capacity-alert-detail');

                if (json.data.breach) {
                    /* Inject Bootstrap alert-warning banner without reload */
                    var pct = Math.round(json.data.ratio * 100);
                    detail.textContent = ' Current: ' + pct + '% (' +
                        json.data.used_m3.toFixed(2) + ' / ' +
                        json.data.total_m3.toFixed(2) + ' m\u00B3)';
                    banner.classList.remove('d-none');
                } else {
                    banner.classList.add('d-none');
                    detail.textContent = '';
                }
            })
            .catch(function () { /* silently ignore network errors */ });
    }

    /* Poll immediately on page load, then every 30 s */
    checkCapacity();
    setInterval(checkCapacity, POLL_INTERVAL);
}());
