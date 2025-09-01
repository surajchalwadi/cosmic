 <?php
// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['user']['role'] ?? '';
$name = $_SESSION['user']['name'] ?? 'User';
?>

<!-- Enhanced Topbar with Global Search -->
<div class="topbar">
    <div class="d-flex align-items-center">
        <button class="btn btn-link d-md-none me-2" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <h5 class="mb-0">Welcome, <?= ucfirst($role) ?> - <?= htmlspecialchars($name) ?></h5>
    </div>
    
    <div class="d-flex align-items-center gap-3">
        <!-- Global Search -->
        <?php include 'components/global_search.php'; ?>
        
        <!-- User Actions -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-user"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</div>

<style>
.topbar {
    background: white;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

@media (max-width: 768px) {
    .topbar {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .topbar > div {
        justify-content: center;
    }
}
</style>