document.getElementById("userLoginForm").addEventListener("submit", async function (e) {
  e.preventDefault();

  const email = document.getElementById("email").value;
  const password = document.getElementById("password").value;

  const response = await fetch("http://localhost/event-management/backend/routes/login.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email, password })
  });

  const result = await response.json();

  const message = document.getElementById("loginMessage");
  if (result.status === "success") {
    message.style.color = "green";
    message.textContent = "Login successful! Redirecting...";

    // Store user ID in localStorage
    localStorage.setItem("user_id", result.user_id);
    localStorage.setItem("user_role", "attendee");
setTimeout(() => {
  window.location.href = "loading.html";
}, 1500);

    
  } else {
    message.style.color = "red";
    message.textContent = result.message;
  }
});
