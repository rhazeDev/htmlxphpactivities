function calcRent() {
    const pickUpDate = document.querySelector('input[name="PickUpDate"]');
    const toReturnDate = document.querySelector('input[name="ToReturnDate"]');
    const vehicleSelect = document.querySelector('select[name="VehicleID"]');
    const costBreakdown = document.getElementById('cost-breakdown');
    const finalCostDisplay = document.getElementById('calculated-cost');

    let rentalCost = 0;
    let deliveryCost = 0;
    let downpaymentCost = 0;
    let totalCost = 0;

    if (pickUpDate && toReturnDate && vehicleSelect &&
        pickUpDate.value && toReturnDate.value && vehicleSelect.value) {

        const pickUp = new Date(pickUpDate.value);
        const returnDate = new Date(toReturnDate.value);

        const selectedOption = vehicleSelect.options[vehicleSelect.selectedIndex];
        const rate = selectedOption && selectedOption.dataset.rate ? parseFloat(selectedOption.dataset.rate) : 0;

        if (pickUp && returnDate && pickUp <= returnDate && rate > 0) {
            const timeDiff = returnDate.getTime() - pickUp.getTime();
            const noOfDays = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;

            if (noOfDays > 30) {
                toReturnDate.setCustomValidity('Maximum rental period is 30 days. Please select an earlier return date.');
                costBreakdown.innerHTML = '<div style="color: #dc2626; font-weight: bold; padding: 1rem; background: #fef2f2; border-radius: 6px; border-left: 4px solid #ef4444;"><i class="fas fa-exclamation-triangle"></i> Maximum rental period exceeded! Please select a return date within 30 days.</div>';
                finalCostDisplay.textContent = '₱0.00';
                return;
            } else {
                toReturnDate.setCustomValidity('');
            }

            rentalCost = noOfDays * rate;
        }
    }

    const rentalTransport = document.querySelector('select[name="RentalTransport"]');
    if (rentalTransport && rentalTransport.value === 'Delivery') {
        deliveryCost = 500;
    }

    const paymentMethod = document.querySelector('select[name="PaymentMethod"]');
    if (paymentMethod && paymentMethod.value === 'Downpayment') {
        downpaymentCost = (rentalCost + deliveryCost) * 0.10;
    }

    totalCost = rentalCost + deliveryCost + downpaymentCost;

    if (costBreakdown) {
        let breakdownHTML = '';
        if (rentalCost > 0) {
            breakdownHTML += '<div class="cost-item"><span>Rental Cost:</span><span>₱' + rentalCost.toFixed(2) + '</span></div>';
        }
        if (deliveryCost > 0) {
            breakdownHTML += '<div class="cost-item"><span>Delivery Cost:</span><span>₱' + deliveryCost.toFixed(2) + '</span></div>';
        }
        if (downpaymentCost > 0) {
            breakdownHTML += '<div class="cost-item"><span>Downpayment Fee (10%):</span><span>₱' + downpaymentCost.toFixed(2) + '</span></div>';
        }
        if (breakdownHTML) {
            breakdownHTML += '<div class="cost-separator"></div>';
        }
        costBreakdown.innerHTML = breakdownHTML;
    }

    if (finalCostDisplay) {
        finalCostDisplay.textContent = '₱' + totalCost.toFixed(2);
    }

    updateAmountToPay(totalCost);
}

function updateAmountToPay(totalCost) {
    const paymentMethod = document.querySelector('select[name="PaymentMethod"]');
    const amountToPayField = document.querySelector('input[name="AmountToPay"]');

    if (paymentMethod && amountToPayField) {
        if (paymentMethod.value === 'Downpayment') {
            const minDownpayment = totalCost * 0.50;
            amountToPayField.min = minDownpayment.toFixed(2);
            amountToPayField.max = totalCost.toFixed(2);
            amountToPayField.value = minDownpayment.toFixed(2);
            amountToPayField.placeholder = 'Min: ₱' + minDownpayment.toFixed(2);
        } else {
            amountToPayField.value = totalCost.toFixed(2);
            amountToPayField.min = totalCost.toFixed(2);
            amountToPayField.max = totalCost.toFixed(2);
        }
    }
}

function togglePaymentFields() {
    const paymentMethod = document.querySelector('select[name="PaymentMethod"]');
    const amountToPayGroup = document.getElementById('amount-to-pay-group');

    if (paymentMethod && amountToPayGroup) {
        if (paymentMethod.value === 'Downpayment') {
            amountToPayGroup.style.display = 'block';
            amountToPayGroup.querySelector('input').required = true;
        } else if (paymentMethod.value === 'Cash') {
            amountToPayGroup.style.display = 'none';
            amountToPayGroup.querySelector('input').required = false;
        } else {
            amountToPayGroup.style.display = 'none';
            amountToPayGroup.querySelector('input').required = false;
        }
    }

    calcRent();
}

