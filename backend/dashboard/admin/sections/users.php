<?php
// Users management section (simple, safe view)
// This file is included only after admin authentication in index.php
if (!defined('APP_INIT')) {
    // lightweight guard if desired; the bootstrap include usually handles this
}
?>
<section class="content-section active" id="users-section">
    <div class="section-header">
        <h2>Users</h2>
        <p>Manage system users</p>
    </div>

    <div class="card">
        <table class="data-table">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php
                // Minimal server-side safe listing — replace with DB-backed data
                $db =
                    (function(){
                        if (class_exists('App\\Classes\\Database')) {
                            return App\Classes\Database::getInstance();
                        }
                        return null;
                    })();

                if ($db) {
                    $users = $db->fetchAll('SELECT id, name, email, role FROM users ORDER BY id DESC LIMIT 50');
                    foreach ($users as $u) {
                        echo '<tr>';
                        echo '<td>'.htmlspecialchars($u['id']).'</td>';
                        echo '<td>'.htmlspecialchars($u['name']).'</td>';
                        echo '<td>'.htmlspecialchars($u['email']).'</td>';
                        echo '<td>'.htmlspecialchars($u['role']).'</td>';
                        echo '<td><a href="?section=users&action=edit&id='.urlencode($u['id']).'">Edit</a></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5">Database unavailable</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</section>
