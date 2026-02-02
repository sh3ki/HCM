<!-- Reusable Confirmation Modal -->
<div id="confirmation-modal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeConfirmationModal()"></div>

        <!-- Modal content -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10" id="modal-icon">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Confirm Action
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="modal-message">
                                Are you sure you want to proceed with this action?
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                        id="modal-confirm-btn">
                    <i class="fas fa-check mr-2"></i>
                    <span id="modal-confirm-text">Confirm</span>
                </button>
                <button type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        onclick="closeConfirmationModal()">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Reusable Confirmation Modal Functions
function showConfirmationModal(options) {
    const modal = document.getElementById('confirmation-modal');
    const titleEl = document.getElementById('modal-title');
    const messageEl = document.getElementById('modal-message');
    const confirmBtn = document.getElementById('modal-confirm-btn');
    const confirmText = document.getElementById('modal-confirm-text');
    const iconEl = document.getElementById('modal-icon');

    // Set modal content
    titleEl.textContent = options.title || 'Confirm Action';
    messageEl.innerHTML = options.message || 'Are you sure you want to proceed?';
    confirmText.textContent = options.confirmText || 'Confirm';

    // Update icon and its container background
    if (options.icon) {
        iconEl.querySelector('i').className = options.icon + ' ' + (options.iconClass || 'text-red-600');

        // Update icon container background color based on the action type
        let bgClass = 'bg-red-100'; // default
        if (options.type === 'warning') {
            bgClass = 'bg-yellow-100';
        } else if (options.type === 'info') {
            bgClass = 'bg-blue-100';
        } else if (options.type === 'success') {
            bgClass = 'bg-green-100';
        }
        iconEl.className = iconEl.className.replace(/bg-\w+-\d+/g, bgClass);
    }

    // Update button styling
    if (options.confirmClass) {
        // Remove existing color classes and add new ones
        confirmBtn.className = confirmBtn.className.replace(/bg-\w+-\d+\s+.*?hover:bg-\w+-\d+/g, options.confirmClass);
    }

    // Set up confirm button click handler
    confirmBtn.onclick = function() {
        if (options.onConfirm && typeof options.onConfirm === 'function') {
            options.onConfirm();
        }
        closeConfirmationModal();
    };

    // Show modal with fade-in effect
    modal.classList.remove('hidden');
    requestAnimationFrame(() => {
        modal.classList.add('opacity-100');
    });
}

function closeConfirmationModal() {
    const modal = document.getElementById('confirmation-modal');
    modal.classList.add('hidden');
    modal.classList.remove('opacity-100');
}

// Generic confirmation modal for any action - available globally
window.confirmAction = function(options) {
    showConfirmationModal(options);
};

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeConfirmationModal();
    }
});

// Example usage functions for common actions
window.confirmDelete = function(itemName, onConfirm) {
    showConfirmationModal({
        title: 'Delete Confirmation',
        message: `Are you sure you want to delete <strong>${itemName}</strong>? This action cannot be undone.`,
        confirmText: 'Delete',
        confirmClass: 'bg-red-600 hover:bg-red-700',
        icon: 'fas fa-trash',
        iconClass: 'text-red-600',
        type: 'danger',
        onConfirm: onConfirm
    });
};

window.confirmTerminate = function(itemName, onConfirm) {
    showConfirmationModal({
        title: 'Terminate Confirmation',
        message: `Are you sure you want to terminate <strong>${itemName}</strong>? This action will change their status to terminated.`,
        confirmText: 'Terminate',
        confirmClass: 'bg-orange-600 hover:bg-orange-700',
        icon: 'fas fa-user-times',
        iconClass: 'text-orange-600',
        type: 'warning',
        onConfirm: onConfirm
    });
};

window.confirmRestore = function(itemName, onConfirm) {
    showConfirmationModal({
        title: 'Restore Confirmation',
        message: `Are you sure you want to restore <strong>${itemName}</strong>? This will reactivate their account.`,
        confirmText: 'Restore',
        confirmClass: 'bg-green-600 hover:bg-green-700',
        icon: 'fas fa-undo',
        iconClass: 'text-green-600',
        type: 'success',
        onConfirm: onConfirm
    });
};

window.confirmSave = function(onConfirm) {
    showConfirmationModal({
        title: 'Save Changes',
        message: 'Are you sure you want to save these changes?',
        confirmText: 'Save',
        confirmClass: 'bg-blue-600 hover:bg-blue-700',
        icon: 'fas fa-save',
        iconClass: 'text-blue-600',
        type: 'info',
        onConfirm: onConfirm
    });
};
</script>