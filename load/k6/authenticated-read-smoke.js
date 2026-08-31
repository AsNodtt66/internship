import http from 'k6/http';
import { check, sleep } from 'k6';

const baseUrl = (__ENV.BASE_URL || '').replace(/\/$/, '');
const cookieName = __ENV.SESSION_COOKIE_NAME || '';
const cookieValue = __ENV.SESSION_COOKIE_VALUE || '';
const targetPaths = (__ENV.AUTH_PATHS || '/admin').split(',').map((p) => p.trim()).filter(Boolean);

if (!baseUrl || !cookieName || !cookieValue) {
  throw new Error('BASE_URL, SESSION_COOKIE_NAME, and SESSION_COOKIE_VALUE are required. Use a dedicated staging test account/session.');
}

export const options = {
  scenarios: {
    authenticated_reads: {
      executor: 'constant-vus',
      vus: Number(__ENV.VUS || 3),
      duration: __ENV.DURATION || '30s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    checks: ['rate>0.99'],
    http_req_duration: [`p(95)<${Number(__ENV.P95_MS || 1000)}`],
  },
};

export default function () {
  for (const path of targetPaths) {
    const res = http.get(`${baseUrl}${path}`, {
      cookies: { [cookieName]: cookieValue },
      redirects: 0,
      tags: { endpoint: path },
    });
    check(res, {
      [`${path} authenticated status`]: (r) => r.status >= 200 && r.status < 400,
      [`${path} not redirected to login`]: (r) => !String(r.headers.Location || '').includes('/login'),
    });
  }
  sleep(1);
}
