<?php

// Get the name of the page currently open
$currentPage = basename($_SERVER['PHP_SELF']);

?>

<div class="sidebar">

    <h2>Poultry System</h2>

    <ul>

        <li>
            <a
                href="dashboard.php"
                class="<?php echo $currentPage === 'dashboard.php'
                    ? 'active'
                    : ''; ?>"
            >
                🏠 Dashboard
            </a>
        </li>

        <li>
            <a
                href="birds.php"
                class="<?php echo $currentPage === 'birds.php'
                    ? 'active'
                    : ''; ?>"
            >
                🐔 Birds
            </a>
        </li>

        <li>
            <a
                href="feed.php"
                class="<?php echo $currentPage === 'feed.php'
                    ? 'active'
                    : ''; ?>"
            >
                🌽 Feed
            </a>
        </li>

        <li>
            <a
                href="sales.php"
                class="<?php echo $currentPage === 'sales.php'
                    ? 'active'
                    : ''; ?>"
            >
                💰 Sales
            </a>
        </li>

        <li>
            <a
                href="vaccination.php"
                class="<?php echo $currentPage === 'vaccination.php'
                    ? 'active'
                    : ''; ?>"
            >
                💉 Vaccination
            </a>
        </li>

        <li>
            <a
                href="mortality.php"
                class="<?php echo $currentPage === 'mortality.php'
                    ? 'active'
                    : ''; ?>"
            >
                ⚠️ Mortality
            </a>
        </li>

        <li>
            <a
                href="expenses.php"
                class="<?php echo $currentPage === 'expenses.php'
                    ? 'active'
                    : ''; ?>"
            >
                🧾 Expenses
            </a>
        </li>

        <li>
            <a
                href="reports.php"
                class="<?php echo $currentPage === 'reports.php'
                    ? 'active'
                    : ''; ?>"
            >
                📄 Reports
            </a>
        </li>

        <li>
            <a
                href="statistics.php"
                class="<?php echo $currentPage === 'statistics.php'
                    ? 'active'
                    : ''; ?>"
            >
                📊 Statistics
            </a>
        </li>


        <li>
    <a
        href="profile.php"
        class="<?php echo $currentPage === 'profile.php'
            ? 'active'
            : ''; ?>"
    >
        👤 Profile
    </a>
</li>

<li>
    <a
        href="settings.php"
        class="<?php echo $currentPage === 'settings.php'
            ? 'active'
            : ''; ?>"
    >
        ⚙️ Settings
    </a>

    <a href="change_password.php">Change Password</a>
</li>
        <li>
            <a href="logout.php">
                🚪 Logout
            </a>
        </li>

    </ul>

</div>