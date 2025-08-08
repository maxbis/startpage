// Password Change functionality
const changePasswordLink = document.getElementById("changePasswordLink");
const passwordChangeModal = document.getElementById("passwordChangeModal");
const passwordChangeForm = document.getElementById("passwordChangeForm");
const passwordChangeCancel = document.getElementById("passwordChangeCancel");

function openPasswordChangeModal() {
  passwordChangeModal.classList.remove("hidden");
  passwordChangeModal.classList.add("flex");
  document.getElementById("current-password").focus();
}

function closePasswordChangeModal() {
  passwordChangeModal.classList.add("hidden");
  passwordChangeModal.classList.remove("flex");
  passwordChangeForm.reset();
}

if (changePasswordLink) {
  changePasswordLink.addEventListener("click", (e) => {
    e.preventDefault();
    openPasswordChangeModal();
  });
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
        closePasswordChangeModal();
        // Redirect to logout to force re-login
        window.location.href = "logout.php";
      } else {
        showFlashMessage("Error: " + result.message, 'error');
      }
    })
    .catch(error => {
      console.error("Error:", error);
      showFlashMessage("An error occurred while changing the password.", 'error');
    });
  });
}

// Export functions for use in other modules
window.openPasswordChangeModal = openPasswordChangeModal;
window.closePasswordChangeModal = closePasswordChangeModal;
