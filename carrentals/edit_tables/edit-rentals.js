function toggleIssueFields() {
    const hasIssue = document.getElementById('hasIssue').checked;
    document.getElementById('issueFields').style.display = hasIssue ? 'block' : 'none';

    const vehicleStatusWarning = document.getElementById('vehicleStatusWarning');
    if (vehicleStatusWarning) {
        vehicleStatusWarning.style.display = hasIssue ? 'block' : 'none';
    }
}

function calculateLateFee() {
    const returnDate = document.getElementById('ReturnedDate').value;
    const dueDate = window.rentalData.dueDate;
    const dailyRate = window.rentalData.dailyRate;
    
    if (returnDate && new Date(returnDate) > new Date(dueDate)) {
        const lateDays = Math.ceil((new Date(returnDate) - new Date(dueDate)) / (1000 * 3600 * 24));
        const lateFee = lateDays * (dailyRate * 1.5);
        document.getElementById('lateFeeInfo').innerHTML = '<div class="late-fee-warning"><i class="fas fa-exclamation-triangle"></i> Late return: ' + lateDays + ' day(s) × ₱' + (dailyRate * 1.5).toFixed(2) + ' = ₱' + lateFee.toFixed(2) + '</div>';
    } else {
        document.getElementById('lateFeeInfo').innerHTML = '';
    }
}

function showPaymentModal(dueItems, totalDue) {
    const modal = document.createElement('div');
    modal.className = 'payment-modal-overlay';

    const modalContent = document.createElement('div');
    modalContent.className = 'payment-modal-content';

    modalContent.innerHTML = `
        <div class="modal-header">
            <div class="modal-icon-container">
                <i class="fas fa-exclamation-triangle modal-icon"></i>
            </div>
            <h2 class="modal-title">Payment Confirmation Required</h2>
            <p class="modal-subtitle">The following amounts are due for this rental:</p>
        </div>
        
        <div class="due-items-container">
            ${dueItems.map(item => `
                <div class="due-item">
                    <span class="due-item-description">
                        <i class="${item.icon} due-item-icon"></i>${item.description}
                    </span>
                    <span class="due-amount">₱${item.amount.toFixed(2)}</span>
                </div>
            `).join('')}
        </div>
        
        <div class="total-section">
            <div class="total-row">
                <span class="total-label">Total Amount Due:</span>
                <span class="total-amount">₱${totalDue.toFixed(2)}</span>
            </div>
        </div>
        
        <p class="confirmation-text">
            Has the customer paid all due amounts above?
        </p>
        
        <div class="modal-buttons">
            <button class="modal-btn btn-cancel" onclick="closePaymentModal(false)">
                <i class="fas fa-times btn-icon"></i>Not Paid
            </button>
            <button class="modal-btn btn-confirm" onclick="closePaymentModal(true)">
                <i class="fas fa-check btn-icon"></i>Paid
            </button>
        </div>
    `;

    modal.appendChild(modalContent);
    document.body.appendChild(modal);

    document.body.style.overflow = 'hidden';

    return modal;
}

function closePaymentModal(confirmed) {
    const modal = document.querySelector('.payment-modal-overlay');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';

        if (confirmed) {
            const form = document.querySelector('form');
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = 'payment_confirmed';
            hiddenField.value = '1';
            form.appendChild(hiddenField);

            form.submit();
        }
    }
}

function confirmReturn() {
    const returnDate = document.getElementById('ReturnedDate').value;
    const dueDate = window.rentalData.dueDate;
    const paymentMethod = window.rentalData.paymentMethod;
    const dueAmount = window.rentalData.dueAmount;
    const remainingBalance = window.rentalData.remainingBalance;
    const dailyRate = window.rentalData.dailyRate;

    let dueItems = [];
    let totalDue = 0;

    if (paymentMethod === 'downpayment' && remainingBalance > 0) {
        dueItems.push({
            icon: 'fas fa-credit-card',
            description: 'Remaining Balance (Downpayment)',
            amount: remainingBalance
        });
        totalDue += remainingBalance;
    }

    if (dueAmount > 0) {
        dueItems.push({
            icon: 'fas fa-exclamation-circle',
            description: 'Outstanding Due Amount',
            amount: dueAmount
        });
        totalDue += dueAmount;
    }

    if (returnDate && new Date(returnDate) > new Date(dueDate)) {
        const lateDays = Math.ceil((new Date(returnDate) - new Date(dueDate)) / (1000 * 3600 * 24));
        const lateFee = lateDays * (dailyRate * 1.5);
        dueItems.push({
            icon: 'fas fa-clock',
            description: `Late Return Fee (${lateDays} day${lateDays > 1 ? 's' : ''})`,
            amount: lateFee
        });
        totalDue += lateFee;
    }

    if (dueItems.length > 0) {
        showPaymentModal(dueItems, totalDue);
        return false;
    }

    return true;
}

function initializeReturnDate() {
    if (window.rentalData && !window.rentalData.isReturned) {
        document.getElementById('ReturnedDate').value = new Date().toISOString().split('T')[0];
        calculateLateFee();
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const issueUpload = document.getElementById('issue-upload');
    const issueUploadText = document.getElementById('issue-upload-text');
    const issueFileName = document.getElementById('issue-file-name');

    if (issueUpload) {
        issueUpload.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                issueUploadText.textContent = 'Image selected';
                issueFileName.textContent = 'Selected: ' + file.name;
                issueFileName.style.display = 'block';

                const uploadLabel = issueUpload.closest('.image-upload');
                uploadLabel.style.borderColor = '#059669';
                uploadLabel.style.backgroundColor = '#f0fdf4';
            }
        });
    }

    const uploadLabel = document.querySelector('.image-upload');
    if (uploadLabel) {
        uploadLabel.addEventListener('mouseover', function () {
            this.style.borderColor = '#94a3b8';
            this.style.backgroundColor = '#f1f5f9';
        });

        uploadLabel.addEventListener('mouseout', function () {
            const file = document.getElementById('issue-upload').files[0];
            if (!file) {
                this.style.borderColor = '#cbd5e1';
                this.style.backgroundColor = '#f8fafc';
            }
        });
    }

    initializeReturnDate();
});