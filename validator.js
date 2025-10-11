

class FormValidator {
    constructor(formId) {
        this.form = document.getElementById(formId);
        this.errors = {};
        this.init();
    }

    init() {
        if (!this.form) return;
        
      
        this.form.addEventListener('input', (e) => {
            this.validateField(e.target);
        });

        this.form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                this.showErrors();
            }
        });
    }

    validateField(field) {
        const fieldName = field.name || field.id;
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        delete this.errors[fieldName];

        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'Field ini wajib diisi';
        }

        // Email validation
        if (field.type === 'email' && value) {
            if (!this.isValidEmail(value)) {
                isValid = false;
                errorMessage = 'Format email tidak valid';
            }
        }

        // Password validation
        if (field.type === 'password' && value) {
            if (value.length < 8) {
                isValid = false;
                errorMessage = 'Password minimal 8 karakter';
            }
        }

        // Password confirmation validation
        if (field.id === 'konfirmasiPassword' && value) {
            const passwordField = this.form.querySelector('#password');
            if (passwordField && value !== passwordField.value) {
                isValid = false;
                errorMessage = 'Konfirmasi password tidak sama';
            }
        }

        // Phone number validation - RELAXED (minimum 10 digits)
        if (field.type === 'tel' || field.id === 'nomorAkun') {
            if (value && !this.isValidPhone(value)) {
                isValid = false;
                errorMessage = 'Nomor minimal 10 digit';
            }
        }

        // Date validation
        if (field.type === 'date' && value) {
            if (!this.isValidDate(value)) {
                isValid = false;
                errorMessage = 'Format tanggal tidak valid';
            }
            
            // Check-in date should not be in the past
            if (field.id === 'tanggalCheckin') {
                const today = new Date();
                const selectedDate = new Date(value);
                if (selectedDate < today.setHours(0,0,0,0)) {
                    isValid = false;
                    errorMessage = 'Tanggal check-in tidak boleh di masa lalu';
                }
            }
            
            // Check-out date should be after check-in
            if (field.id === 'tanggalCheckout') {
                const checkinField = this.form.querySelector('#tanggalCheckin');
                if (checkinField && checkinField.value) {
                    const checkinDate = new Date(checkinField.value);
                    const checkoutDate = new Date(value);
                    if (checkoutDate <= checkinDate) {
                        isValid = false;
                        errorMessage = 'Tanggal check-out harus setelah check-in';
                    }
                }
            }
        }

        // Number validation
        if (field.type === 'number' && value) {
            const num = parseInt(value);
            if (isNaN(num) || num < 1) {
                isValid = false;
                errorMessage = 'Harus berupa angka positif';
            }
            
            // Room quantity validation
            if (field.id === 'jumlahKamar') {
                const maxStock = field.getAttribute('data-max-stock');
                if (maxStock && num > parseInt(maxStock)) {
                    isValid = false;
                    errorMessage = `Maksimal ${maxStock} kamar tersedia`;
                }
            }
        }

        // Update field appearance
        this.updateFieldAppearance(field, isValid);

        // Store error if invalid
        if (!isValid) {
            this.errors[fieldName] = errorMessage;
        }

        return isValid;
    }

    validateForm() {
        this.errors = {};
        let isFormValid = true;

        // Validate all form fields
        const fields = this.form.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isFormValid = false;
            }
        });

        // Additional form-specific validations
        if (this.form.id === 'bookingForm') {
            isFormValid = this.validateBookingForm() && isFormValid;
        }

        if (this.form.id === 'paymentForm') {
            isFormValid = this.validatePaymentForm() && isFormValid;
        }

        return isFormValid;
    }

    validateBookingForm() {
        let isValid = true;

        // Validate date range
        const checkinField = this.form.querySelector('#tanggalCheckin');
        const checkoutField = this.form.querySelector('#tanggalCheckout');
        
        if (checkinField && checkoutField && checkinField.value && checkoutField.value) {
            const checkin = new Date(checkinField.value);
            const checkout = new Date(checkoutField.value);
            const daysDiff = Math.ceil((checkout - checkin) / (1000 * 60 * 60 * 24));
            
            if (daysDiff < 1) {
                this.errors['tanggalCheckout'] = 'Minimal menginap 1 malam';
                isValid = false;
            }
            
            if (daysDiff > 30) {
                this.errors['tanggalCheckout'] = 'Maksimal menginap 30 malam';
                isValid = false;
            }
        }

        // Validate guest count
        const roomField = this.form.querySelector('#jumlahKamar');
        const guestField = this.form.querySelector('#jumlahOrang');
        
        if (roomField && guestField && roomField.value && guestField.value) {
            const rooms = parseInt(roomField.value);
            const guests = parseInt(guestField.value);
            
            if (guests > rooms * 4) {
                this.errors['jumlahOrang'] = 'Maksimal 4 orang per kamar';
                isValid = false;
            }
        }

        return isValid;
    }

    validatePaymentForm() {
        let isValid = true;

        // Validate payment method selection
        const selectedMethod = this.form.querySelector('input[name="metode"]:checked');
        if (!selectedMethod) {
            this.errors['metode'] = 'Pilih metode pembayaran';
            isValid = false;
        }

        // Relaxed account number validation - just check minimum length
        const accountField = this.form.querySelector('#nomorAkun');
        if (accountField && accountField.value) {
            const accountNumber = accountField.value.replace(/\D/g, ''); // Remove non-digits
            
            if (accountNumber.length < 10) {
                this.errors['nomorAkun'] = 'Nomor akun minimal 10 digit';
                isValid = false;
            }
        }

        return isValid;
    }

    updateFieldAppearance(field, isValid) {
        // Remove existing validation classes
        field.classList.remove('is-valid', 'is-invalid');
        
        // Add appropriate class if field has value
        if (field.value.trim()) {
            field.classList.add(isValid ? 'is-valid' : 'is-invalid');
        }

        // Update feedback message
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.textContent = this.errors[field.name || field.id] || '';
        }
    }

    showErrors() {
        // Scroll to first error field
        const firstErrorField = this.form.querySelector('.is-invalid');
        if (firstErrorField) {
            firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstErrorField.focus();
        }

        // Show error summary
        const errorSummary = Object.values(this.errors);
        if (errorSummary.length > 0) {
            this.showAlert(errorSummary[0], 'danger');
        }
    }

    showAlert(message, type = 'info') {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'danger' ? 'exclamation-triangle' : 'info-circle'}"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Insert at top of form
        this.form.insertBefore(alertDiv, this.form.firstChild);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }

    // Utility validation methods
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    isValidPhone(phone) {
        // Relaxed phone validation - just check minimum 10 digits
        const phoneDigits = phone.replace(/\D/g, '');
        return phoneDigits.length >= 10;
    }

    isValidDate(dateString) {
        const date = new Date(dateString);
        return date instanceof Date && !isNaN(date);
    }

    // Public method to add custom validation
    addCustomValidation(fieldName, validationFn, errorMessage) {
        const field = this.form.querySelector(`[name="${fieldName}"], #${fieldName}`);
        if (field) {
            field.addEventListener('input', () => {
                if (!validationFn(field.value)) {
                    this.errors[fieldName] = errorMessage;
                    this.updateFieldAppearance(field, false);
                } else {
                    delete this.errors[fieldName];
                    this.updateFieldAppearance(field, true);
                }
            });
        }
    }
}

