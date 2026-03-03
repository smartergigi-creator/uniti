/* =========================
   TOKEN HELPER
========================= */

const TOKEN_KEY = "jwt_token";

function setToken(token) {
    localStorage.setItem(TOKEN_KEY, token);
}

function getToken() {
    return (
        localStorage.getItem(TOKEN_KEY) ||
        getCookie("jwt_token")
    );
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null;
}

function clearToken() {
    localStorage.removeItem(TOKEN_KEY);
}

/* =========================
   LOGIN
========================= */

function login() {
    let email = document.getElementById("email").value;
    let password = document.getElementById("password").value;

    fetch("/web-login", {
        method: "POST",
        credentials: "same-origin", // ADD THIS
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        body: JSON.stringify({ email, password }),
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.access_token) {
                // Optional: keep in localStorage
                setToken(data.access_token);

                // ❌ DO NOT set cookie here

                window.location.replace("/dashboard");
            } else {
                document.getElementById("error").innerText =
                    data.message || "Invalid login";
            }
        })
        .catch(() => {
            document.getElementById("error").innerText = "Server error";
        });
}
/* =========================
   SIGNUP
========================= */

/* =========================
   SIGNUP
========================= */

function signup() {
    let fname = document.getElementById("fname").value.trim();
    let lname = document.getElementById("lname").value.trim();
    let email = document.getElementById("email").value.trim();
    let password = document.getElementById("password").value.trim();

    if (!fname || !lname || !email || !password) {
        alert("All fields are required");
        return;
    }

    let name = fname + " " + lname;

    fetch("/api/register", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
        },
        body: JSON.stringify({
            name: name,
            email: email,
            password: password,
        }),
    })
        .then(async (res) => {
            // If validation error (user already exists)
            if (!res.ok) {
                let errData = await res.json();
                throw errData;
            }

            return res.json();
        })
        .then((data) => {
            // ✅ New user → Auto login → Dashboard
            if (data.status === true && data.access_token) {
                // optional: save token
                localStorage.setItem("jwt_token", data.access_token);

                window.location.href = "/dashboard";
            } else {
                alert(data.message || "Signup failed");
            }
        })
        .catch((err) => {
            console.error(err);

            // ❌ Already registered user
            if (err?.errors?.email) {
                alert("Email already registered. Please login.");
                window.location.href = "/login";
            } else {
                alert("Signup failed. Try again.");
            }
        });
}

/* =========================
   LOGOUT
========================= */

function logout() {
    fetch("/web-logout", {
        method: "POST",
        credentials: "same-origin",
    }).then(() => {
        localStorage.clear();

        // force reload fresh page
        window.location.href = "/login";
    });
}
