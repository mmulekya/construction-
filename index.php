<?php require_once "includes/security.php";

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

$loggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>BuildSmart AI - Smart Construction Assistant</title>

<!-- Google verification -->
<meta name="google-site-verification" content="HUfw3qi4KakAG6Qrp-yRDWQKsPTvrNIqOvHuah6JV2U" />

<!-- SEO -->
<meta name="description" content="BuildSmart AI - AI-powered construction assistant for engineers and builders.">
<meta name="keywords" content="construction AI, engineering AI, building assistant">
<meta name="author" content="BuildSmart AI">

<!-- Open Graph -->
<meta property="og:title" content="BuildSmart AI">
<meta property="og:description" content="AI-powered construction assistant platform">
<meta property="og:type" content="website">
<meta property="og:url" content="https://buildsmart.wuaze.com">
<meta property="og:image" content="https://buildsmart.wuaze.com/assets/logo.png">

<meta name="theme-color" content="#007bff">

<!-- Favicon -->
<link rel="icon" href="favicon.ico" type="image/x-icon">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary: #007bff;
    --secondary: #28a745;
    --light: #f5f7fa;
    --dark-bg: #181818;
    --dark-text: #eee;
}
* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'Inter', sans-serif;
    background: var(--light);
    color: #333;
    line-height:1.6;
}

/* HEADER */
header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:20px 40px;
    background:white;
    box-shadow:0 2px 6px rgba(0,0,0,0.05);
}

header h1 {
    color: var(--primary);
    font-size:24px;
    font-weight:700;
}

header img {
    height:30px;
    vertical-align:middle;
    margin-right:10px;
}

header nav button {
    margin-left:10px;
    padding:8px 18px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    background: var(--primary);
    color:white;
}

/* HERO */
.hero {
    text-align:center;
    padding:100px 20px;
    background:linear-gradient(135deg,#007bff,#28a745);
    color:white;
}

.hero h2 { font-size:36px; margin-bottom:20px; }
.hero p { font-size:18px; margin-bottom:30px; }

.hero button {
    padding:12px 30px;
    font-size:18px;
    border:none;
    border-radius:6px;
    background:white;
    color:var(--primary);
}

/* FEATURES */
.features {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:30px;
    padding:60px 20px;
}

.feature {
    background:white;
    padding:20px;
    border-radius:10px;
    text-align:center;
}

/* CHAT */
.chat-container {
    max-width:800px;
    margin:20px auto;
}

.message {
    padding:10px;
    border-radius:20px;
    margin:5px;
}

.message.user { background:#007bff; color:white; }
.message.ai { background:#e4e6eb; }

/* INPUT */
.input-bar {
    display:flex;
    margin:10px;
}

.input-bar input {
    flex:1;
    padding:10px;
}

.input-bar button {
    padding:10px;
}

/* FOOTER */
footer {
    background:#333;
    color:white;
    text-align:center;
    padding:20px;
}
</style>

</head>

<body>

<header>
    <h1>
        <img src="assets/logo.png">
        BuildSmart AI
    </h1>

    <nav>
        <button onclick="location.href='index.php'">Home</button>
        <button onclick="location.href='about.php'">About</button>
        <button onclick="location.href='contact.php'">Contact</button>

        <?php if($loggedIn): ?>
            <button onclick="logout()" style="background:red;">Logout</button>
        <?php else: ?>
            <button onclick="location.href='login.php'">Login</button>
        <?php endif; ?>
    </nav>
</header>

<section class="hero">
    <h2>Smart Construction Assistant</h2>
    <p>AI-powered assistant for building and project planning.</p>

    <?php if(!$loggedIn): ?>
        <button onclick="location.href='login.php'">Get Started</button>
    <?php endif; ?>
</section>

<section class="features">
    <div class="feature">
        <h3>AI Chat</h3>
        <p>Ask construction questions instantly.</p>
    </div>

    <div class="feature">
        <h3>Project Guidance</h3>
        <p>Step-by-step building support.</p>
    </div>

    <div class="feature">
        <h3>Secure Platform</h3>
        <p>Protected with OTP & secure login.</p>
    </div>
</section>

<?php if($loggedIn): ?>
<div class="chat-container" id="chat-box"></div>

<div class="input-bar">
    <input type="text" id="question" placeholder="Ask something...">
    <button onclick="sendQuestion()">Send</button>
</div>

<input type="hidden" id="csrf_token">
<?php endif; ?>

<footer>
    © 2026 BuildSmart AI |
    <a href="privacy.php" style="color:white;">Privacy</a>
</footer>

</body>
</html>