// Initialize validators when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize validators for different forms
    const forms = ['registerForm', 'loginForm', 'bookingForm', 'paymentForm', 'hotelForm', 'userForm'];
    
    forms.forEach(formId => {
        if (document.getElementById(formId)) {
            new FormValidator(formId);
        }
    });

    // Add real-time formatting for phone numbers (relaxed)
    const phoneFields = document.querySelectorAll('input[type="tel"], #nomorAkun');
    phoneFields.forEach(field => {
        field.addEventListener('input', function(e) {
            // Allow any characters, just format nicely
            let value = e.target.value;
            
            // Remove extra spaces
            value = value.replace(/\s+/g, ' ').trim();
            
            e.target.value = value;
        });
    });

    // Add real-time price calculation
    const priceCalculationFields = ['tanggalCheckin', 'tanggalCheckout', 'jumlahKamar'];
    priceCalculationFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('change', calculateTotalPrice);
        }
    });
});

// Real-time price calculation function
function calculateTotalPrice() {
    const checkinField = document.getElementById('tanggalCheckin');
    const checkoutField = document.getElementById('tanggalCheckout');
    const roomField = document.getElementById('jumlahKamar');
    const pricePerNightField = document.getElementById('hargaPerMalam');
    const totalDisplay = document.getElementById('totalHarga');
    const totalHidden = document.getElementById('totalHargaHidden');

    if (checkinField && checkoutField && roomField && pricePerNightField && totalDisplay) {
        const checkin = checkinField.value;
        const checkout = checkoutField.value;
        const rooms = parseInt(roomField.value) || 0;
        const pricePerNight = parseInt(pricePerNightField.value) || 0;

        if (checkin && checkout && rooms > 0) {
            const checkinDate = new Date(checkin);
            const checkoutDate = new Date(checkout);
            const nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));

            if (nights > 0) {
                const total = nights * rooms * pricePerNight;
                totalDisplay.textContent = formatRupiah(total);
                
                if (totalHidden) {
                    totalHidden.value = total;
                }

                // Update nights display
                const nightsDisplay = document.getElementById('jumlahMalam');
                if (nightsDisplay) {
                    nightsDisplay.textContent = nights + ' malam';
                }
            }
        }
    }
}
function formatRupiah(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}