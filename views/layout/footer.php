        </main>
    </div>
</div>

<!-- Global Javascript Handler -->
<script>
    // Intercept native fetch to automatically inject CSRF token on POST requests
    const originalFetch = window.fetch;
    window.fetch = function(url, options) {
        options = options || {};
        const method = (options.method || 'GET').toUpperCase();
        if (method === 'POST') {
            options.headers = options.headers || {};
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (token) {
                // If body is FormData, append CSRF
                if (options.body instanceof FormData) {
                    if (!options.body.has('csrf_token')) {
                        options.body.append('csrf_token', token);
                    }
                } else if (typeof options.body === 'string') {
                    // If body is JSON string, parse and add csrf_token
                    try {
                        const parsed = JSON.parse(options.body);
                        if (typeof parsed === 'object' && parsed !== null && !parsed.hasOwnProperty('csrf_token')) {
                            parsed.csrf_token = token;
                            options.body = JSON.stringify(parsed);
                        }
                    } catch(e) {}
                }
                // Also append as header for standard practice
                options.headers['X-CSRF-TOKEN'] = token;
            }
        }
        return originalFetch(url, options);
    };

    // Mobile Menu Toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
            const icon = mobileMenuBtn.querySelector('i');
            if (mobileMenu.classList.contains('hidden')) {
                icon.className = 'fa-solid fa-bars text-xl';
            } else {
                icon.className = 'fa-solid fa-xmark text-xl';
            }
        });
    }
</script>
</body>
</html>
