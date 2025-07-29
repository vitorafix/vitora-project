// resources/js/cart/export.js

// نیازی به import showMessage نیست چون به صورت global در window تعریف شده است.
// کتابخانه‌های XLSX, jspdf و html2canvas باید به صورت گلوبال در دسترس باشند
// یا توسط Vite به صورت جداگانه Bundle و لود شوند.
// فرض بر این است که این کتابخانه‌ها از طریق <script> تگ در HTML یا تنظیمات Vite لود می‌شوند.

export function setupExportButtons() {
    // دکمه Export Excel
    const exportExcelButton = document.getElementById('export-excel');

    if (exportExcelButton) {
        exportExcelButton.addEventListener('click', () => {
            // اطمینان حاصل کنید که XLSX به صورت گلوبال در دسترس است
            if (typeof XLSX === 'undefined') {
                if (typeof window.showMessage === 'function') {
                    window.showMessage('خطا: کتابخانه XLSX برای صادرات اکسل یافت نشد.', 'error');
                } else {
                    console.error('Error: XLSX library not found for Excel export.');
                }
                return;
            }

            const data = [
                ['نام محصول', 'قیمت', 'موجودی'],
                ['چای سیاه', 50000, 1000],
                ['چای سبز', 60000, 500],
                ['چای ارل گری', 75000, 300],
            ];

            const ws = XLSX.utils.aoa_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'گزارش محصولات');
            XLSX.writeFile(wb, 'گزارش_محصولات.xlsx');

            if (typeof window.showMessage === 'function') {
                window.showMessage('فایل اکسل با موفقیت صادر شد.', 'success');
            } else {
                console.log('فایل اکسل با موفقیت صادر شد.');
            }
        });
    }

    // دکمه Export PDF
    const exportPdfButton = document.getElementById('export-pdf');

    if (exportPdfButton) {
        exportPdfButton.addEventListener('click', async () => {
            // اطمینان حاصل کنید که jspdf و html2canvas به صورت گلوبال در دسترس هستند
            if (typeof window.jspdf === 'undefined' || typeof html2canvas === 'undefined') {
                if (typeof window.showMessage === 'function') {
                    window.showMessage('خطا: کتابخانه‌های jspdf یا html2canvas برای صادرات PDF یافت نشدند.', 'error');
                } else {
                    console.error('Error: jspdf or html2canvas libraries not found for PDF export.');
                }
                return;
            }

            const { jsPDF } = window.jspdf; // دسترسی به jsPDF از window
            const doc = new jsPDF();

            const content = document.getElementById('reports-content');

            if (!content) {
                if (typeof window.showMessage === 'function') {
                    window.showMessage('محتوای گزارش برای تولید PDF یافت نشد.', 'error');
                } else {
                    console.log('محتوای گزارش برای تولید PDF یافت نشد.');
                }
                return;
            }

            // اگر محتوا مخفی بود، موقتاً نشان داده شود
            const isHidden = content.classList.contains('hidden') || !content.classList.contains('active');

            if (isHidden) {
                content.style.display = 'block';
                content.classList.add('temp-visible-for-pdf');
            }

            try {
                const canvas = await html2canvas(content, {
                    scale: 2,
                    useCORS: true,
                    windowWidth: document.body.scrollWidth,
                    windowHeight: document.body.scrollHeight,
                });

                const imgData = canvas.toDataURL('image/png');
                const imgWidth = 210; // A4 عرض mm
                const pageHeight = 297; // A4 ارتفاع mm
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                let heightLeft = imgHeight;
                let position = 0;

                doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;

                while (heightLeft > 0) {
                    position = heightLeft - imgHeight;
                    doc.addPage();
                    doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;
                }

                doc.save('گزارش.pdf');

                if (typeof window.showMessage === 'function') {
                    window.showMessage('فایل PDF با موفقیت صادر شد.', 'success');
                } else {
                    console.log('فایل PDF با موفقیت صادر شد.');
                }
            } catch (error) {
                console.error('Error generating PDF:', error);
                if (typeof window.showMessage === 'function') {
                    window.showMessage('خطا در تولید PDF: ' + error.message, 'error');
                } else {
                    console.log('خطا در تولید PDF: ' + error.message);
                }
            } finally {
                if (isHidden) {
                    content.style.display = '';
                    content.classList.remove('temp-visible-for-pdf');
                }
            }
        });
    }
}

// The document.addEventListener('DOMContentLoaded') block is removed from here.
// app.js will dynamically import this module and call setupExportButtons().
