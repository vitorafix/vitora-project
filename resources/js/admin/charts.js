// resources/js/ui/charts.js

// Import Chart.js library if it's not globally available or bundled separately
// Assuming Chart.js is loaded via a script tag in your HTML or bundled by Vite.
// If you need to import it as a module, it would look something like:
// import Chart from 'chart.js/auto';

let monthlySalesChartInstance;

// Data for the monthly sales chart
const monthlySalesData = {
    labels: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
    datasets: [{
        label: 'میزان فروش',
        data: [120, 190, 150, 220, 180, 250, 200, 280, 230, 300, 270, 350], // Sample data in millions
        backgroundColor: 'rgba(56, 161, 105, 0.6)', // green-700 with opacity
        borderColor: 'rgba(56, 161, 105, 1)', // green-700
        borderWidth: 1,
        fill: true,
        tension: 0.3
    }]
};

const monthlySalesConfig = {
    type: 'line',
    data: monthlySalesData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
                labels: {
                    font: {
                        family: 'Vazirmatn' // Set font for legend
                    }
                }
            },
            tooltip: {
                rtl: true, // Enable RTL for tooltips
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += context.parsed.y.toLocaleString('fa-IR') + ' میلیون تومان';
                        }
                        return label;
                    }
                },
                titleFont: {
                    family: 'Vazirmatn'
                },
                bodyFont: {
                    family: 'Vazirmatn'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'میزان فروش (میلیون تومان)',
                    font: {
                        family: 'Vazirmatn'
                    }
                },
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('fa-IR');
                    },
                    font: {
                        family: 'Vazirmatn'
                    }
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'ماه',
                    font: {
                        family: 'Vazirmatn'
                    }
                },
                ticks: {
                    font: {
                        family: 'Vazirmatn'
                    }
                }
            }
        },
        layout: {
            padding: {
                left: 10,
                right: 10,
                top: 10,
                bottom: 10
            }
        }
    }
};

/**
 * Function to initialize the monthly sales chart.
 * این تابع نمودار فروش ماهانه را مقداردهی اولیه می‌کند.
 */
export function initializeMonthlySalesChart() {
    const monthlySalesCtx = document.getElementById('monthlySalesChart');
    if (monthlySalesCtx && typeof Chart !== 'undefined' && !monthlySalesChartInstance) { // Ensure Chart is defined
        monthlySalesChartInstance = new Chart(monthlySalesCtx.getContext('2d'), monthlySalesConfig);
        console.log('Monthly sales chart initialized.');
    } else if (!monthlySalesCtx) {
        console.warn('Canvas element with ID "monthlySalesChart" not found.');
    } else if (monthlySalesChartInstance) {
        console.log('Monthly sales chart already initialized.');
    } else if (typeof Chart === 'undefined') {
        console.error('Chart.js library is not loaded. Cannot initialize chart.');
    }
}

/**
 * Function to update chart size (e.g., after sidebar toggle).
 * این تابع اندازه نمودار را به‌روزرسانی می‌کند (مثلاً پس از تغییر اندازه سایدبار).
 */
export function updateChartOnResize() {
    if (monthlySalesChartInstance) {
        monthlySalesChartInstance.resize();
        console.log('Monthly sales chart resized.');
    } else {
        console.warn('Monthly sales chart instance not found for resize operation.');
    }
}

/**
 * Main initialization function for the charts module.
 * این تابع اصلی راه‌اندازی ماژول نمودارها است.
 * app.js این تابع را به صورت داینامیک فراخوانی می‌کند.
 */
export function initCharts() {
    console.log('Charts module initializing...');
    // Initialize charts that are always present on the dashboard/admin reports page
    initializeMonthlySalesChart();
    // Add other chart initializations here if you have more charts in this module
}
