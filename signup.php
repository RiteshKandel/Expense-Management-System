<?php include('config/db.php'); ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Sign Up — ExpenseFlow</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    :root{
      --gradient-from: #7F00FF;
      --gradient-to:   #00C6FF;
    }
    .gradient-bg{
      background-image: linear-gradient(110deg, var(--gradient-from), var(--gradient-to));
    }
    .message{
      color: green;
      margin-top: 10px;
      text-align: center;
    }
  </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center text-slate-800">

  <!-- Signup Card -->
  <div class="bg-white w-full max-w-md p-8 rounded-2xl shadow-xl">
    
    <!-- Logo & Title -->
    <div class="text-center mb-6">
      <div class="mx-auto w-12 h-12 rounded-xl gradient-bg flex items-center justify-center text-white mb-3">
        <svg viewBox="0 0 24 24" class="w-6 h-6" fill="none" stroke="white" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 12h7l3 7 6-14"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold">Create Account</h1>
      <p class="text-sm text-slate-500">Join ExpenseFlow today</p>
    </div>

    <!-- Form -->
    <form method="POST" action="">
      <!-- Full Name -->
      <label class="block mb-3">
        <span class="text-sm font-medium">Full Name</span>
        <input type="text" name="name" class="w-full mt-1 px-3 py-2 rounded-md border border-slate-300 focus:ring-2 focus:ring-[var(--gradient-from)] outline-none" placeholder="John Doe" required>
      </label>

      <!-- Organization Name -->
      <label class="block mb-3">
        <span class="text-sm font-medium">Organization Name</span>
        <input type="text" name="organization" class="w-full mt-1 px-3 py-2 rounded-md border border-slate-300 focus:ring-2 focus:ring-[var(--gradient-from)] outline-none" placeholder="Company Inc." required>
      </label>

      <!-- Country -->
      <label class="block mb-3">
        <span class="text-sm font-medium">Country</span>
        <select name="country" class="w-full mt-1 px-3 py-2 rounded-md border border-slate-300 focus:ring-2 focus:ring-[var(--gradient-from)] outline-none" required>
          <option value="" disabled selected>Select your country</option>
          <option>United States</option>
          <option>India</option>
          <option>United Kingdom</option>
          <option>Canada</option>
          <option>Australia</option>
          <option>Germany</option>
          <option>France</option>
          <option>Singapore</option>
          <option>United Arab Emirates</option>
          <option>Other</option>
        </select>
      </label>

      <!-- Email -->
      <label class="block mb-3">
        <span class="text-sm font-medium">Email</span>
        <input type="email" name="email" class="w-full mt-1 px-3 py-2 rounded-md border border-slate-300 focus:ring-2 focus:ring-[var(--gradient-from)] outline-none" placeholder="you@example.com" required>
      </label>

      <!-- Password -->
      <label class="block mb-3">
        <span class="text-sm font-medium">Password</span>
        <input type="password" name="password" class="w-full mt-1 px-3 py-2 rounded-md border border-slate-300 focus:ring-2 focus:ring-[var(--gradient-from)] outline-none" placeholder="••••••••" required>
      </label>

      <!-- Confirm Password -->
      <label class="block mb-4">
        <span class="text-sm font-medium">Confirm Password</span>
        <input type="password" class="w-full mt-1 px-3 py-2 rounded-md border border-slate-300 focus:ring-2 focus:ring-[var(--gradient-from)] outline-none" placeholder="••••••••" required>
      </label>

      <!-- Submit Button -->
      <button type="submit" name="signup" class="w-full py-3 rounded-lg gradient-bg text-white font-semibold hover:opacity-90 transition">Sign Up</button>
      <?php
      if (isset($_POST['signup'])) {
          $name = $_POST['name'];
          $email = $_POST['email'];
          $password = $_POST['password'];
          $role = 'admin'; // Default role for new users
          $country = $_POST['country'];
          $organization = $_POST['organization'];



          $sql = "INSERT INTO users (name, email, password, role, organization, country) VALUES (?, ?, ?, ?, ?, ?)";
          $stmt = $conn->prepare($sql);
          $stmt->bind_param("ssssss", $name, $email, $password, $role,$organization,$country);

          if ($stmt->execute()) {
              echo "<div class='message'>Registered successfully!</div>";
          } else {
              echo "<div class='message error'>Error: " . $stmt->error . "</div>";
          }
      }
      ?>
    </form>

    <!-- Footer -->
    <p class="text-center text-sm text-slate-500 mt-6">
      Already have an account?
      <a href="login.php" class="text-[var(--gradient-from)] font-medium hover:underline">Log In</a>
    </p>
  </div>

</body>
</html>
