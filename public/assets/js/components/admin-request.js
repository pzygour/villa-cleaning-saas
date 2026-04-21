(function () {
    function apiUrl(path) {
        if (typeof window.apiUrl === 'function') {
            return window.apiUrl(path);
        }

        return path;
    }

    async function readJsonSafe(response) {
        try {
            return await response.json();
        } catch {
            return {};
        }
    }

    function extractErrorMessage(payload, fallback = 'request_failed') {
        if (!payload || typeof payload !== 'object') {
            return fallback;
        }

        if (typeof payload.message === 'string' && payload.message.trim() !== '') {
            return payload.message;
        }

        if (typeof payload.error === 'string' && payload.error.trim() !== '') {
            return payload.error;
        }

        return fallback;
    }

    function unwrapPayload(payload) {
        if (!payload || typeof payload !== 'object') {
            return payload;
        }

        if (Object.prototype.hasOwnProperty.call(payload, 'success')) {
            if (payload.success === false) {
                return {
                    ok: false,
                    data: null,
                    error: extractErrorMessage(payload),
                    payload,
                };
            }

            return {
                ok: true,
                data: Object.prototype.hasOwnProperty.call(payload, 'data') ? payload.data : payload,
                error: null,
                payload,
            };
        }

        return {
            ok: true,
            data: Object.prototype.hasOwnProperty.call(payload, 'data') ? payload.data : payload,
            error: null,
            payload,
        };
    }

    async function request(path, options = {}) {
        const response = await fetch(apiUrl(path), options);
        const payload = await readJsonSafe(response);

        if (!response.ok) {
            return {
                ok: false,
                data: null,
                error: extractErrorMessage(payload, `HTTP ${response.status}`),
                status: response.status,
                payload,
            };
        }

        const unwrapped = unwrapPayload(payload);

        return {
            ...unwrapped,
            status: response.status,
        };
    }

    function setBanner(target, type, message) {
        if (!target) {
            return;
        }

        target.style.display = message ? 'block' : 'none';
        target.className = `banner ${type}`;
        target.textContent = message || '';
    }

    async function withButtonLoading(button, loadingText, task) {
        if (!button) {
            return task();
        }

        const originalText = button.dataset.originalText || button.textContent;
        button.dataset.originalText = originalText;
        button.disabled = true;
        button.textContent = loadingText;

        try {
            return await task();
        } finally {
            button.disabled = false;
            button.textContent = originalText;
        }
    }

    window.AdminRequest = {
        apiUrl,
        request,
        extractErrorMessage,
        setBanner,
        withButtonLoading,
    };
})();
