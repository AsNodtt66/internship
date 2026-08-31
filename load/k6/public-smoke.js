import http from 'k6/http';
import { check, sleep } from 'k6';

const baseUrl = (__ENV.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const p95 = Number(__ENV.P95_MS || 1000);
const p99 = Number(__ENV.P99_MS || 2000);

export const options = {
  scenarios: {
    smoke: {
      executor: 'shared-iterations',
      vus: Number(__ENV.VUS || 1),
      iterations: Number(__ENV.ITERATIONS || 8),
      maxDuration: '2m',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    checks: ['rate>0.99'],
    http_req_duration: [`p(95)<${p95}`, `p(99)<${p99}`],
  },
};

const pages = ['/', '/up', '/health/ready', '/admin/login', '/peserta/login'];

export default function () {
  for (const path of pages) {
    const res = http.get(`${baseUrl}${path}`, { tags: { endpoint: path } });
    check(res, {
      [`${path} returns non-error`]: (r) => r.status >= 200 && r.status < 500,
      [`${path} has request id`]: (r) => Boolean(r.headers['X-Request-Id'] || r.headers['X-Request-ID']),
    });
  }
  sleep(0.5);
}
