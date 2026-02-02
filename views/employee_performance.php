<?php
// Include authentication helper and require proper authentication
require_once __DIR__ . '/../includes/auth_helper.php';
requireAuth();

// Check if user has employee role and appropriate permissions
$currentUser = getCurrentUser();
checkPermission(['performance_history']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance History - HCM System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1b68ff',
                        secondary: '#6c757d',
                        success: '#3ad29f',
                        danger: '#dc3545',
                        warning: '#eea303',
                        info: '#17a2b8',
                        light: '#f8f9fa',
                        dark: '#343a40'
                    },
                    fontFamily: {
                        'sans': ['Inter', 'ui-sans-serif', 'system-ui']
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Top Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="p-4 sm:ml-64">
        <div class="p-4 rounded-lg mt-14">
            <!-- Notification Container -->
            <div id="notificationContainer" class="fixed top-20 right-4 z-40 space-y-2"></div>

            <!-- Welcome Banner -->
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 mb-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold mb-2">
                            <i class="fas fa-award mr-3"></i>Your Performance Journey
                        </h1>
                        <p class="text-purple-100">Track your growth and celebrate your achievements</p>
                    </div>
                    <div class="hidden md:block">
                        <i class="fas fa-trophy text-white text-6xl opacity-20"></i>
                    </div>
                </div>
            </div>

            <!-- Performance Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg shadow-md border-l-4 border-yellow-500 p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-yellow-800">Latest Score</h3>
                        <div class="bg-yellow-500 rounded-full p-2">
                            <i class="fas fa-star text-xl text-white"></i>
                        </div>
                    </div>
                    <p id="latestRating" class="text-3xl font-bold text-yellow-900">-</p>
                    <p class="text-xs text-yellow-600 mt-2"><i class="fas fa-check-circle mr-1"></i>Your most recent review</p>
                </div>

                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg shadow-md border-l-4 border-blue-500 p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-blue-800">Your Average</h3>
                        <div class="bg-blue-500 rounded-full p-2">
                            <i class="fas fa-chart-line text-xl text-white"></i>
                        </div>
                    </div>
                    <p id="averageRating" class="text-3xl font-bold text-blue-900">-</p>
                    <p class="text-xs text-blue-600 mt-2"><i class="fas fa-calculator mr-1"></i>Overall performance score</p>
                </div>

                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg shadow-md border-l-4 border-green-500 p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-green-800">Reviews</h3>
                        <div class="bg-green-500 rounded-full p-2">
                            <i class="fas fa-clipboard-check text-xl text-white"></i>
                        </div>
                    </div>
                    <p id="totalReviews" class="text-3xl font-bold text-green-900">0</p>
                    <p class="text-xs text-green-600 mt-2"><i class="fas fa-history mr-1"></i>Total evaluations</p>
                </div>

                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg shadow-md border-l-4 border-purple-500 p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-purple-800">Progress</h3>
                        <div class="bg-purple-500 rounded-full p-2">
                            <i class="fas fa-rocket text-xl text-white"></i>
                        </div>
                    </div>
                    <p id="improvement" class="text-3xl font-bold text-purple-900">-</p>
                    <p class="text-xs text-purple-600 mt-2"><i class="fas fa-arrow-up mr-1"></i>Since last review</p>
                </div>
            </div>

            <!-- Performance Trend Chart -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-1">
                    <i class="fas fa-chart-line text-primary mr-2"></i>Your Performance Growth
                </h2>
                <p class="text-sm text-gray-500 mb-4">See how you've improved over time</p>
                <div style="height: 250px;">
                    <canvas id="performanceTrendChart"></canvas>
                </div>
            </div>

            <!-- Performance Categories -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Skills Assessment -->
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-1">
                        <i class="fas fa-tasks text-primary mr-2"></i>Your Skills Ratings
                    </h2>
                    <p class="text-sm text-gray-500 mb-4">How you're performing in each area</p>
                    <div id="skillsAssessment">
                        <div class="text-center py-8">
                            <div class="animate-spin h-8 w-8 border-4 border-primary border-t-transparent rounded-full mx-auto mb-4"></div>
                            <p class="text-gray-600">Loading...</p>
                        </div>
                    </div>
                </div>

                <!-- Competency Radar -->
                <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-1">
                        <i class="fas fa-radar text-primary mr-2"></i>Your Competency Snapshot
                    </h2>
                    <p class="text-sm text-gray-500 mb-4">Visual overview of your strengths</p>
                    <div style="height: 250px;">
                        <canvas id="competencyRadarChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Performance Review History -->
            <div class="bg-white rounded-lg shadow-md border border-gray-200">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">
                                <i class="fas fa-history text-primary mr-2"></i>Your Review History
                            </h2>
                            <p class="text-sm text-gray-500 mt-1">All your performance evaluations</p>
                        </div>
                        <button onclick="exportPerformanceReport()" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-download mr-2"></i>Export Report
                        </button>
                    </div>
                    <div id="performanceTable" class="overflow-x-auto">
                        <div class="text-center py-8">
                            <div class="animate-spin h-8 w-8 border-4 border-primary border-t-transparent rounded-full mx-auto mb-4"></div>
                            <p class="text-gray-600">Loading performance reviews...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Details Modal -->
    <div id="reviewModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-lg bg-white">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-5 border-b border-gray-200 rounded-t">
                <h3 class="text-xl font-bold text-gray-900">
                    <i class="fas fa-file-alt text-primary mr-2"></i>Performance Review Details
                </h3>
                <button onclick="closeReviewModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6" id="reviewModalContent">
                <!-- Content will be populated by JavaScript -->
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end p-5 border-t border-gray-200 rounded-b">
                <button onclick="closeReviewModal()" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-6 rounded-lg transition">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        const API_BASE_URL = '../api';
        let performanceData = [];
        let performanceTrendChart, competencyRadarChart;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadPerformanceData();
        });

        // Load performance data from API
        async function loadPerformanceData() {
            try {
                const token = localStorage.getItem('token');
                const response = await fetch(`${API_BASE_URL}/employees.php?action=my_performance`, {
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                });

                const data = await response.json();
                if (data.success) {
                    performanceData = data.data || [];
                    updateSummaryCards();
                    updatePerformanceTrendChart();
                    updateCompetencyRadar();
                    updateSkillsAssessment();
                    displayPerformanceTable();
                } else {
                    throw new Error(data.message || 'Failed to load performance data');
                }
            } catch (error) {
                console.error('Error loading performance data:', error);
                // Show sample data for demonstration
                loadSampleData();
            }
        }

        // Load sample data for demonstration
        function loadSampleData() {
            performanceData = [
                {
                    id: 1,
                    review_date: '2025-12-15',
                    period: 'Q4 2025',
                    overall_rating: 4.5,
                    technical_skills: 4.7,
                    communication: 4.3,
                    teamwork: 4.6,
                    leadership: 4.2,
                    problem_solving: 4.5,
                    reviewer: 'John Smith',
                    comments: 'Excellent performance with strong technical skills'
                },
                {
                    id: 2,
                    review_date: '2025-09-15',
                    period: 'Q3 2025',
                    overall_rating: 4.2,
                    technical_skills: 4.5,
                    communication: 4.0,
                    teamwork: 4.3,
                    leadership: 4.0,
                    problem_solving: 4.2,
                    reviewer: 'Jane Doe',
                    comments: 'Good progress, continue improving communication skills'
                },
                {
                    id: 3,
                    review_date: '2025-06-15',
                    period: 'Q2 2025',
                    overall_rating: 4.0,
                    technical_skills: 4.2,
                    communication: 3.8,
                    teamwork: 4.1,
                    leadership: 3.9,
                    problem_solving: 4.0,
                    reviewer: 'John Smith',
                    comments: 'Solid performance, focus on leadership development'
                }
            ];

            updateSummaryCards();
            updatePerformanceTrendChart();
            updateCompetencyRadar();
            updateSkillsAssessment();
            displayPerformanceTable();
        }

        // Update summary cards
        function updateSummaryCards() {
            if (performanceData.length === 0) {
                return;
            }

            const latestRating = performanceData[0].overall_rating;
            const avgRating = performanceData.reduce((sum, r) => sum + parseFloat(r.overall_rating), 0) / performanceData.length;
            const improvement = performanceData.length > 1 ? 
                (performanceData[0].overall_rating - performanceData[1].overall_rating) : 0;

            document.getElementById('latestRating').textContent = latestRating.toFixed(1);
            document.getElementById('averageRating').textContent = avgRating.toFixed(1);
            document.getElementById('totalReviews').textContent = performanceData.length;
            
            const improvementEl = document.getElementById('improvement');
            if (improvement > 0) {
                improvementEl.textContent = `+${improvement.toFixed(1)}`;
                improvementEl.className = 'text-2xl font-bold text-green-600';
            } else if (improvement < 0) {
                improvementEl.textContent = improvement.toFixed(1);
                improvementEl.className = 'text-2xl font-bold text-red-600';
            } else {
                improvementEl.textContent = '0.0';
                improvementEl.className = 'text-2xl font-bold text-gray-600';
            }
        }

        // Update performance trend chart
        function updatePerformanceTrendChart() {
            const labels = performanceData.map(r => r.period).reverse();
            const ratings = performanceData.map(r => r.overall_rating).reverse();

            const ctx = document.getElementById('performanceTrendChart');
            if (performanceTrendChart) performanceTrendChart.destroy();

            performanceTrendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Overall Rating',
                        data: ratings,
                        borderColor: 'rgba(27, 104, 255, 1)',
                        backgroundColor: 'rgba(27, 104, 255, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 5,
                            ticks: {
                                stepSize: 0.5
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Rating: ' + context.parsed.y.toFixed(1) + '/5.0';
                                }
                            }
                        }
                    }
                }
            });
        }

        // Update competency radar chart
        function updateCompetencyRadar() {
            if (performanceData.length === 0) return;

            const latest = performanceData[0];
            const competencies = {
                'Technical Skills': latest.technical_skills,
                'Communication': latest.communication,
                'Teamwork': latest.teamwork,
                'Leadership': latest.leadership,
                'Problem Solving': latest.problem_solving
            };

            const ctx = document.getElementById('competencyRadarChart');
            if (competencyRadarChart) competencyRadarChart.destroy();

            competencyRadarChart = new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: Object.keys(competencies),
                    datasets: [{
                        label: 'Latest Review',
                        data: Object.values(competencies),
                        backgroundColor: 'rgba(27, 104, 255, 0.2)',
                        borderColor: 'rgba(27, 104, 255, 1)',
                        pointBackgroundColor: 'rgba(27, 104, 255, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(27, 104, 255, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 5,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Update skills assessment
        function updateSkillsAssessment() {
            if (performanceData.length === 0) {
                document.getElementById('skillsAssessment').innerHTML = `
                    <p class="text-gray-500 text-center py-4">No skills assessment data available</p>
                `;
                return;
            }

            const latest = performanceData[0];
            const skills = [
                { name: 'Technical Skills', score: latest.technical_skills },
                { name: 'Communication', score: latest.communication },
                { name: 'Teamwork', score: latest.teamwork },
                { name: 'Leadership', score: latest.leadership },
                { name: 'Problem Solving', score: latest.problem_solving }
            ];

            let html = '<div class="space-y-4">';
            skills.forEach(skill => {
                const percentage = (skill.score / 5) * 100;
                const color = skill.score >= 4.5 ? 'bg-green-500' : 
                             skill.score >= 4.0 ? 'bg-blue-500' : 
                             skill.score >= 3.5 ? 'bg-yellow-500' : 'bg-red-500';

                html += `
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-gray-700">${skill.name}</span>
                            <span class="text-sm font-semibold text-gray-900">${skill.score.toFixed(1)}/5.0</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="${color} h-2.5 rounded-full" style="width: ${percentage}%"></div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';

            document.getElementById('skillsAssessment').innerHTML = html;
        }

        // Display performance table
        function displayPerformanceTable() {
            const container = document.getElementById('performanceTable');

            if (!performanceData || performanceData.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">No performance reviews found</p>
                    </div>
                `;
                return;
            }

            let html = `
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Review Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overall Rating</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reviewer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comments</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
            `;

            performanceData.forEach(review => {
                const ratingClass = review.overall_rating >= 4.5 ? 'bg-green-100 text-green-800' :
                                   review.overall_rating >= 4.0 ? 'bg-blue-100 text-blue-800' :
                                   review.overall_rating >= 3.5 ? 'bg-yellow-100 text-yellow-800' :
                                   'bg-red-100 text-red-800';

                html += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${formatDate(review.review_date)}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${review.period}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="${ratingClass} text-xs font-medium px-2.5 py-0.5 rounded">
                                ${review.overall_rating.toFixed(1)}/5.0
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            ${review.reviewer}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                            ${review.comments}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button onclick="viewReviewDetails(${review.id})" class="text-primary hover:text-blue-700">
                                <i class="fas fa-eye mr-2"></i>View Details
                            </button>
                        </td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                </table>
            `;

            container.innerHTML = html;
        }

        // View review details
        function viewReviewDetails(reviewId) {
            const review = performanceData.find(r => r.id === reviewId);
            if (!review) return;

            const ratingClass = review.overall_rating >= 4.5 ? 'text-green-600' :
                               review.overall_rating >= 4.0 ? 'text-blue-600' :
                               review.overall_rating >= 3.5 ? 'text-yellow-600' : 'text-red-600';

            const content = `
                <div class="space-y-6">
                    <!-- Review Info -->
                    <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg p-5 border-l-4 border-purple-500">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-600 mb-1"><i class="fas fa-calendar text-purple-500 mr-2"></i>Period</p>
                                <p class="font-semibold text-gray-900">${review.period}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1"><i class="fas fa-clock text-purple-500 mr-2"></i>Review Date</p>
                                <p class="font-semibold text-gray-900">${formatDate(review.review_date)}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 mb-1"><i class="fas fa-user text-purple-500 mr-2"></i>Reviewed By</p>
                                <p class="font-semibold text-gray-900">${review.reviewer}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Overall Rating -->
                    <div class="text-center bg-gray-50 rounded-lg p-6">
                        <p class="text-sm text-gray-600 mb-2">Overall Performance Rating</p>
                        <p class="text-5xl font-bold ${ratingClass}">${review.overall_rating.toFixed(1)}</p>
                        <p class="text-gray-500 text-sm mt-2">out of 5.0</p>
                    </div>

                    <!-- Individual Ratings -->
                    <div>
                        <h4 class="font-bold text-gray-900 mb-4 text-lg">
                            <i class="fas fa-star text-yellow-500 mr-2"></i>Detailed Ratings
                        </h4>
                        <div class="space-y-3">
                            ${createRatingBar('Technical Skills', review.technical_skills)}
                            ${createRatingBar('Communication', review.communication)}
                            ${createRatingBar('Teamwork', review.teamwork)}
                            ${createRatingBar('Leadership', review.leadership)}
                            ${createRatingBar('Problem Solving', review.problem_solving)}
                        </div>
                    </div>

                    <!-- Comments -->
                    <div>
                        <h4 class="font-bold text-gray-900 mb-3 text-lg">
                            <i class="fas fa-comment-dots text-blue-500 mr-2"></i>Reviewer Comments
                        </h4>
                        <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-4">
                            <p class="text-gray-700 leading-relaxed">${review.comments}</p>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('reviewModalContent').innerHTML = content;
            document.getElementById('reviewModal').classList.remove('hidden');
        }

        // Create rating bar HTML
        function createRatingBar(label, score) {
            const percentage = (score / 5) * 100;
            const color = score >= 4.5 ? 'bg-green-500' : 
                         score >= 4.0 ? 'bg-blue-500' : 
                         score >= 3.5 ? 'bg-yellow-500' : 'bg-red-500';

            return `
                <div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">${label}</span>
                        <span class="text-sm font-bold text-gray-900">${score.toFixed(1)}/5.0</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="${color} h-3 rounded-full transition-all duration-500" style="width: ${percentage}%"></div>
                    </div>
                </div>
            `;
        }

        // Close review modal
        function closeReviewModal() {
            document.getElementById('reviewModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('reviewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReviewModal();
            }
        });

        // Export performance report
        function exportPerformanceReport() {
            if (!performanceData || performanceData.length === 0) {
                showNotification('No data to export', 'warning');
                return;
            }

            let csv = 'Review Date,Period,Overall Rating,Technical Skills,Communication,Teamwork,Leadership,Problem Solving,Reviewer,Comments\n';

            performanceData.forEach(review => {
                csv += `${review.review_date},${review.period},${review.overall_rating},${review.technical_skills},${review.communication},${review.teamwork},${review.leadership},${review.problem_solving},"${review.reviewer}","${review.comments}"\n`;
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `performance_history_${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            showNotification('Performance report exported successfully', 'success');
        }

        // Helper: Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notificationContainer');
            const colors = {
                success: 'bg-green-500',
                error: 'bg-red-500',
                info: 'bg-blue-500',
                warning: 'bg-yellow-500'
            };

            const notification = document.createElement('div');
            notification.className = `${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg transform transition-all duration-500`;
            notification.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            container.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 500);
            }, 5000);
        }
    </script>

    <!-- Include common scripts -->
    <?php include 'includes/scripts.php'; ?>
</body>
</html>
