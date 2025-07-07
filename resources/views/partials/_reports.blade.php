{{-- resources/views/partials/_reports.blade.php --}}

<section id="reports-content" class="section-content">
    <h2 class="text-2xl font-semibold mb-4 text-brown-900">گزارشات</h2>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <p class="text-gray-700">این بخش برای نمایش گزارشات مختلف است.</p>
        <div class="mt-4 flex space-x-2 space-x-reverse">
            <button id="export-excel" class="btn-primary">
                خروجی اکسل
            </button>
            <button id="export-pdf" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 focus:outline-none">
                خروجی PDF
            </button>
            <button id="toggle-report-actions" aria-expanded="false" aria-controls="report-actions-container"
                    class="btn-secondary relative">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div id="report-actions-container" class="absolute bg-white shadow-lg rounded-lg py-2 right-40 mt-12 w-48 hidden">
                <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 hover:text-green-700">گزارش فروش</a>
                <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 hover:text-green-700">گزارش مشتریان</a>
                <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 hover:text-green-700">گزارش محصولات</a>
            </div>
        </div>
    </div>
</section>
