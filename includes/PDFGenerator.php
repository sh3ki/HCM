<?php
// PDFGenerator.php - Simple PDF generation using HTML to PDF conversion

class PDFGenerator {
    public $html;
    private $title;
    private $orientation;

    public function __construct($title = 'HCM Report', $orientation = 'portrait') {
        $this->title = $title;
        $this->orientation = $orientation;
        $this->html = '';
    }

    public function setHTML($html) {
        $this->html = $html;
    }

    public function addReportHeader($reportType, $generatedAt, $filters = []) {
        $filterText = '';
        if (!empty($filters)) {
            $filterParts = [];
            foreach ($filters as $key => $value) {
                if (!empty($value)) {
                    $filterParts[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $value;
                }
            }
            if (!empty($filterParts)) {
                $filterText = '<p class="filters"><strong>Filters:</strong> ' . implode(', ', $filterParts) . '</p>';
            }
        }

        $header = '
        <div class="report-header">
            <h1>HCM System - ' . ucfirst($reportType) . ' Report</h1>
            <p class="generated-date">Generated on: ' . date('F j, Y g:i A', strtotime($generatedAt)) . '</p>
            ' . $filterText . '
        </div>';

        $this->html = $header . $this->html;
    }

    public function addEmployeeTable($employees) {
        $tableHTML = '
        <h2>Employee Details</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Full Name</th>
                    <th>Department</th>
                    <th>Position</th>
                    <th>Email</th>
                    <th>Hire Date</th>
                    <th>Basic Salary</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($employees as $emp) {
            $tableHTML .= '
                <tr>
                    <td>' . htmlspecialchars($emp['employee_id'] ?? '') . '</td>
                    <td>' . htmlspecialchars($emp['full_name'] ?? '') . '</td>
                    <td>' . htmlspecialchars($emp['department'] ?? '') . '</td>
                    <td>' . htmlspecialchars($emp['position'] ?? '') . '</td>
                    <td>' . htmlspecialchars($emp['email'] ?? '') . '</td>
                    <td>' . htmlspecialchars($emp['hire_date'] ?? '') . '</td>
                    <td>₱' . number_format($emp['basic_salary'] ?? 0) . '</td>
                </tr>';
        }

        $tableHTML .= '
            </tbody>
        </table>
        <p class="record-count">Total Records: ' . count($employees) . '</p>';

        $this->html .= $tableHTML;
    }

    public function addDepartmentTable($departments) {
        $tableHTML = '
        <h2>Department Performance</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Employees</th>
                    <th>Average Salary</th>
                    <th>Attendance Rate</th>
                    <th>Performance</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($departments as $dept) {
            $performanceClass = ($dept['attendance_rate'] ?? 0) >= 95 ? 'excellent' :
                               (($dept['attendance_rate'] ?? 0) >= 90 ? 'good' : 'needs-improvement');
            $performanceText = ($dept['attendance_rate'] ?? 0) >= 95 ? 'Excellent' :
                              (($dept['attendance_rate'] ?? 0) >= 90 ? 'Good' : 'Needs Improvement');

            $tableHTML .= '
                <tr>
                    <td>' . htmlspecialchars($dept['department'] ?? $dept['dept_name'] ?? '') . '</td>
                    <td>' . ($dept['employee_count'] ?? $dept['employees'] ?? 0) . '</td>
                    <td>₱' . number_format($dept['avg_salary'] ?? 0) . '</td>
                    <td>' . ($dept['attendance_rate'] ?? 0) . '%</td>
                    <td class="' . $performanceClass . '">' . $performanceText . '</td>
                </tr>';
        }

        $tableHTML .= '
            </tbody>
        </table>';

        $this->html .= $tableHTML;
    }

    public function addSummaryStats($summary, $title = 'Summary Statistics') {
        $statsHTML = '<h2>' . $title . '</h2><div class="summary-stats">';

        foreach ($summary as $key => $value) {
            $label = ucfirst(str_replace('_', ' ', $key));
            $statsHTML .= '<div class="stat-item"><strong>' . $label . ':</strong> ' . $value . '</div>';
        }

        $statsHTML .= '</div>';
        $this->html .= $statsHTML;
    }

    public function generatePDF($filename = null) {
        if (!$filename) {
            $filename = 'hcm_report_' . date('Y-m-d_H-i-s') . '.pdf';
        }

        $fullHTML = $this->getFullHTML();

        // For this implementation, we'll use wkhtmltopdf (if available) or fall back to basic HTML output
        // In a production environment, you'd use libraries like TCPDF, FPDF, or Dompdf

        // Check if wkhtmltopdf is available
        $wkhtmltopdf = $this->findWkhtmltopdf();

        if ($wkhtmltopdf) {
            return $this->generateWithWkhtmltopdf($fullHTML, $filename, $wkhtmltopdf);
        } else {
            // Fallback: serve as HTML with PDF-like styling
            return $this->generateHTMLFallback($fullHTML, $filename);
        }
    }

    private function findWkhtmltopdf() {
        // Common paths where wkhtmltopdf might be installed
        $possiblePaths = [
            'wkhtmltopdf',
            '/usr/bin/wkhtmltopdf',
            '/usr/local/bin/wkhtmltopdf',
            'C:\Program Files\wkhtmltopdf\bin\wkhtmltopdf.exe',
            'C:\wkhtmltopdf\bin\wkhtmltopdf.exe'
        ];

        foreach ($possiblePaths as $path) {
            if (shell_exec("where $path 2>nul") || shell_exec("which $path 2>/dev/null")) {
                return $path;
            }
        }

        return null;
    }

    private function generateWithWkhtmltopdf($html, $filename, $wkhtmltopdf) {
        $tempHtmlFile = tempnam(sys_get_temp_dir(), 'hcm_report') . '.html';
        $tempPdfFile = tempnam(sys_get_temp_dir(), 'hcm_report') . '.pdf';

        file_put_contents($tempHtmlFile, $html);

        $command = sprintf(
            '"%s" --page-size A4 --orientation %s --margin-top 20 --margin-bottom 20 --margin-left 20 --margin-right 20 "%s" "%s"',
            $wkhtmltopdf,
            $this->orientation,
            $tempHtmlFile,
            $tempPdfFile
        );

        shell_exec($command);

        if (file_exists($tempPdfFile) && filesize($tempPdfFile) > 0) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($tempPdfFile));

            readfile($tempPdfFile);

            unlink($tempHtmlFile);
            unlink($tempPdfFile);

            return true;
        }

