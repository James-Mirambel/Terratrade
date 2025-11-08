<!-- Quick Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">📋</div>
        <div class="stat-value"><?php echo $stats['active_listings']; ?></div>
        <div class="stat-label">Active Listings</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-value"><?php echo $stats['offers_made']; ?></div>
        <div class="stat-label">Offers Made</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📨</div>
        <div class="stat-value"><?php echo $stats['offers_received']; ?></div>
        <div class="stat-label">Offers Received</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📄</div>
        <div class="stat-value"><?php echo $stats['active_contracts']; ?></div>
        <div class="stat-label">Active Contracts</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">✓</div>
        <div class="stat-value"><?php echo $stats['completed_transactions']; ?></div>
        <div class="stat-label">Completed</div>
    </div>
</div>
