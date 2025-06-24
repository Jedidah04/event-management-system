function resetPassword() {
  const newPassword = document.getElementById("newPassword").value;
  const email = localStorage.getItem("reset_email");

  fetch("http://localhost/event-management/backend/routes/reset-password.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email, password: newPassword })
  })
  .then(res => res.json())
  .then(data => {
    const msg = document.getElementById("msg");
    if (data.status === "success") {
      msg.style.color = "green";
      msg.innerText = "Password reset successful! Redirecting to login...";
      setTimeout(() => {
        window.location.href = "user-login.html";
      }, 1500);
    } else {
      msg.style.color = "red";
      msg.innerText = data.message;
    }
  });
}
