(function () {
    function apiUrl(path) {
        if (typeof window.apiUrl === 'function') {
            return window.apiUrl(path);
        }
        return path;
    }

    async function readJson(response) {
        try {
            return await response.json();
        } catch {
            return {};
        }
    }

    function unwrap(payload) {
        if (payload && typeof payload === 'object' && Object.prototype.hasOwnProperty.call(payload, 'success')) {
            if (payload.success === false) {
                const err = new Error(payload.message || payload.error || 'request_failed');
                err.payload = payload;
                throw err;
            }
            if (Object.prototype.hasOwnProperty.call(payload, 'data')) {
                return payload.data;
            }
        }

        if (payload && typeof payload === 'object' && Object.prototype.hasOwnProperty.call(payload, 'data')) {
            return payload.data;
        }

        return payload;
    }

    async function requestJson(path, options = {}) {
        const response = await fetch(apiUrl(path), options);
        const payload = await readJson(response);

        if (!response.ok) {
            const err = new Error(payload.message || payload.error || `HTTP ${response.status}`);
            err.payload = payload;
            throw err;
        }

        return unwrap(payload);
    }

    window.LaundryUi = {
        requestJson,
        apiUrl,
        unwrap,
    };
})();
