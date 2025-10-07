

document.addEventListener('DOMContentLoaded', function() {

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });


    initAnimations();
    

    initSearch();
    
    
    initPaymentMethods();
    
    
    initDateValidation();
    

    initDynamicContent();
});


function initAnimations() {

    const cards = document.querySelectorAll('.card, .hotel-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    });

    cards.forEach(card => {
        observer.observe(card);
    });
}


function initSearch() {
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
    }

    // Real-time search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 500);
        });
    }
}

function performSearch() {
    const searchTerm = document.getElementById('searchInput')?.value || '';
    const location = document.getElementById('locationFilter')?.value || '';
    const minPrice = document.getElementById('minPrice')?.value || 0;
    const maxPrice = document.getElementById('maxPrice')?.value || 999999999;

    // Show loading spinner
    showLoading();

    // Fetch search results
    fetch(`api/hotel.php?search=${encodeURIComponent(searchTerm)}&location=${encodeURIComponent(location)}&min_price=${minPrice}&max_price=${maxPrice}`)
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data);
            hideLoading();
        })
        .catch(error => {
            console.error('Search error:', error);
            hideLoading();
            showAlert('Terjadi kesalahan saat mencari hotel', 'danger');
        });
}

function displaySearchResults(hotels) {
    const container = document.getElementById('hotelContainer');
    if (!container) return;

    if (hotels.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-warning text-center">
                    <i class="fas fa-search"></i>
                    <h4>Tidak ada hotel yang ditemukan</h4>
                    <p>Coba ubah kriteria pencarian Anda</p>
                </div>
            </div>
        `;
        return;
    }

    let html = '';
    hotels.forEach(hotel => {
        html += createHotelCard(hotel);
    });

    container.innerHTML = html;
}

function createHotelCard(hotel) {
    const stockWarning = hotel.stok_kamar <= 3 && hotel.stok_kamar > 0 ? 
        `<div class="stock-warning"><i class="fas fa-exclamation-triangle"></i> Hanya tersisa ${hotel.stok_kamar} kamar!</div>` : '';
    
    const stockEmpty = hotel.stok_kamar === 0 ? 
        `<div class="stock-empty"><i class="fas fa-times-circle"></i> Kamar tidak tersedia</div>` : '';

    return `
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="hotel-card">
                <div class="hotel-image" style="background-image: url('${hotel.foto || 'https://via.placeholder.com/400x200?text=Hotel+Image'}')">
                    <div class="hotel-price">${formatRupiah(hotel.harga_per_malam)}/malam</div>
                </div>
                <div class="hotel-info">
                    <h3 class="hotel-name">${hotel.nama_hotel}</h3>
                    <p class="hotel-location">
                        <i class="fas fa-map-marker-alt"></i>
                        ${hotel.lokasi}
                    </p>
                    <p class="hotel-description">${hotel.deskripsi}</p>
                    ${stockWarning}
                    ${stockEmpty}
                    <div class="d-grid">
                        <a href="deskripsi.php?id=${hotel.id_hotel}" class="btn btn-primary ${hotel.stok_kamar === 0 ? 'disabled' : ''}">
                            ${hotel.stok_kamar === 0 ? 'Tidak Tersedia' : 'Lihat Detail'}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Payment Methods
function initPaymentMethods() {
    const paymentMethods = document.querySelectorAll('.payment-method');
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            // Remove active class from all methods
            paymentMethods.forEach(m => m.classList.remove('active'));
            
            // Add active class to clicked method
            this.classList.add('active');
            
            // Update hidden input
            const methodValue = this.dataset.method;
            const hiddenInput = document.getElementById('selectedPaymentMethod');
            if (hiddenInput) {
                hiddenInput.value = methodValue;
            }
            
            // Update account number placeholder
            const accountInput = document.getElementById('nomorAkun');
            if (accountInput) {
                accountInput.placeholder = `Masukkan nomor ${methodValue}`;
            }
        });
    });
}

// Date Validation
function initDateValidation() {
    const checkinDate = document.getElementById('tanggalCheckin');
    const checkoutDate = document.getElementById('tanggalCheckout');

    if (checkinDate && checkoutDate) {
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        checkinDate.min = today;
        checkoutDate.min = today;

        checkinDate.addEventListener('change', function() {
            // Set checkout minimum date to checkin date + 1 day
            const checkinValue = new Date(this.value);
            checkinValue.setDate(checkinValue.getDate() + 1);
            checkoutDate.min = checkinValue.toISOString().split('T')[0];
            
            // Clear checkout if it's before new minimum
            if (checkoutDate.value && new Date(checkoutDate.value) <= new Date(this.value)) {
                checkoutDate.value = '';
            }
            
            calculateTotalPrice();
        });

        checkoutDate.addEventListener('change', function() {
            calculateTotalPrice();
        });
    }
}

