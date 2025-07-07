// resources/js/export.js

import { showMessage } from './app.js'; // Import showMessage from app.js

export function setupExportButtons() {
    // Export to Excel (using SheetJS)
    const exportExcelButton = document.getElementById('export-excel');
    if (exportExcelButton) {
        exportExcelButton.addEventListener('click', () => {
            const data = [
                ['نام محصول', 'قیمت', 'موجودی'],
                ['چای سیاه', 50000, 1000],
                ['چای سبز', 60000, 500],
                ['چای ارل گری', 75000, 300]
            ];
            const ws = XLSX.utils.aoa_to_sheet(data);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "گزارش محصولات");
            XLSX.writeFile(wb, "گزارش_محصولات.xlsx");
            showMessage('فایل اکسل با موفقیت صادر شد.', 'success');
        });
    }

    // Export to PDF (using jsPDF and html2canvas)
    const exportPdfButton = document.getElementById('export-pdf');
    if (exportPdfButton) {
        exportPdfButton.addEventListener('click', async () => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Select the content you want to export (e.g., the reports section)
            const content = document.getElementById('reports-content'); // Or any other section

            if (content) {
                // Temporarily show the report content if it's hidden
                const isHidden = content.classList.contains('hidden') || !content.classList.contains('active');
                if (isHidden) {
                    content.style.display = 'block'; // Make it visible for capture
                    content.classList.add('temp-visible-for-pdf'); // Mark for removal later
                }

                // Ensure Vazirmatn font is loaded for jsPDF if needed, or use a fallback
                // For jsPDF to use custom fonts, they need to be added. This is a complex step
                // and usually involves converting the font to base64 and adding it to jsPDF.
                // For simplicity, we'll assume a default font or that Vazirmatn is somehow available
                // via browser's default or a pre-configured jsPDF setup.
                // doc.setFont('Vazirmatn', 'normal'); // Uncomment if Vazirmatn is properly added to jsPDF

                await html2canvas(content, {
                    scale: 2, // Increase scale for better quality
                    useCORS: true, // If images/assets are from different origin
                    windowWidth: document.body.scrollWidth,
                    windowHeight: document.body.scrollHeight,
                }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const imgWidth = 210; // A4 width in mm
                    const pageHeight = 297; // A4 height in mm
                    const imgHeight = canvas.height * imgWidth / canvas.width;
                    let heightLeft = imgHeight;
                    let position = 0;

                    doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                    heightLeft -= pageHeight;

                    while (heightLeft >= 0) {
                        position = heightLeft - imgHeight;
                        doc.addPage();
                        doc.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                        heightLeft -= pageHeight;
                    }
                    doc.save("گزارش.pdf");
                    showMessage('فایل PDF با موفقیت صادر شد.', 'success');

                    // Revert display style if temporarily changed
                    if (isHidden) {
                        content.style.display = ''; // Revert to original display
                        content.classList.remove('temp-visible-for-pdf');
                    }
                }).catch(error => {
                    console.error("Error generating PDF:", error);
                    showMessage('خطا در تولید PDF: ' + error.message, 'error');
                    // Ensure display style is reverted even on error
                    if (content.classList.contains('temp-visible-for-pdf')) {
                        content.style.display = '';
                        content.classList.remove('temp-visible-for-pdf');
                    }
                });
            } else {
                showMessage('محتوای گزارش برای تولید PDF یافت نشد.', 'error');
            }
        });
    }
}

// Call setupExportButtons when DOM is ready
document.addEventListener('DOMContentLoaded', setupExportButtons);
