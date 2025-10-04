<?php
session_start();
include __DIR__ . '/../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee'){
    die("Unauthorized access");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Employee Dashboard</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/style.css" rel="stylesheet">
</head>
<body>
<div class="dashboard">
    <!-- Header -->
    <div class="header">
        <div class="brand">
            <div class="brand-text">
                <h1>Expense Dashboard</h1>
                <div class="subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?></div>
            </div>
        </div>

        <div class="actions">
            <button class="btn btn-primary" onclick="location.href='submit.php'">
                <i class="bi bi-plus-circle"></i> Submit Expense
            </button>

            <button class="btn btn-ghost" onclick="location.href='my_expenses.php'">
                <i class="bi bi-list-check"></i> My Expenses
            </button>

            <div class="profile">
                <div class="avatar" id="avatar">--</div>
                <div>
                    <div style="font-weight:600"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                    <div style="font-size:0.875rem;color:var(--muted)"><?php echo htmlspecialchars($_SESSION['role']); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid">
        <!-- Left Column -->
        <div>
            <!-- Overview Card -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Expense Overview</div>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat">
                        <div>
                            <div class="stat-label">This month spent</div>
                            <div class="stat-value">$420.75</div>
                        </div>
                        <div class="stat-label">12 items</div>
                    </div>

                    <div class="stat">
                        <div>
                            <div class="stat-label">Pending approvals</div>
                            <div class="stat-value">3</div>
                        </div>
                        <div class="stat-label">Awaiting manager</div>
                    </div>
                </div>
            </div>

            <!-- Recent Expenses Card -->
            <div class="card" style="margin-top:1.5rem">
                <div class="card-header">
                    <div>
                        <div class="card-title">Recent Expenses</div>
                        <div class="card-subtitle">Latest 5 submitted</div>
                    </div>
                    <a class="ghost-link" href="my_expenses.php">See all</a>
                </div>

                <div class="list">
                    <?php
                    $stmt = $conn->prepare("SELECT amount, currency, category, description, created_at FROM expenses WHERE employee_id = ? ORDER BY created_at DESC LIMIT 5");
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    
                    if($res && $res->num_rows){
                        while($row = $res->fetch_assoc()){
                            $amt = htmlspecialchars(number_format((float)$row['amount'], 2));
                            $cur = htmlspecialchars($row['currency']);
                            $cat = htmlspecialchars($row['category']);
                            $desc = htmlspecialchars($row['description']);
                            $time = htmlspecialchars($row['created_at'] ?? '');
                            
                            echo "<div class='list-item'>
                                    <div>
                                        <div class='item-main'>{$cat} <span style='color:var(--muted);font-weight:500'>· {$desc}</span></div>
                                        <div class='item-meta'>{$time}</div>
                                    </div>
                                    <div class='item-amount'>${$amt} <span style='color:var(--muted);font-weight:600'>{$cur}</span></div>
                                  </div>";
                        }
                    } else {
                        echo "<div style='padding:1.5rem 0;text-align:center;color:var(--muted)'>No recent expenses found.</div>";
                    }
                    $stmt->close();
                    ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div>
            <!-- Insights Card -->
            <div class="card">
                <div class="stats-grid">
                    <div style="margin-top:1rem">
                        <div class="card-title" style="margin-bottom:0.75rem">Search expenses</div>
                        <div class="search-box">
                            <input type="search" class="search-input" placeholder="Search by category, description..." />
                            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="card" style="margin-top:1.5rem">
                <div class="card-header">
                    <div>
                        <div class="card-title">Quick Actions</div>
                    </div>
                </div>

                <div class="quick-actions">
                    <a href="#" class="action-btn">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Sign out</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Avatar initials
    (function(){
        const name = "<?php echo addslashes($_SESSION['name']); ?>";
        const avatar = document.getElementById('avatar');
        if(name){
            const parts = name.trim().split(' ');
            let initials = parts.slice(0,2).map(p => p.charAt(0).toUpperCase()).join('');
            avatar.textContent = initials || name.charAt(0).toUpperCase();
        }
    })();
</script>
</body>
</html>
