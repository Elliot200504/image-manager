/**
 * Shared frontend helpers.
 *
 * Everything that changes state goes through api(), so the CSRF token is
 * attached in exactly one place and cannot be forgotten at a call site.
 */
(function () {
  'use strict';

  /**
   * POST a JSON action to api.php.
   *
   * Resolves with the parsed body on success and rejects with an Error whose
   * message is safe to show the user. Callers never see a raw response.
   */
  async function api(action, payload) {
    let response;

    try {
      response = await fetch('api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': window.CSRF_TOKEN || '',
        },
        // Same-origin only; the token would otherwise be sent to a redirect target.
        credentials: 'same-origin',
        redirect: 'error',
        body: JSON.stringify(Object.assign({ action: action }, payload || {})),
      });
    } catch (e) {
      throw new Error('Could not reach the server. Check your connection and try again.');
    }

    let body = null;
    try {
      body = await response.json();
    } catch (e) {
      // A non-JSON body means something upstream failed (a PHP fatal, a proxy
      // error page). Surface the status rather than a JSON parse error.
      throw new Error('The server returned an unexpected response (' + response.status + ').');
    }

    if (!response.ok || !body || body.ok !== true) {
      throw new Error((body && body.error) || 'That action failed.');
    }

    return body;
  }

  /** Transient bottom-of-screen message. Text only — never HTML. */
  function toast(message, type) {
    const el = document.createElement('div');
    el.className = 'toast toast-' + (type || 'info');
    el.textContent = message;
    document.body.appendChild(el);

    // Next frame, so the entry transition actually runs.
    requestAnimationFrame(() => el.classList.add('visible'));

    setTimeout(() => {
      el.classList.remove('visible');
      el.addEventListener('transitionend', () => el.remove(), { once: true });
    }, 3200);
  }

  window.App = { api, toast };
})();
