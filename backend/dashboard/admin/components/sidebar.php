<?php
// Sidebar component — simple nav with section links
?>
<aside class="sidebar">
    <nav class="nav-menu">
        <ul>
            <li class="nav-item <?php echo ($section==='users')? 'active':'';?>"><a class="nav-link" href="?section=users">Users</a></li>
            <li class="nav-item <?php echo ($section==='bookings')? 'active':'';?>"><a class="nav-link" href="?section=bookings">Bookings</a></li>
            <li class="nav-item <?php echo ($section==='reports')? 'active':'';?>"><a class="nav-link" href="?section=reports">Reports</a></li>
            <li class="nav-item <?php echo ($section==='settings')? 'active':'';?>"><a class="nav-link" href="?section=settings">Settings</a></li>
        </ul>
    </nav>
</aside>
