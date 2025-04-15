// import jsPDF from 'jspdf';
// import autoTable from 'jspdf-autotable';
// import { saveAs } from 'file-saver';

// Export to CSV
// export const exportToCsv = (data, filename) => {
//   const csvContent = data
//     .map((row) => Object.values(row).join(','))
//     .join('\n');
//   const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
//   saveAs(blob, filename);
// };

// // Export to PDF
// export const exportToPdf = (data, filename) => {
//   const doc = new jsPDF();
//   autoTable(doc, { html: '#table' });
//   doc.save(filename);
// };

// Temporarily disable the export functions
export const exportToCsv = (data, filename) => {
    console.warn('Export to CSV is temporarily disabled.');
  };
  
  export const exportToPdf = (data, filename) => {
    console.warn('Export to PDF is temporarily disabled.');
  };