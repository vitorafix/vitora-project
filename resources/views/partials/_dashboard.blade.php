{{-- resources/views/partials/_dashboard.blade.php --}}

<section id="dashboard-content" class="section-content active">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Cards for Dashboard -->
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between card-hover-effect">
            <div>
                <p class="text-sm text-gray-500">محصولات موجود</p>
                <p class="text-2xl font-bold text-brown-900">2,156</p>
            </div>
            <i class="fas fa-boxes text-4xl text-blue-600"></i>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between card-hover-effect">
            <div>
                <p class="text-sm text-gray-500">مشتریان جدید</p>
                <p class="text-2xl font-bold text-brown-900">8,642</p>
            </div>
            <i class="fas fa-user-plus text-4xl text-green-600"></i>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between card-hover-effect">
            <div>
                <p class="text-sm text-gray-500">فروش امروز (میلیون تومان)</p>
                <p class="text-2xl font-bold text-brown-900">45,320</p>
            </div>
            <i class="fas fa-money-bill-wave text-4xl text-amber-600"></i>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between card-hover-effect">
            <div>
                <p class="text-sm text-gray-500">سفارشات جدید</p>
                <p class="text-2xl font-bold text-brown-900">1,284</p>
            </div>
            <i class="fas fa-clipboard-list text-4xl text-red-600"></i>
        </div>
    </div>

    <!-- Charts and Activity Log -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white p-6 rounded-lg shadow-md card-hover-effect">
            <h3 class="text-lg font-semibold mb-4 text-brown-900">نمودار فروش ماهانه</h3>
            <div class="chart-container">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </div>
        <div class="bg-white p-6 rounded-lg shadow-md card-hover-effect">
            <h3 class="text-lg font-semibold mb-4 text-brown-900">فعالیت‌های اخیر ادمین</h3>
            <div id="admin-activity-log" class="space-y-3 h-64 overflow-y-auto custom-scrollbar">
                <!-- Log entries will be added here by JS -->
            </div>
        </div>
    </div>
</section>