function validateDownpayment() {
    const amountToPayField = document.querySelector('input[name="AmountToPay"]');
    const paymentMethod = document.querySelector('select[name="PaymentMethod"]');

    if (paymentMethod && paymentMethod.value === 'Downpayment' && amountToPayField) {
        const amount = parseFloat(amountToPayField.value);
        const min = parseFloat(amountToPayField.min);
        const max = parseFloat(amountToPayField.max);

        if (amount < min) {
            amountToPayField.setCustomValidity('Downpayment must be at least 50% of total cost (₱' + min.toFixed(2) + ')');
        } else if (amount > max) {
            amountToPayField.setCustomValidity('Amount cannot exceed total cost (₱' + max.toFixed(2) + ')');
        } else {
            amountToPayField.setCustomValidity('');
        }
    }
}

function validateForm() {
    const pickUpDate = document.querySelector('input[name="PickUpDate"]');
    const toReturnDate = document.querySelector('input[name="ToReturnDate"]');
    const paymentMethod = document.querySelector('select[name="PaymentMethod"]');
    const amountToPayField = document.querySelector('input[name="AmountToPay"]');

    if (pickUpDate && toReturnDate && pickUpDate.value && toReturnDate.value) {
        const pickUp = new Date(pickUpDate.value);
        const returnDate = new Date(toReturnDate.value);
        const timeDiff = returnDate.getTime() - pickUp.getTime();
        const noOfDays = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;

        if (noOfDays > 30) {
            alert('Maximum rental period is 30 days. Current selection: ' + noOfDays + ' days. Please select an earlier return date.');
            toReturnDate.focus();
            return false;
        }

        if (noOfDays < 1) {
            alert('Return date must be after pickup date.');
            toReturnDate.focus();
            return false;
        }
    }

    if (paymentMethod && paymentMethod.value === 'Downpayment') {
        if (!amountToPayField.value) {
            alert('Please enter the amount to pay for downpayment.');
            amountToPayField.focus();
            return false;
        }

        const amount = parseFloat(amountToPayField.value);
        const min = parseFloat(amountToPayField.min);
        const max = parseFloat(amountToPayField.max);

        if (amount < min) {
            alert('Downpayment must be at least 50% of total cost (₱' + min.toFixed(2) + ')');
            amountToPayField.focus();
            return false;
        }

        if (amount > max) {
            alert('Amount cannot exceed total cost (₱' + max.toFixed(2) + ')');
            amountToPayField.focus();
            return false;
        }
    }

    return true;
}

function carRate() {
    calcRent();
}

function toggleDeliveryAddress() {
    const rentalTransport = document.querySelector('select[name="RentalTransport"]');
    const deliveryAddressGroup = document.getElementById('delivery-address-group');
    const deliveryNote = document.getElementById('delivery-fee-note');

    if (rentalTransport && deliveryAddressGroup) {
        if (rentalTransport.value === 'Delivery') {
            deliveryAddressGroup.style.display = 'block';
            deliveryAddressGroup.style.opacity = '1';
            deliveryAddressGroup.querySelector('input').required = true;

            if (deliveryNote) {
                deliveryNote.style.display = 'block';
            }
        } else {
            deliveryAddressGroup.style.opacity = '0';
            setTimeout(() => {
                deliveryAddressGroup.style.display = 'none';
            }, 300);
            deliveryAddressGroup.querySelector('input').required = false;
            deliveryAddressGroup.querySelector('input').value = '';

            if (deliveryNote) {
                deliveryNote.style.display = 'none';
            }
        }
    }

    calcRent();
}

document.addEventListener('DOMContentLoaded', function () {
    const carImageUpload = document.getElementById('car-image-upload');
    if (carImageUpload) {
        carImageUpload.addEventListener('change', function (e) {
            const fileInput = e.target;
            const fileName = fileInput.files[0]?.name;
            const uploadText = document.getElementById('car-image-upload-text');
            const fileNameDiv = document.getElementById('car-image-file-name');

            if (fileName) {
                uploadText.textContent = 'Car image selected:';
                fileNameDiv.textContent = fileName;
                fileNameDiv.style.display = 'block';
            } else {
                uploadText.textContent = 'Click to upload car image';
                fileNameDiv.style.display = 'none';
            }
        });
    }

    calcRent();
});