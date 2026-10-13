// Password Change functionality
const passwordChangeModal = document.getElementById("passwordChangeModal");
const passwordChangeForm = document.getElementById("passwordChangeForm");
const passwordChangeCancel = document.getElementById("passwordChangeCancel");
const passwordChangeSubmit = passwordChangeForm?.querySelector('[type="submit"]');

function openPasswordChangeModal() {
  const returnFocus = document.getElementById("accountMenuButton") || document.activeElement;
  window.showManagedDialog(
    passwordChangeModal,
    document.getElementById("current-password"),
    returnFocus
  );
}

function closePasswordChangeModal(options = {}) {
  if (passwordChangeModal.getAttribute("aria-busy") === "true" && !options.force) return;
  window.hideManagedDialog(passwordChangeModal);
  passwordChangeModal.removeAttribute("aria-busy");
  passwordChangeForm.reset();
}

if (passwordChangeCancel) {
  passwordChangeCancel.addEventListener("click", closePasswordChangeModal);
}

if (passwordChangeForm) {
  passwordChangeForm.addEventListener("submit", (e) => {
    e.preventDefault();
    
    const currentPassword = document.getElementById("current-password").value;
    const newPassword = document.getElementById("new-password").value;
    const confirmPassword = document.getElementById("confirm-password").value;
    
    if (newPassword !== confirmPassword) {
      showFlashMessage("New passwords do not match!", 'error');
      return;
    }
    
    if (newPassword.length < 6) {
      showFlashMessage("New password must be at least 6 characters long!", 'error');
      return;
    }

    passwordChangeModal.setAttribute("aria-busy", "true");
    passwordChangeSubmit.disabled = true;
    fetch("../api/change-password.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        current_password: currentPassword,
        new_password: newPassword,
        confirm_password: confirmPassword
      }),
    })
    .then(response => response.json())
    .then(result => {
      if (result.success) {
        showFlashMessage(result.message, 'success');
        closePasswordChangeModal({ force: true });
        // Redirect to logout to force re-login
        window.location.href = "logout.php";
      } else {
        showFlashMessage("Error: " + result.message, 'error');
      }
    })
    .catch(error => {
      console.error("Error:", error);
      showFlashMessage("An error occurred while changing the password.", 'error');
    })
    .finally(() => {
      passwordChangeModal.removeAttribute("aria-busy");
      passwordChangeSubmit.disabled = false;
    });
  });
}

// Export functions for use in other modules
window.openPasswordChangeModal = openPasswordChangeModal;
window.closePasswordChangeModal = closePasswordChangeModal;
