<?php

// Get the name of the page currently open
$currentPage = basename($_SERVER['PHP_SELF']);


// Logged-in username
$sidebarUsername =
    $_SESSION['username'] ?? 'User';


// Check whether the current page belongs to a menu item
function sidebarActive(
    string $currentPage,
    array $pages
): string {

    return in_array(
        $currentPage,
        $pages,
        true
    )
        ? 'active'
        : '';

}

?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>


<aside class="sidebar" id="systemSidebar">


    <!-- Sidebar brand -->

    <div class="sidebar-brand">

        <div class="sidebar-logo">
            🐔
        </div>

        <div class="sidebar-brand-text">

            <h2>Poultry Farm</h2>

            <p>Management System</p>

        </div>

        <button
            type="button"
            class="sidebar-close-button"
            id="sidebarCloseButton"
            aria-label="Close sidebar"
        >
            ×
        </button>

    </div>


    <!-- Logged-in user -->

    <div class="sidebar-user">

        <div class="sidebar-user-avatar">

            <?php
            echo strtoupper(
                substr(
                    htmlspecialchars(
                        $sidebarUsername
                    ),
                    0,
                    1
                )
            );
            ?>

        </div>

        <div class="sidebar-user-details">

            <span>Signed in as</span>

            <strong>
                <?php
                echo htmlspecialchars(
                    $sidebarUsername
                );
                ?>
            </strong>

        </div>

    </div>


    <!-- Main navigation -->

    <nav class="sidebar-navigation">


        <p class="sidebar-section-title">
            Farm Management
        </p>


        <ul>

            <li>

                <a
                    href="dashboard.php"
                    class="<?php
                    echo sidebarActive(
                        $currentPage,
                        ['dashboard.php']
                    );
                    ?>"
                >

                    <span class="sidebar-menu-icon">
                        🏠
                    </span>

                    <span class="sidebar-menu-text">
                        Dashboard
                    </span>

                </a>

            </li>


            <li>

                <a
                    href="birds.php"
                    class="<?php
                    echo sidebarActive(
                        $currentPage,
                        [
                            'birds.php',
                            'edit_bird.php',
                            'delete_bird.php'
                        ]
                    );
                    ?>"
                >

                    <span class="sidebar-menu-icon">
                        🐔
                    </span>

                    <span class="sidebar-menu-text">
                        Birds
                    </span>

                </a>

            </li>


            <li>

                <a
                    href="feed.php"
                    class="<?php
                    echo sidebarActive(
                        $currentPage,
                        [
                            'feed.php',
                            'edit_feed.php',
                            'delete_feed.php'
                        ]
                    );
                    ?>"
                >

                    <span class="sidebar-menu-icon">
                        🌽
                    </span>

                    <span class="sidebar-menu-text">
                        Feed
                    </span>

                </a>

            </li>


            <li>

                <a
                    href="sales.php"
                    class="<?php
                    echo sidebarActive(
                        $currentPage,
                        [
                            'sales.php',
                            'edit_sale.php',
                            'delete_sale.php'
                        ]
                    );
                    ?>"
                >

                    <span class="sidebar-menu-icon">
                        💰
                    </span>

                    <span class="sidebar-menu-text">
                        Sales
                    </span>

                </a>

            </li>


            <li>

                <a
                    href="vaccination.php"
                    class="<?php
                    echo sidebarActive(
                        $currentPage,
                        [
                            'vaccination.php',
                            'edit_vaccination.php',
                            'delete_vaccination.php'
                        ]
                    );
                    ?>"
                >

                    <span class="sidebar-menu-icon">
                        💉
                    </span>

                    <span class="sidebar-menu-text">
                        Vaccination
                    </span>

                </a>

            </li>


            <li>

                <a
                    href="mortality.php"
                    class="<?php
                    echo sidebarActive(
                        $currentPage,
                        [
                            'mortality.php',
                            'edit_mortality.php',
                            'delete_mortality.php'
                        ]
                    );
                    ?>"
                >

                    <span class="sidebar-menu-icon">
                        ⚠️
                    </span>

                    <span class="sidebar-menu-text">
                        Mortality
                    </span>

                </a>

            </li>


            <li>

                <a
                    href="expenses.php"
                    class="<?php
                    echo sidebarActive(
                        $currentPage,
                        [
                            'expenses.php',
                            'edit_expense.php',
                            'delete_expense.php'
                        ]
                    );
                    ?>"
                >

                    <span class="sidebar-menu-icon">
                        🧾
                    </span>

                    <span class="sidebar-menu-text">
                        Expenses
                    </span>

                </a>

            </li>

        </ul>


        <p class="sidebar-section-title">
            Analysis
        </p>


        <ul>

            <li>

                <a
                    href="reports.php"
                    class="<?php
                    echo sidebarActive(
                        $currentPage,
                        ['reports.php']
                    );
                    ?>"
                >

                    <span class="sidebar-menu-icon">
                        📄
                    </span>

                    <span class="sidebar-menu-text">
                        Reports
                    </span>

                </a>

            </li>


            <li>

                <a
                    href="statistics.php"
                    class="<?php
                    echo sidebarActive(
                        $currentPage,
                        ['statistics.php']
                    );
                    ?>"
                >

                    <span class="sidebar-menu-icon">
                        📊
                    </span>

                    <span class="sidebar-menu-text">
                        Statistics
                    </span>

                </a>

            </li>

        </ul>


        <p class="sidebar-section-title">
            Account
        </p>


        <ul>

            <li>

                <a
                    href="profile.php"
                    class="<?php
                    echo sidebarActive(
                        $currentPage,
                        ['profile.php']
                    );
                    ?>"
                >

                    <span class="sidebar-menu-icon">
                        👤
                    </span>

                    <span class="sidebar-menu-text">
                        Profile
                    </span>

                </a>

            </li>


            <li>

                <a
                    href="settings.php"
                    class="<?php
                    echo sidebarActive(
                        $currentPage,
                        ['settings.php']
                    );
                    ?>"
                >

                    <span class="sidebar-menu-icon">
                        ⚙️
                    </span>

                    <span class="sidebar-menu-text">
                        Settings
                    </span>

                </a>

            </li>


            <li>

                <a
                    href="change_password.php"
                    class="<?php
                    echo sidebarActive(
                        $currentPage,
                        ['change_password.php']
                    );
                    ?>"
                >

                    <span class="sidebar-menu-icon">
                        🔐
                    </span>

                    <span class="sidebar-menu-text">
                        Change Password
                    </span>

                </a>

            </li>

        </ul>

    </nav>


    <!-- Logout -->

    <div class="sidebar-footer">

        <a
            href="logout.php"
            class="sidebar-logout-link"
        >

            <span class="sidebar-menu-icon">
                🚪
            </span>

            <span class="sidebar-menu-text">
                Logout
            </span>

        </a>

        <p>
            Poultry Management System
        </p>

    </div>


</aside>


<!-- Mobile menu button -->

<button
    type="button"
    class="sidebar-menu-button"
    id="sidebarMenuButton"
    aria-label="Open sidebar"
>
    ☰
</button>


<script>

const systemSidebar =
    document.getElementById("systemSidebar");

const sidebarOverlay =
    document.getElementById("sidebarOverlay");

const sidebarMenuButton =
    document.getElementById("sidebarMenuButton");

const sidebarCloseButton =
    document.getElementById("sidebarCloseButton");


function openSidebar(){

    systemSidebar.classList.add(
        "sidebar-open"
    );

    sidebarOverlay.classList.add(
        "sidebar-overlay-visible"
    );

    document.body.classList.add(
        "sidebar-body-locked"
    );

}


function closeSidebar(){

    systemSidebar.classList.remove(
        "sidebar-open"
    );

    sidebarOverlay.classList.remove(
        "sidebar-overlay-visible"
    );

    document.body.classList.remove(
        "sidebar-body-locked"
    );

}


sidebarMenuButton.addEventListener(
    "click",
    openSidebar
);


sidebarCloseButton.addEventListener(
    "click",
    closeSidebar
);


sidebarOverlay.addEventListener(
    "click",
    closeSidebar
);

</script>