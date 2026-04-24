if (window.appLoaded) {
    console.log("BLOCKED DUPLICATE LOAD");
    throw new Error("Script already loaded");
}
window.appLoaded = true;console.log("SCRIPT LOADED");
let API = "http://127.0.0.1:8000/api";
let BASE = "http://127.0.0.1:8000";
let token = localStorage.getItem("token") || "";
let editId = null;


let loadingAccounts = false; // ✅ REQUIRED FIX


// 🔥 READ TOKEN + EMAIL FROM URL (SAFE)
const params = new URLSearchParams(window.location.search);

const urlToken = params.get("token");
const urlEmail = params.get("email");

// SAVE ONLY — DO NOT override token variable
if (urlToken) {
    localStorage.setItem("token", urlToken);
    token = urlToken; // ✅ update existing variable (NO let)
}

if (urlEmail) {
    localStorage.setItem("email", urlEmail);
}

// optional debug
console.log("TOKEN:", token);
console.log("EMAIL:", localStorage.getItem("email"));

// ================= REGISTER =================
function register() {
    fetch(API + "/register", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json" // ✅ REQUIRED
        },
        body: JSON.stringify({
            name: document.getElementById("regName").value,
            email: document.getElementById("regEmail").value,
            password: document.getElementById("regPassword").value
        })
    })
    .then(async res => {
        let text = await res.text();

        try {
            return JSON.parse(text);
        } catch {
            console.error("RAW RESPONSE:", text);
            throw new Error("Invalid JSON");
        }
    })
    .then(data => alert(data.message))
    .catch(err => {
        console.error(err);
        alert("Register failed");
    });
}

// ================= LOGIN =================
function login() {
    
    let email = document.getElementById("loginEmail").value;
    let password = document.getElementById("loginPassword").value;

    fetch(API + "/login-link", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
        },
        body: JSON.stringify({
            email: email,
            password: password
        })
    })
    .then(res => res.json())
    .then(data => {

        // ✅ ONLY SAVE IF SUCCESS
        if (data.message && data.message.toLowerCase().includes("check")) {

            localStorage.setItem("email", email);
            console.log("EMAIL SAVED:", localStorage.getItem("email"));

        } else {
            console.warn("Login failed, email not saved");
        }

        alert(data.message);
    });
}
// ================= CREATE ACCOUNT =================
function createAccount() {
    console.log("SAVE CLICKED → editId:", editId);

    let formData = new FormData();
    formData.append("site", document.getElementById("accSite").value);
    formData.append("username", document.getElementById("accUsername").value);

    let password = document.getElementById("accPassword").value;
    if (password) formData.append("password", password);

    let file = document.getElementById("accImage").files[0];
    if (file) formData.append("image", file);

    let url = API + "/accounts";
    let method = "POST";

    // ✅ UPDATE MODE
    if (editId !== null) {
        url = API + "/accounts/update/" + editId;
        method = "POST"; // no override
    }

    fetch(url, {
        method: method,
        headers: {
            "Authorization": "Bearer " + token,
            "Accept": "application/json"
        },
        body: formData
    })
    .then(async res => {
    let text = await res.text();
    console.log("RAW RESPONSE:", text); // 🔥 THIS WILL SHOW THE ERROR

    try {
        return JSON.parse(text);
    } catch {
        throw new Error("Invalid JSON");
    }
})
    .then(data => {
        alert(data.message);

        editId = null;
        loadAccounts();
    });
}

