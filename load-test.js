import http from 'k6/http';
import { check } from 'k6';
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.2/index.js'; // برای گزارش‌دهی بهتر

export let options = {
  vus: 20,          // تعداد کاربران مجازی
  duration: '1m',   // مدت زمان تست
};

export default function () {
  // مسیرهای مختلف برای تست‌های مختلف
  const analyticsUrl = 'http://localhost:8080/api/analytics/track';
  const protectedUrl = 'http://localhost:8080/api/user'; // مسیر محافظت‌شده با JWT (مثال)

  // توکن‌های جایگزین (باید با توکن‌های واقعی جایگزین شوند)
  const VALID_JWT_TOKEN_HERE = 'YOUR_ACTUAL_VALID_JWT_TOKEN'; // توکن JWT معتبر واقعی
  const INVALID_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c_INVALID'; // توکن نامعتبر
  const TAMPERED_JWT_TOKEN_HERE = 'YOUR_ACTUAL_VALID_JWT_TOKEN_tampered'; // توکن دستکاری شده (یک کاراکتر آخرش تغییر داده شده)

  // تست های مختلف امنیتی
  const tests = [
    // 1. Valid Request - باید 200 باشه (روی مسیر Analytics)
    {
      name: "Valid Analytics Request",
      url: analyticsUrl,
      headers: {
        'Content-Type': 'application/json',
      },
      payload: {
        lick_count: 39,
        guest_uuid: 'dc3eabf4-4e48-42bd-a86f-093d4bb5ee90',
        page_views: 4,
        screen_time: 1426,
        session_time: 1423,
      },
      expectedStatus: 200
    },

    // 2. Invalid JWT - باید 401 باشه (روی مسیر محافظت‌شده)
    {
      name: "Invalid JWT",
      url: protectedUrl,
      headers: {
        'Authorization': `Bearer ${INVALID_TOKEN}`,
        'Content-Type': 'application/json',
      },
      payload: {}, // Payload برای GET معمولا خالی است
      expectedStatus: 401
    },

    // 3. Missing Authorization - باید 401 باشه (روی مسیر محافظت‌شده)
    {
      name: "Missing Auth",
      url: protectedUrl,
      headers: {
        'Content-Type': 'application/json',
      },
      payload: {}, // Payload برای GET معمولا خالی است
      expectedStatus: 401
    },

    // 4. Invalid UUID Format - باید 400 باشه (روی مسیر Analytics - نیاز به اعتبارسنجی در کنترلر)
    {
      name: "Invalid UUID",
      url: analyticsUrl,
      headers: {
        'Content-Type': 'application/json',
      },
      payload: {
        lick_count: 39,
        guest_uuid: 'invalid-uuid-format',
        page_views: 4,
        screen_time: 1426,
        session_time: 1423,
      },
      expectedStatus: 400
    },

    // 5. Negative Values - باید 400 باشه (روی مسیر Analytics - نیاز به اعتبارسنجی در کنترلر)
    {
      name: "Negative Values",
      url: analyticsUrl,
      headers: {
        'Content-Type': 'application/json',
      },
      payload: {
        lick_count: -1,
        guest_uuid: 'dc3eabf4-4e48-42bd-a86f-093d4bb5ee90',
        page_views: -5,
        screen_time: -100,
        session_time: -50,
      },
      expectedStatus: 400
    },

    // 6. SQL Injection Attempt - باید 400 یا 403 باشه (روی مسیر Analytics - نیاز به اعتبارسنجی در کنترلر)
    ...[
        "' OR '1'='1",
        "' OR 1=1 --",
        "'; EXEC xp_cmdshell('cmd.exe') --",
        "UNION SELECT username, password FROM users"
    ].map(sqlPayload => ({
        name: `SQL Injection (${sqlPayload.substring(0, 20)}...)`,
        url: analyticsUrl,
        headers: {
            'Content-Type': 'application/json',
        },
        payload: {
            lick_count: sqlPayload, // فرض می‌کنیم این فیلد می‌تواند هدف SQL Injection باشد
            guest_uuid: 'dc3eabf4-4e48-42bd-a86f-093d4bb5ee90',
            page_views: 4,
            screen_time: 1426,
            session_time: 1423,
        },
        expectedStatus: 400 // یا 403 بسته به پیاده‌سازی بک‌اند
    })),

    // 7. Too Large Payload - باید 413 باشه (روی مسیر Analytics - هندل شده توسط Nginx)
    {
      name: "Large Payload",
      url: analyticsUrl,
      headers: {
        'Content-Type': 'application/json',
      },
      payload: {
        lick_count: 39,
        guest_uuid: 'dc3eabf4-4e48-42bd-a86f-093d4bb5ee90',
        page_views: 999999999,
        screen_time: 999999999,
        session_time: 999999999,
        large_field: "x".repeat(1024 * 1024 * 11) // 11MB string - بیشتر از 10MB Nginx
      },
      expectedStatus: 413
    },

    // 8. XSS Attempt - باید 400 باشه (روی مسیر Analytics - نیاز به اعتبارسنجی در کنترلر)
    {
      name: "XSS Attempt",
      url: analyticsUrl,
      headers: {
        'Content-Type': 'application/json',
      },
      payload: {
        lick_count: 39,
        guest_uuid: '<script>alert(1)</script>', // فرض می‌کنیم guest_uuid هم می‌تواند هدف XSS باشد
        page_views: 4,
        screen_time: 1426,
        session_time: 1423,
      },
      expectedStatus: 400 // انتظار می‌رود سیستم این ورودی را نامعتبر تشخیص دهد
    },

    // 9. Missing Content-Type - باید 400 باشه (روی مسیر Analytics - نیاز به اعتبارسنجی در کنترلر)
    {
      name: "Missing Content-Type",
      url: analyticsUrl,
      headers: {
        'Authorization': `Bearer ${VALID_JWT_TOKEN_HERE}`
      },
      payload: { // یک payload معتبر برای این تست
        lick_count: 39,
        guest_uuid: 'dc3eabf4-4e48-42bd-a86f-093d4bb5ee90',
        page_views: 4,
        screen_time: 1426,
        session_time: 1423,
      },
      expectedStatus: 400 // انتظار می‌رود سرور بدون Content-Type مناسب، درخواست را رد کند
    },

    // 10. CSRF Attempt - (روی مسیر Analytics - لاراول CSRF را برای APIها بررسی نمی‌کند مگر صریحاً فعال شود)
    {
      name: "CSRF Attempt",
      url: analyticsUrl,
      headers: {
        'Content-Type': 'application/json',
        // 'X-CSRF-Token': 'MISSING_OR_INVALID_TOKEN' // اگر سرور انتظار توکن CSRF را دارد
      },
      payload: {
        lick_count: 39,
        guest_uuid: 'dc3eabf4-4e48-42bd-a86f-093d4bb5ee90',
        page_views: 4,
        screen_time: 1426,
        session_time: 1423,
      },
      expectedStatus: 200 // برای APIها، لاراول به طور پیش‌فرض CSRF را بررسی نمی‌کند.
    },

    // 11. Path Traversal Attempt - باید 400 باشه (روی مسیر Analytics - نیاز به اعتبارسنجی در کنترلر)
    {
      name: "Path Traversal",
      url: analyticsUrl,
      headers: {
        'Content-Type': 'application/json',
      },
      payload: {
        lick_count: 39,
        guest_uuid: '../../../../etc/passwd', // تلاش برای تزریق Path Traversal
        page_views: 4,
        screen_time: 1426,
        session_time: 1423,
      },
      expectedStatus: 400 // انتظار می‌رود اگر ورودی اعتبارسنجی شود، درخواست نامعتبر باشد
    },

    // 12. Rate Limiting Expectation - باید 429 باشه (روی مسیر Analytics - اگر throttle فعال باشد)
    // این تست به تنهایی باعث فعال شدن Rate Limiting نمی‌شود،
    // اما انتظار کد 429 (Too Many Requests) را در صورت فعال بودن Rate Limiting تعریف می‌کند.
    // برای تست واقعی Rate Limiting، نیاز به سناریوی جداگانه با حجم بالای درخواست‌ها است.
    {
      name: "Rate Limiting Expectation",
      url: analyticsUrl,
      headers: {
        'Content-Type': 'application/json',
      },
      payload: {
        lick_count: 39,
        guest_uuid: 'dc3eabf4-4e48-42bd-a86f-093d4bb5ee90',
        page_views: 4,
        screen_time: 1426,
        session_time: 1423,
      },
      expectedStatus: 429 // انتظار 429 Too Many Requests اگر Rate Limit فعال باشد
    },

    // 13. JWT Tampering Attempt - باید 401 باشه (روی مسیر محافظت‌شده)
    {
      name: "JWT Tampering",
      url: protectedUrl,
      headers: {
        'Authorization': `Bearer ${TAMPERED_JWT_TOKEN_HERE}`,
        'Content-Type': 'application/json',
      },
      payload: {}, // Payload برای GET معمولا خالی است
      expectedStatus: 401 // انتظار 401 Unauthorized برای JWT دستکاری شده
    }
  ];

  // انتخاب تصادفی یک تست
  const randomTest = tests[Math.floor(Math.random() * tests.length)];

  let res;
  // برای درخواست‌های GET، payload را ارسال نکن
  if (randomTest.url === protectedUrl && randomTest.payload && Object.keys(randomTest.payload).length === 0) {
    res = http.get(randomTest.url, {
      headers: randomTest.headers
    });
  } else {
    res = http.post(randomTest.url, JSON.stringify(randomTest.payload), {
      headers: randomTest.headers
    });
  }

  // چک کردن status code
  check(res, {
    [`${randomTest.name} - Status is ${randomTest.expectedStatus}`]: (r) =>
      r.status === randomTest.expectedStatus,
  });

  console.log(`${randomTest.name}: Expected ${randomTest.expectedStatus}, Got ${res.status}`);

  // اگه response body داره، چاپش کن
  if (res.body && res.status !== 200) {
    console.log(`Error Response: ${res.body.substring(0, 100)}`);
  }
}

// بهبود گزارش‌دهی (پیشنهاد شما)
export function handleSummary(data) {
  console.log('Generating k6 summary...');
  return {
    'summary.json': JSON.stringify(data, null, 2), // فرمت JSON خواناتر
    'stdout': textSummary(data, { indent: ' ', enableColors: true }),
  };
}
