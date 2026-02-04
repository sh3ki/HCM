<?php
// Include authentication helper and require proper authentication
require_once __DIR__ . '/../includes/auth_helper.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compensation Planning - HCM System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            <!-- Alert Messages -->
            <div id="alert-container"></div>

            <!-- Page Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Compensation Planning</h1>
                    <p class="text-gray-600">Manage employee salaries, bonuses, and compensation structures</p>
                </div>
                <button class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center" onclick="openModal('add-compensation-modal')">
                    <i class="fas fa-plus mr-2"></i>
                    Add Compensation Plan
                </button>
            </div>

          
            <!-- Filter and Search Bar -->
            <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <input type="search" id="compensation-search" class="w-full bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-primary focus:border-primary block pl-10 p-2.5" placeholder="Search by employee name or position...">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-500"></i>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2">
                       

                        <select id="plan-type-filter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary focus:border-primary block w-40 p-2.5">
                            <option value="">All Plans</option>
                            <option value="Salary">Salary</option>
                            <option value="Bonus">Bonus</option>
                            <option value="Allowance">Allowance</option>
                        </select>

                        <button id="export-btn" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                            <i class="fas fa-download mr-2"></i>
                            Export
                        </button>
                    </div>
                </div>
            </div>

            <!-- Compensation Plans Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Compensation Plans</h3>
                    <p id="plans-count" class="text-sm text-gray-600">Loading plans...</p>
                </div>

                <div class="overflow-x-auto">
                    <table id="compensation-table" class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 cursor-pointer" data-sort="employee">Employee</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="position">Position</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="plan_type">Plan Type</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="amount">Amount</th>
                                <th class="px-6 py-3 cursor-pointer" data-sort="effective_date">Effective Date</th>
                                <th class="px-6 py-3">Remarks</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="compensation-table-body">
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center">
                                    <div class="flex justify-center">
                                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                
            </div>
        </div>
    </div>

    <div id="add-compensation-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
  <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
    
    <!-- Overlay -->
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity modal-overlay"></div>

    <!-- Modal Content -->
    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
      <div class="bg-white px-6 pt-6 pb-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg leading-6 font-medium text-gray-900">Add Compensation Plan</h3>
          <button type="button" class="text-gray-400 hover:text-gray-600" onclick="closeModal('add-compensation-modal')">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <form id="compensation-form" class="space-y-4">
         <!-- Employee Dropdown -->
<select id="employee" name="employee_id" required class="w-full border rounded-lg p-2">
    <option value="">Select Employee</option>
    <?php
    require_once __DIR__ . '/../config/database.php';
    $stmt = $pdo->query("SELECT id, first_name, last_name FROM employees ORDER BY first_name");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<option value='{$row['id']}'>{$row['first_name']} {$row['last_name']}</option>";
    }
    ?>
</select>

<!-- Position Dropdown -->
<div>
    <label for="position" class="block text-sm font-medium text-gray-700">Position</label>
    <select id="position" name="position" 
        class="mt-1 block w-full border border-gray-300 rounded-lg p-2 focus:ring-primary focus:border-primary">
        <option value="">Select Position</option>
        <?php
        $stmt = $pdo->query("SELECT position_title FROM positions ORDER BY position_title");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $title = htmlspecialchars($row['position_title']);
            echo "<option value='{$title}'>{$title}</option>";
        }
        ?>
    </select>
</div>



          <div>
            <label for="plan_type" class="block text-sm font-medium text-gray-700">Plan Type</label>
            <select id="plan_type" name="plan_type" class="mt-1 block w-full border border-gray-300 rounded-lg p-2 focus:ring-primary focus:border-primary">
              <option value="Salary">Salary</option>
              <option value="Bonus">Bonus</option>
              <option value="Allowance">Allowance</option>
            </select>
          </div>

          <div>
            <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
            <input type="number" id="amount" name="amount" class="mt-1 block w-full border border-gray-300 rounded-lg p-2 focus:ring-primary focus:border-primary">
          </div>

          <div>
            <label for="effective_date" class="block text-sm font-medium text-gray-700">Effective Date</label>
            <input type="date" id="effective_date" name="effective_date" class="mt-1 block w-full border border-gray-300 rounded-lg p-2 focus:ring-primary focus:border-primary">
          </div>

          <div>
            <label for="remarks" class="block text-sm font-medium text-gray-700">Remarks</label>
            <textarea id="remarks" name="remarks" rows="3" class="mt-1 block w-full border border-gray-300 rounded-lg p-2 focus:ring-primary focus:border-primary"></textarea>
          </div>

          <div class="flex justify-end">
            <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 mr-2" onclick="closeModal('add-compensation-modal')">Cancel</button>
            <button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>


<script>
/**
 * Open modal by ID
 */
function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.remove("hidden");
    document.body.classList.add("overflow-hidden"); // prevent background scroll
  }
}

/**
 * Close modal by ID
 */
function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) {
    modal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
  }
}

/**
 * Close modal when ESC key is pressed
 */
document.addEventListener("keydown", (event) => {
  if (event.key === "Escape") {
    document.querySelectorAll("[id$='-modal']").forEach((modal) => {
      if (!modal.classList.contains("hidden")) {
        modal.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
      }
    });
  }
});

let allPlans = []; // store all plans for filtering/searching

/**
 * Automatically attach overlay close behavior
 */
document.addEventListener("click", (event) => {
  if (event.target.classList.contains("modal-overlay")) {
    const modal = event.target.closest("[id$='-modal']");
    if (modal) {
      modal.classList.add("hidden");
      document.body.classList.remove("overflow-hidden");
    }
  }
});