// ================= LOAD ACCOUNTS =================
function loadAccounts() {

    if (loadingAccounts) return;
    loadingAccounts = true;

    fetch(API + "/accounts", {
        headers: { 
            "Authorization": "Bearer " + token,
            "Accept": "application/json"
        }
    })
    .then(res => {
        if (!res.ok) {
            loadingAccounts = false;
            return null;
        }
        return res.json();
    })
    .then(data => {
         console.log("ACCOUNTS DATA:", data);
        if (!data || !data.data) {
            loadingAccounts = false;
            return;
        }

        let table = document.getElementById("accountTable");

        if (!table) {
        console.log("TABLE NOT FOUND");
        loadingAccounts = false;
        return;
    }

    let html = "";

        data.data.forEach(acc => {
            html += `
                <tr>
                    <td>${acc.site}</td>
                    <td>${acc.username}</td>
                    <td>••••••••</td>
                    <td>
                 ${acc.image 
                     ? `<img src="${BASE + '/' + acc.image}" width="60">`
                    : 'No Image'}
                     </td>
                    <td>
                        <button onclick="editAccount(${acc.id}, '${acc.site}', '${acc.username}')">Edit</button>
                        <button onclick="deleteAccount(${acc.id})">Delete</button>
                    </td>
                </tr>
            `;
        });

        table.innerHTML = html;

        loadingAccounts = false;
    })
    .catch(error => {
        console.log("LOAD ACCOUNTS ERROR:", error);
        loadingAccounts = false;
    });
}


// ================= DELETE =================
function deleteAccount(id) {
    if (!confirm("Delete this account?")) return;

    fetch(API + "/accounts/" + id, {
        method: "DELETE",
        headers: { "Authorization": "Bearer " + token,
            "Accept": "application/json"
         }
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        loadAccounts();
    });
}

// ================= UI =================
function showAdd() {
    let form = document.getElementById("addForm");
    form.style.display = form.style.display === "none" ? "block" : "none";
}

// ================= PROFILE UPDATE =================
function updateProfile() {

    let formData = new FormData();
    formData.append("user_id", localStorage.getItem("user_id"));
    formData.append("name", document.getElementById("profileName").value);
    formData.append("email", document.getElementById("profileEmail").value);
    formData.append("password", document.getElementById("profilePassword").value);

    let image = document.getElementById("profileImage").files[0];
    if (image) {
        formData.append("image", image);
    }

    fetch(API + "/profile", {
        method: "POST",
        headers: {
            "Authorization": "Bearer " + localStorage.getItem("token")
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
       loadProfile();
    })
    .catch(err => {
        console.error(err);
        alert("Update failed");
    });
}

// ================= PAGE CONTROL =================
function showLogin() { hideAll(); document.getElementById("loginPage").style.display = "block"; }
function showRegister() { hideAll(); document.getElementById("registerPage").style.display = "block"; }
function showForgot() { hideAll(); document.getElementById("forgotPage").style.display = "block"; }
function showDashboard() { hideAll(); document.getElementById("dashboard").style.display = "block"; }
function showProfile() { hideAll(); document.getElementById("profilePage").style.display = "block"; loadProfile(); }
function backToDashboard() { showDashboard(); }

function hideAll() {
    document.getElementById("loginPage").style.display = "none";
    document.getElementById("registerPage").style.display = "none";
    document.getElementById("forgotPage").style.display = "none";
    document.getElementById("dashboard").style.display = "none";
    document.getElementById("profilePage").style.display = "none";
    document.getElementById("resetPage").style.display = "none";
}
function forgotPassword() {
    let email = document.getElementById("forgotEmail").value;
    if (!email) return alert("Please enter your email");

    fetch(API + "/forgot-password", {
        method: "POST",
        headers: {
    "Content-Type": "application/json",
    "Accept": "application/json"
},
        body: JSON.stringify({ email })
    })
    .then(res => res.json())
    .then(data => alert(data.message));
}

function showReset() {

    // hide everything
    document.getElementById("loginPage").style.display = "none";
    document.getElementById("registerPage").style.display = "none";
    document.getElementById("forgotPage").style.display = "none";
    document.getElementById("dashboard").style.display = "none";
    document.getElementById("profilePage").style.display = "none";

    // show reset page
    document.getElementById("resetPage").style.display = "block";
}

window.onload = function () {

    document.body.style.display = "block";

    const params = new URLSearchParams(window.location.search);

    let loginToken = params.get("token");
    let resetToken = params.get("reset_token");
    let email = params.get("email");

    if (loginToken && email) {

    localStorage.setItem("token", loginToken);
    localStorage.setItem("email", email);

    //  GET USER ID FROM BACKEND
    fetch(API + "/accounts") // temporary trick
    .then(res => res.json())
    .then(data => {

        if (data.data && data.data.length > 0) {
            localStorage.setItem("user_id", data.data[0].user_id);
        }

        showDashboard();
        loadAccounts();
    });

    return;
}

    // RESET PASSWORD (priority)
    if (resetToken && email) {

        document.getElementById("resetToken").value = resetToken;
        document.getElementById("resetEmail").value = email;

        showReset();
        return;
    }

    // LOGIN LINK
    if (loginToken) {

        console.log("LOGIN TOKEN:", loginToken);

        // save token
        localStorage.setItem("token", loginToken);

        // REMOVE TOKEN FROM URL (IMPORTANT)
        window.history.replaceState({}, document.title, "index.html");

        showDashboard();
        loadAccounts();
        return;
    }

    //  DEFAULT
    showLogin();
};

function resetPassword() {

    let email = document.getElementById("resetEmail").value;
    let password = document.getElementById("newPassword").value;
    let confirmPassword = document.getElementById("confirmPassword").value;
    let token = document.getElementById("resetToken").value;

    if (!email || !password || !confirmPassword) {
        alert("All fields are required");
        return;
    }

    if (password !== confirmPassword) {
        alert("Passwords do not match");
        return;
    }

    fetch(API + "/reset-password", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
        },
        body: JSON.stringify({
            email: email,
            password: password,
            password_confirmation: confirmPassword,
            token: token
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);

        // after success → go back to login
        showLogin();
    })
    .catch(err => {
        console.error(err);
        alert("Reset failed");
    });
}

