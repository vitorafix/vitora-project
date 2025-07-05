    <?php
    // File: app/Services/Managers/CartMetricsManager.php
    namespace App\Services\Managers;

    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Event; // For dispatching custom metric events

    class CartMetricsManager
    {
        private bool $enableMetrics;

        public function __construct()
        {
            $this->enableMetrics = config('cart.enable_metrics', true);
        }

        /**
         * Records a performance metric.
         * یک معیار عملکرد را ثبت می‌کند.
         *
         * @param string $metricName The name of the metric (e.g., 'addOrUpdateCartItem_duration').
         * @param float $value The value of the metric (e.g., duration in seconds).
         * @param array $tags Additional tags or context for the metric.
         */
        public function recordMetric(string $metricName, float $value, array $tags = []): void
        {
            if (!$this->enableMetrics) {
                return;
            }

            // In a real application, you would send this to a dedicated monitoring system
            // like Prometheus, Grafana, Datadog, New Relic, or a custom logging channel.
            // در یک برنامه واقعی، این معیار را به یک سیستم مانیتورینگ اختصاصی ارسال می‌کنید.

            Log::info('Cart Metric', [
                'metric_name' => $metricName,
                'value' => $value,
                'tags' => $tags,
                'timestamp' => now()->toIso8601String(),
            ]);

            // Optionally dispatch an event for other parts of the application to react to metrics
            // به صورت اختیاری یک رویداد را برای واکنش سایر بخش‌های برنامه به معیارها ارسال کنید
            // Event::dispatch(new \App\Events\CartMetricRecorded(['name' => $metricName, 'value' => $value, 'tags' => $tags]));
        }

        /**
         * Performs a health check for the metrics manager.
         * یک بررسی سلامت برای مدیر معیارها انجام می‌دهد.
         *
         * @return array
         */
        public function healthCheck(): array
        {
            // Simple check to ensure metrics logging is enabled
            // یک بررسی ساده برای اطمینان از فعال بودن لاگ‌گذاری معیارها
            if ($this->enableMetrics) {
                return ['status' => 'ok', 'message' => 'Cart metrics logging is enabled.'];
            }
            return ['status' => 'warning', 'message' => 'Cart metrics logging is disabled.'];
        }
    }
    