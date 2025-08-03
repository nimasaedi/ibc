<div class="ibetcoin-last-wins">
    <h3><?php _e('Recent Wins', 'ibetcoin'); ?></h3>
    
    <?php if ($wins) : ?>
    <div class="wins-grid">
        <?php foreach ($wins as $win) : ?>
        <div class="win-card">
            <div class="win-user"><?php echo substr($win->user_login, 0, 3) . '...'; ?></div>
            <div class="win-amount">+<?php echo number_format($win->profit, 2); ?> USDT</div>
            <div class="win-time"><?php echo human_time_diff(strtotime($win->updated_at), current_time('timestamp')); ?> ago</div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <p><?php _e('No wins yet', 'ibetcoin'); ?></p>
    <?php endif; ?>
</div>