<!-- Activity Tab -->
<div class="tab-pane" id="activity">
    <div class="profile-section">
        <h2>Account Activity</h2>
        
        <div class="activity-timeline">
            <?php if (empty($recentActivity)): ?>
                <p class="empty-state">No activity recorded</p>
            <?php else: ?>
                <?php foreach ($recentActivity as $activity): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $activity['action']))); ?></strong>
                                <span class="timeline-time"><?php echo timeAgo($activity['created_at']); ?></span>
                            </div>
                            <?php if ($activity['table_name']): ?>
                                <div class="timeline-details">
                                    Table: <?php echo htmlspecialchars($activity['table_name']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="timeline-meta">
                                IP: <?php echo htmlspecialchars($activity['ip_address']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