function calculateTotalPrice() {
    const checkinDate = document.getElementById('tanggalCheckin');
    const checkoutDate = document.getElementById('tanggalCheckout');
    const jumlahKamar = document.getElementById('jumlahKamar');
    const pricePerNight = document.getElementById('hargaPerMalam');
    const totalPriceElement = document.getElementById('totalHarga');

    if (checkinDate && checkoutDate && jumlahKamar && pricePerNight && totalPriceElement) {
        if (checkinDate.value && checkoutDate.value && jumlahKamar.value) {
            const checkin = new Date(checkinDate.value);
            const checkout = new Date(checkoutDate.value);
            const days = Math.ceil((checkout - checkin) / (1000 * 60 * 60 * 24));
            const rooms = parseInt(jumlahKamar.value);
            const pricePerNightValue = parseInt(pricePerNight.value);
            
            if (days > 0) {
                const total = days * rooms * pricePerNightValue;
                totalPriceElement.textContent = formatRupiah(total);
                
                // Update hidden input for form submission
                const hiddenTotal = document.getElementById('totalHargaHidden');
                if (hiddenTotal) {
                    hiddenTotal.value = total;
                }
            }
        }
    }
}

// Dynamic Content Loading
function initDynamicContent() {
    // Load user bookings if on user dashboard
    if (document.getElementById('userBookings')) {
        loadUserBookings();
    }
    
    // Load admin statistics if on admin dashboard
    if (document.getElementById('adminStats')) {
        loadAdminStats();
    }
}

function loadUserBookings(status = '') {
    fetch(`api/pemesanan.php?status=${status}`)
        .then(response => response.json())
        .then(data => {
            displayUserBookings(data);
        })
        .catch(error => {
            console.error('Error loading bookings:', error);
        });
}

function displayUserBookings(bookings) {
    const container = document.getElementById('userBookings');
    if (!container) return;

    if (bookings.length === 0) {
        container.innerHTML = `
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle"></i>
                <h4>Belum ada riwayat pemesanan</h4>
                <p>Mulai jelajahi dan pesan hotel impian Anda!</p>
            </div>
        `;
        return;
    }

    let html = `
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Hotel</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Kamar</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
    `;

    bookings.forEach(booking => {
        const statusClass = booking.status === 'berhasil' ? 'success' : 
                           booking.status === 'pending' ? 'warning' : 'danger';
        
        html += `
            <tr>
                <td>
                    <strong>${booking.nama_hotel}</strong><br>
                    <small class="text-muted">${booking.lokasi}</small>
                </td>
                <td>${formatDate(booking.tanggal_checkin)}</td>
                <td>${formatDate(booking.tanggal_checkout)}</td>
                <td>${booking.jumlah_kamar} kamar, ${booking.jumlah_orang} orang</td>
                <td>${formatRupiah(booking.total_harga)}</td>
                <td><span class="badge bg-${statusClass}">${booking.status}</span></td>
                <td>
                    ${booking.status === 'berhasil' ? 
                        `<button class="btn btn-sm btn-primary" onclick="printBooking('${booking.kode_booking}')">
                            <i class="fas fa-print"></i> Cetak
                        </button>` : 
                        `<span class="text-muted">-</span>`
                    }
                </td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    container.innerHTML = html;
}

function loadAdminStats() {
    fetch('api/laporan.php?type=stats')
        .then(response => response.json())
        .then(data => {
            updateAdminStats(data);
        })
        .catch(error => {
            console.error('Error loading admin stats:', error);
        });
}

function updateAdminStats(stats) {
    // Update stat cards
    const elements = {
        'totalUsers': stats.total_users,
        'totalHotels': stats.total_hotels,
        'totalBookings': stats.total_bookings,
        'totalRevenue': formatRupiah(stats.total_revenue)
    };

    Object.keys(elements).forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = elements[id];
        }
    });
}

// Utility Functions
function formatRupiah(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID');
}

function showLoading() {
    const loadingElement = document.getElementById('loading');
    if (loadingElement) {
        loadingElement.style.display = 'block';
    }
}

function hideLoading() {
    const loadingElement = document.getElementById('loading');
    if (loadingElement) {
        loadingElement.style.display = 'none';
    }
}

function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer');
    if (alertContainer) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        alertContainer.appendChild(alert);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 5000);
    }
}

function printBooking(bookingCode) {
    window.open(`print_booking.php?code=${bookingCode}`, '_blank');
}

// Form Validation Helpers
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[0-9]{10,15}$/;
    return re.test(phone);
}

function validatePassword(password) {
    return password.length >= 8;
}


document.addEventListener('input', function(e) {
    if (e.target.type === 'email') {
        const isValid = validateEmail(e.target.value);
        toggleFieldValidation(e.target, isValid);
    }
    
    if (e.target.id === 'konfirmasiPassword') {
        const password = document.getElementById('password');
        const isValid = password && e.target.value === password.value;
        toggleFieldValidation(e.target, isValid);
    }
    
    if (e.target.type === 'tel' || e.target.id === 'nomorAkun') {
        const isValid = validatePhone(e.target.value);
        toggleFieldValidation(e.target, isValid);
    }
});

function toggleFieldValidation(field, isValid) {
    field.classList.remove('is-valid', 'is-invalid');
    if (field.value) {
        field.classList.add(isValid ? 'is-valid' : 'is-invalid');
    }
}


function submitFormAjax(formId, successCallback) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (successCallback) {
                    successCallback(data);
                } else {
                    showAlert(data.message, 'success');
                }
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Form submission error:', error);
            showAlert('Terjadi kesalahan. Silakan coba lagi.', 'danger');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
}