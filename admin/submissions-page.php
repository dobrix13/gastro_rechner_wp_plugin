<div class="wrap">
    <h1>Gastro Rechner Submissions</h1>
    
    <!-- Export Form -->
    <form method="post" action="">
        <?php wp_nonce_field('gastro_export_submissions_nonce'); ?>
        <input type="hidden" name="export_submissions" value="1">
        <p>
            <button type="submit" class="button button-primary">Export to CSV</button>
        </p>
    </form>
    
    <!-- Submissions Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Total Sales (€)</th>
                <th>Cash Sales (€)</th>
                <th>Team Tip (€)</th>
                <th>Flow Cash Received</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($submissions)): ?>
                <tr>
                    <td colspan="7">No submissions found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <td><?php echo esc_html($submission->id); ?></td>
                        <td><?php echo esc_html($submission->name); ?></td>
                        <td><?php echo number_format($submission->total_sales, 2); ?> €</td>
                        <td><?php echo number_format($submission->sales_cash, 2); ?> €</td>
                        <td><?php echo number_format($submission->team_tip, 2); ?> €</td>
                        <td><?php echo $submission->flow_cash_received ? 'Yes' : 'No'; ?></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($submission->timestamp)); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="pagination-links">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>