const TOKEN_KEYS = ['accessToken', 'access_token', 'token', 'jwt', 'refreshToken', 'refresh_token'];

function saveTokens(data) {
  TOKEN_KEYS.forEach((key) => {
    if (data?.[key]) {
      localStorage.setItem(key, data[key]);
    }
  });

  if (data?.user) {
    localStorage.setItem('user', JSON.stringify(data.user));
  }
}

function authHeaderMap(extraHeaders = {}) {
  const token =
    localStorage.getItem('accessToken') ||
    localStorage.getItem('access_token') ||
    localStorage.getItem('token') ||
    localStorage.getItem('jwt');
  const refreshToken = localStorage.getItem('refreshToken') || localStorage.getItem('refresh_token');
  const headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...extraHeaders,
  };

  if (token) {
    headers.Authorization = `Bearer ${token}`;
    headers['X-Access-Token'] = token;
  }

  if (refreshToken) {
    headers['X-Refresh-Token'] = refreshToken;
  }

  return headers;
}

window.authFetch = function authFetch(url, options = {}) {
  return fetch(url, {
    ...options,
    headers: authHeaderMap(options.headers),
  });
};

document.querySelectorAll('[data-auth-form]').forEach((form) => {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const submitButton = form.querySelector('button[type="submit"]');
    const message = form.querySelector('[data-auth-message]');
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());

    if (message) {
      message.textContent = '';
      message.classList.remove('success');
    }

    if (submitButton) {
      submitButton.disabled = true;
    }

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'fetch',
        },
        body: JSON.stringify(payload),
      });
      const result = await response.json();

      if (!response.ok || !result.ok) {
        throw new Error(result.error || 'Authentication failed.');
      }

      saveTokens(result.data || {});

      if (message) {
        message.textContent = 'Success. Redirecting...';
        message.classList.add('success');
      }

      window.location.href = result.redirect || '../profile/profile.html';
    } catch (error) {
      if (message) {
        message.textContent = error.message;
      }
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
      }
    }
  });
});
