// resources/js/charts.js

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

// Function to initialize the chart
export function initializeMonthlySalesChart() {
    const monthlySalesCtx = document.getElementById('monthlySalesChart');
    if (monthlySalesCtx && !monthlySalesChartInstance) {
        monthlySalesChartInstance = new Chart(monthlySalesCtx.getContext('2d'), monthlySalesConfig);
    }
}

// Function to update chart size (e.g., after sidebar toggle)
export function updateChartOnResize() {
    if (monthlySalesChartInstance) {
        monthlySalesChartInstance.resize();
    }
}