        unlink($tempHtmlFile);
        if (file_exists($tempPdfFile)) {
            unlink($tempPdfFile);
        }

        return false;
    }

    private function generateHTMLFallback($html, $filename) {
        // Serve as HTML with print-friendly styling
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: inline; filename="' . str_replace('.pdf', '.html', $filename) . '"');

        echo $html;
        echo '<script>window.print();</script>'; // Auto-trigger print dialog

        return true;
    }

    private function getFullHTML() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>' . htmlspecialchars($this->title) . '</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.4;
                    margin: 0;
                    padding: 20px;
                    color: #333;
                }

                .report-header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 2px solid #1b68ff;
                    padding-bottom: 20px;
                }

                .report-header h1 {
                    color: #1b68ff;
                    margin: 0 0 10px 0;
                    font-size: 24px;
                }

                .generated-date {
                    color: #666;
                    margin: 5px 0;
                }

                .filters {
                    color: #666;
                    font-size: 11px;
                    margin: 10px 0;
                }

                h2 {
                    color: #1b68ff;
                    font-size: 16px;
                    margin: 25px 0 15px 0;
                    border-bottom: 1px solid #ddd;
                    padding-bottom: 5px;
                }

                .data-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                    font-size: 11px;
                }

                .data-table th {
                    background-color: #f8f9fa;
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                    font-weight: bold;
                    color: #333;
                }

                .data-table td {
                    border: 1px solid #ddd;
                    padding: 6px 8px;
                }

                .data-table tr:nth-child(even) {
                    background-color: #f9f9f9;
                }

                .summary-stats {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 10px;
                    margin-bottom: 20px;
                }

                .stat-item {
                    padding: 10px;
                    background-color: #f8f9fa;
                    border-left: 4px solid #1b68ff;
                }

                .record-count {
                    text-align: right;
                    font-weight: bold;
                    color: #666;
                    margin-top: 10px;
                }

                .excellent { color: #28a745; font-weight: bold; }
                .good { color: #ffc107; font-weight: bold; }
                .needs-improvement { color: #dc3545; font-weight: bold; }

                @media print {
                    body { margin: 0; }
                    .report-header { page-break-after: avoid; }
                    .data-table { page-break-inside: avoid; }
                    h2 { page-break-after: avoid; }
                }
            </style>
        </head>
        <body>
            ' . $this->html . '
        </body>
        </html>';
    }
}
?>