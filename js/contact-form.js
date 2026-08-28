'use strict';

(function () {
  var form = document.querySelector('#contact-form');
  if (!form) return;

  var submitBtn = form.querySelector('.btn--submit');
  var btnText = submitBtn ? submitBtn.querySelector('.btn-text') : null;
  var successMsg = form.querySelector('.form-message.is-success') || form.querySelector('#form-success');
  var errorMsg = form.querySelector('.form-message.is-error') || form.querySelector('#form-error');

  // ============================================================
  // VALIDATION HELPERS
  // ============================================================
  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function isValidPhone(phone) {
    return /^[0-9\s\+\-\(\)]{7,20}$/.test(phone);
  }

  function showFieldError(field, message) {
    field.classList.add('is-error');
    var errorEl = field.parentElement.querySelector('.field-error');
    if (errorEl) {
      errorEl.textContent = message;
      errorEl.classList.add('is-visible');
    }
  }

  function clearFieldError(field) {
    field.classList.remove('is-error');
    var errorEl = field.parentElement.querySelector('.field-error');
    if (errorEl) {
      errorEl.textContent = '';
      errorEl.classList.remove('is-visible');
    }
  }

  function clearAllErrors() {
    form.querySelectorAll('.is-error').forEach(function (el) {
      el.classList.remove('is-error');
    });
    form.querySelectorAll('.field-error').forEach(function (el) {
      el.textContent = '';
      el.classList.remove('is-visible');
    });
    if (successMsg) successMsg.style.display = 'none';
    if (errorMsg) errorMsg.style.display = 'none';
  }

  // Live clear errors on input
  form.querySelectorAll('.form-input, .form-textarea, .form-select').forEach(function (field) {
    field.addEventListener('input', function () {
      clearFieldError(field);
    });
    field.addEventListener('change', function () {
      clearFieldError(field);
    });
  });

  // ============================================================
  // VALIDATE
  // ============================================================
  function validateForm() {
    var valid = true;

    var nameField = form.querySelector('[name="name"]');
    var emailField = form.querySelector('[name="email"]');
    var phoneField = form.querySelector('[name="phone"]');
    var messageField = form.querySelector('[name="message"]');

    if (nameField && nameField.value.trim().length < 2) {
      showFieldError(nameField, 'Please enter your full name (at least 2 characters).');
      valid = false;
    }

    if (emailField && !isValidEmail(emailField.value.trim())) {
      showFieldError(emailField, 'Please enter a valid email address.');
      valid = false;
    }

    if (phoneField && phoneField.value.trim() && !isValidPhone(phoneField.value.trim())) {
      showFieldError(phoneField, 'Please enter a valid phone number.');
      valid = false;
    }

    if (messageField && messageField.value.trim().length < 20) {
      showFieldError(messageField, 'Please provide more detail (at least 20 characters).');
      valid = false;
    }

    return valid;
  }

  // ============================================================
  // BUTTON STATE
  // ============================================================
  function setLoading(loading) {
    if (!submitBtn) return;
    if (loading) {
      submitBtn.classList.add('is-loading');
      submitBtn.disabled = true;
      if (btnText) btnText.style.opacity = '0';
    } else {
      submitBtn.classList.remove('is-loading');
      submitBtn.disabled = false;
      if (btnText) btnText.style.opacity = '1';
    }
  }

  function setSuccess() {
    if (!submitBtn) return;
    submitBtn.classList.remove('is-loading');
    submitBtn.classList.add('is-success');
    if (btnText) {
      btnText.style.opacity = '1';
      btnText.textContent = 'Message Sent';
    }
    submitBtn.disabled = true;
  }

  function showSuccessMessage() {
    if (successMsg) {
      successMsg.style.display = 'block';
      successMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function showErrorMessage(message) {
    if (errorMsg) {
      errorMsg.textContent = message || 'Something went wrong. Please try again or email us directly.';
      errorMsg.style.display = 'block';
      errorMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  // ============================================================
  // FILE ATTACHMENT VALIDATION
  // ============================================================
  var fileInput = form.querySelector('input[type="file"]');
  var fileSelectedLabel = form.querySelector('.form-file-selected');

  if (fileInput) {
    fileInput.addEventListener('change', function () {
      var file = fileInput.files[0];
      if (!file) return;

      var allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'dwg'];
      var ext = file.name.split('.').pop().toLowerCase();
      var maxSize = 5 * 1024 * 1024; // 5MB

      if (!allowedExts.includes(ext)) {
        showFieldError(fileInput, 'Invalid file type. Please upload PDF, JPG, PNG, or DWG.');
        fileInput.value = '';
        if (fileSelectedLabel) fileSelectedLabel.style.display = 'none';
        return;
      }

      if (file.size > maxSize) {
        showFieldError(fileInput, 'File too large. Maximum size is 5MB.');
        fileInput.value = '';
        if (fileSelectedLabel) fileSelectedLabel.style.display = 'none';
        return;
      }

      clearFieldError(fileInput);
      if (fileSelectedLabel) {
        fileSelectedLabel.textContent = 'Selected: ' + file.name;
        fileSelectedLabel.style.display = 'block';
      }
    });
  }

  // ============================================================
  // CHARACTER COUNT for textarea
  // ============================================================
  var messageField = form.querySelector('[name="message"]');
  var charCount = form.querySelector('.form-char-count');
  var maxChars = 2000;

  if (messageField && charCount) {
    function updateCharCount() {
      var remaining = maxChars - messageField.value.length;
      charCount.textContent = remaining + ' characters remaining';
      charCount.classList.toggle('is-warning', remaining < 200);
      charCount.classList.toggle('is-limit', remaining < 50);
    }

    messageField.addEventListener('input', updateCharCount);
    updateCharCount();
  }

  // ============================================================
  // SUBMIT HANDLER
  // ============================================================
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearAllErrors();

    if (!validateForm()) return;

    setLoading(true);

    var formData = new FormData(form);

    fetch('/api/contact.php', {
      method: 'POST',
      body: formData
    })
      .then(function (res) {
        return res.json().then(function (data) {
          return { status: res.status, data: data };
        });
      })
      .then(function (result) {
        setLoading(false);
        if (result.data && result.data.success) {
          setSuccess();
          showSuccessMessage();
          // Reset form after 3 seconds
          setTimeout(function () {
            form.reset();
            if (fileSelectedLabel) fileSelectedLabel.style.display = 'none';
            if (charCount) charCount.textContent = maxChars + ' characters remaining';
          }, 3000);
        } else {
          var errorText = (result.data && result.data.error)
            ? result.data.error
            : 'Failed to send. Please email sales@pinionadams.co.za directly.';
          showErrorMessage(errorText);
        }
      })
      .catch(function () {
        setLoading(false);
        showErrorMessage('Network error. Please check your connection and try again.');
      });
  });
})();