const apiUrl = "http://localhost/HCM/api/compensation_planning.php"; // adjust path if needed


// Fetch all compensation plans and populate table
async function loadCompensations() {
    const tbody = document.getElementById("compensation-table-body");
    tbody.innerHTML = `
        <tr>
            <td colspan="7" class="px-6 py-8 text-center">
                <div class="flex justify-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                </div>
            </td>
        </tr>
    `;

    try {
        const res = await fetch(apiUrl);
        const data = await res.json();

        allPlans = data; // save master copy
        applyFilters();  // apply search + filter
    } catch (error) {
        console.error("Error loading compensations:", error);
        tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-4 text-center text-red-500">Failed to load data</td></tr>`;
    }
}

/**
 * Apply search + plan-type filter
 */
function applyFilters() {
    const tbody = document.getElementById("compensation-table-body");
    const searchValue = document.getElementById("compensation-search").value.toLowerCase();
    const planType = document.getElementById("plan-type-filter").value;

    let filtered = allPlans.filter(plan => {
        const matchesSearch =
            plan.first_name.toLowerCase().includes(searchValue) ||
            plan.last_name.toLowerCase().includes(searchValue) ||
            (plan.position_title && plan.position_title.toLowerCase().includes(searchValue));

        const matchesPlanType = planType === "" || plan.plan_type === planType;

        return matchesSearch && matchesPlanType;
    });

    tbody.innerHTML = "";
    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No records found</td></tr>`;
        document.getElementById("plans-count").innerText = "0 plans";
        return;
    }

    document.getElementById("plans-count").innerText = `${filtered.length} plans`;

    filtered.forEach(plan => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td class="px-6 py-4">${plan.first_name} ${plan.last_name}</td>
            <td class="px-6 py-4">${plan.position_title || "-"}</td>
            <td class="px-6 py-4">${plan.plan_type}</td>
            <td class="px-6 py-4">₱${parseFloat(plan.amount).toLocaleString()}</td>
            <td class="px-6 py-4">${plan.effective_date}</td>
            <td class="px-6 py-4">${plan.remarks || "-"}</td>
           <td class="px-6 py-4">
        <button class="bg-blue-100 text-blue-600 hover:bg-blue-200 p-2 rounded-lg mr-2"
                title="Edit" onclick="editCompensation(${plan.id})">
            <i class="fas fa-edit"></i>
        </button>
        <button class="bg-red-100 text-red-600 hover:bg-red-200 p-2 rounded-lg"
                title="Delete" onclick="deleteCompensation(${plan.id})">
            <i class="fas fa-trash"></i>
        </button>
    </td>
        `;
        tbody.appendChild(row);
    });
}

// Event listeners for search & filter
document.getElementById("compensation-search").addEventListener("input", applyFilters);
document.getElementById("plan-type-filter").addEventListener("change", applyFilters);

document.getElementById("compensation-form").addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = {
    employee_id: document.getElementById("employee").value,
    position_title: document.getElementById("position").value, // ✅ match API
    plan_type: document.getElementById("plan_type").value,
    amount: document.getElementById("amount").value,
    effective_date: document.getElementById("effective_date").value,
    remarks: document.getElementById("remarks").value,
};


    try {
        let res, result;
        if (editingId) {
            // Update existing
            res = await fetch(`${apiUrl}?id=${editingId}`, {
                method: "PUT",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(formData)
            });
        } else {
            // Add new
            res = await fetch(apiUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(formData)
            });
        }

        result = await res.json();

        if (res.ok && (result.success || result.id)) {
            alert(editingId ? "Compensation plan updated!" : "Compensation plan added!");
            closeModal('add-compensation-modal');
            loadCompensations();
            editingId = null; // reset
            e.target.reset();
        } else {
            alert("Error: " + (result.message || "Failed to save compensation"));
        }
    } catch (error) {
        console.error("Error saving compensation:", error);
        alert("Network error. Please try again.");
    }
});



async function deleteCompensation(id) {
    if (!confirm("Are you sure you want to delete this compensation plan?")) return;

    try {
        const res = await fetch(`${apiUrl}?id=${id}`, { method: "DELETE" });
        const result = await res.json();

        if (result.success) {
            alert("Compensation plan deleted successfully!");
            loadCompensations();
        } else {
            alert("Failed to delete compensation plan.");
        }
    } catch (error) {
        console.error("Error deleting compensation:", error);
        alert("Network error. Please try again.");
    }
}


let editingId = null; // track which record is being edited

async function editCompensation(id) {
    try {
        const res = await fetch(`${apiUrl}?id=${id}`);
        const plan = await res.json();

        if (!plan || plan.message) {
            alert("Compensation not found");
            return;
        }

        // Fill form
        document.getElementById("employee").value = plan.employee_id;
        document.getElementById("position").value = plan.position || "";
        document.getElementById("plan_type").value = plan.plan_type;
        document.getElementById("amount").value = plan.amount;
        document.getElementById("effective_date").value = plan.effective_date;
        document.getElementById("remarks").value = plan.remarks || "";

        editingId = id;
        openModal("add-compensation-modal");
    } catch (error) {
        console.error("Error fetching plan:", error);
        alert("Error loading compensation for editing");
    }
}

// Load compensations on page load
document.addEventListener("DOMContentLoaded", loadCompensations);
</script>

    <!-- Include global scripts for header dropdown functionality -->
    <?php include 'includes/scripts.php'; ?>
</body>
</html>
