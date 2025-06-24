function submitEmail() {
  const email = document.getElementById("email").value;

  fetch("http://localhost/event-management/backend/routes/forgot-password.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email })
  })
  .then(res => res.json())
  .then(data => {
    const msg = document.getElementById("msg");
    if (data.status === "success") {
      localStorage.setItem("reset_email", email);
      msg.style.color = "green";
      msg.innerText = "Email found. Redirecting to reset page...";
      setTimeout(() => {
        window.location.href = "reset-password.html";
      }, 1500);
    } else {
      msg.style.color = "red";
      msg.innerText = data.message;
    }
  });
}