function editAccount(id) {
    fetch(API + "/accounts/" + id)
    .then(res => res.json())
    .then(data => {
        let acc = data.data;

        document.getElementById("accSite").value = acc.site;
        document.getElementById("accUsername").value = acc.username;
        document.getElementById("accPassword").value = acc.password;

        document.getElementById("addForm").style.display = "block";

        editId = id; 
    });
}

function updateAccount() {

    let formData = new FormData();

    formData.append("site", document.getElementById("accSite").value);
    formData.append("username", document.getElementById("accUsername").value);
    formData.append("password", document.getElementById("accPassword").value);

    let image = document.getElementById("accImage").files[0];
    if (image) {
        formData.append("image", image);
    }

    fetch(API + "/accounts/update/" + editId, {
        method: "POST",
        headers: {
            "Authorization": "Bearer " + token,
            "Accept": "application/json"
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);

        editId = null; //  RESET
        loadAccounts();
        document.getElementById("addForm").style.display = "none";
    })
    .catch(err => {
        console.error(err);
        alert("Update failed");
    });
}

function saveAccount() {
    if (editId !== null) {
        updateAccount(); // edit mode
    } else {
        createAccount(); // add mode
    }
}

function logout() {

    let email = localStorage.getItem("email");

    fetch(API + "/logout-link", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ email: email })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);

        // clear local storage
        localStorage.removeItem("token");
        localStorage.removeItem("email");

        showLogin();
    })
    .catch(err => {
        console.error(err);
        alert("Logout failed");
    });
}

function loadProfile() {

    let userId = localStorage.getItem("user_id");

    fetch(API + "/user/" + userId)
    .then(res => res.json())
    .then(data => {

        document.getElementById("profileName").value = data.name;
        document.getElementById("profileEmail").value = data.email;

        document.getElementById("displayName").innerText = data.name;
        document.getElementById("displayEmail").innerText = data.email;

        if (data.image) {
            document.getElementById("profilePreview").src = BASE + "/" + data.image;
        }

    })
    .catch(err => {
        console.error(err);
        alert("Failed to load profile");
    });
}