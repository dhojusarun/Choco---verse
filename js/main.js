// Form Validation and Interaction Scripts

// Show alert message
function showAlert(message, type = 'error') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} show`;
    alertDiv.textContent = message;
    
    const form = document.querySelector('.auth-form');
    form.insertBefore(alertDiv, form.firstChild);
    
    setTimeout(() => {
        alertDiv.classList.remove('show');
        setTimeout(() => alertDiv.remove(), 300);
    }, 5000);
}

// Validate email format
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Validate password strength
function validatePassword(password) {
    return password.length >= 6;
}

// Handle form submission with AJAX
function handleFormSubmit(form, endpoint, redirectUrl) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<div class="spinner"></div>';
        
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 1500);
            } else {
                showAlert(data.message, 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        } catch (error) {
            showAlert('An error occurred. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
}

// Real-time password confirmation validation
function setupPasswordConfirmation() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (password && confirmPassword) {
        confirmPassword.addEventListener('input', () => {
            if (confirmPassword.value !== password.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });
    }
}

// Add smooth scroll behavior
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Initialize password confirmation on page load
document.addEventListener('DOMContentLoaded', () => {
    setupPasswordConfirmation();
    
    // Add input animation effects
    const inputs = document.querySelectorAll('.form-group input');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', () => {
            input.parentElement.style.transform = 'scale(1)';
        });
    });
});

// Password visibility toggle (optional enhancement)
function togglePasswordVisibility(inputId, toggleBtn) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        toggleBtn.textContent = '👁️';
    } else {
        input.type = 'password';
        toggleBtn.textContent = '👁️‍🗨️';
    }
}
