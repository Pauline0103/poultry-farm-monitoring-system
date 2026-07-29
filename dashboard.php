<?php

session_start();

if(!isset($_SESSION['username'])){

    header("Location: login.php");

    exit();

}

include "config/database.php";


// Today's date
$today = date('Y-m-d');


// Vaccinations due within the next three days
$threeDaysLater = date(
    'Y-m-d',
    strtotime('+3 days')
);


// Count overdue vaccinations
$sql = "SELECT COUNT(*) AS overdue_total
        FROM vaccination
        WHERE next_due_date < '$today'";

$result = mysqli_query($conn, $sql);

$overdueVaccinations = mysqli_fetch_assoc($result);

$overdueTotal =
    (int) ($overdueVaccinations['overdue_total'] ?? 0);


// Count upcoming vaccinations
$sql = "SELECT COUNT(*) AS upcoming_total
        FROM vaccination
        WHERE next_due_date
        BETWEEN '$today' AND '$threeDaysLater'";

$result = mysqli_query($conn, $sql);

$upcomingVaccinations = mysqli_fetch_assoc($result);

$upcomingTotal =
    (int) ($upcomingVaccinations['upcoming_total'] ?? 0);


// Count bird batches
$sql = "SELECT COUNT(*) AS total_batches
        FROM birds";

$result = mysqli_query($conn, $sql);

$birdBatchResult = mysqli_fetch_assoc($result);

$totalBatches =
    (int) ($birdBatchResult['total_batches'] ?? 0);


// Calculate total birds placed
$sql = "SELECT SUM(quantity) AS total_birds
        FROM birds";

$result = mysqli_query($conn, $sql);

$totalBirdResult = mysqli_fetch_assoc($result);

$totalBirds =
    (int) ($totalBirdResult['total_birds'] ?? 0);


// Count feed records
$sql = "SELECT COUNT(*) AS total_feed_records
        FROM feed";

$result = mysqli_query($conn, $sql);

$feedRecordResult = mysqli_fetch_assoc($result);

$totalFeedRecords =
    (int) ($feedRecordResult['total_feed_records'] ?? 0);


// Calculate total feed bags
$sql = "SELECT SUM(quantity) AS total_feed_bags
        FROM feed";

$result = mysqli_query($conn, $sql);

$totalFeedResult = mysqli_fetch_assoc($result);

$totalFeedBags =
    (float) ($totalFeedResult['total_feed_bags'] ?? 0);


// Calculate total mortality
$sql = "SELECT SUM(number_dead) AS total_mortality
        FROM mortality";

$result = mysqli_query($conn, $sql);

$mortalityResult = mysqli_fetch_assoc($result);

$totalMortality =
    (int) ($mortalityResult['total_mortality'] ?? 0);


// Calculate total birds sold
$sql = "SELECT SUM(birds_sold) AS total_birds_sold
        FROM sales";

$result = mysqli_query($conn, $sql);

$birdsSoldResult = mysqli_fetch_assoc($result);

$totalBirdsSold =
    (int) ($birdsSoldResult['total_birds_sold'] ?? 0);


// Calculate estimated birds remaining
$birdsRemaining =
    $totalBirds -
    $totalMortality -
    $totalBirdsSold;

if($birdsRemaining < 0){

    $birdsRemaining = 0;

}


// Calculate mortality percentage
if($totalBirds > 0){

    $mortalityRate =
        ($totalMortality / $totalBirds) * 100;

}else{

    $mortalityRate = 0;

}


// Calculate total sales
$sql = "SELECT SUM(total_amount) AS total_sales
        FROM sales";

$result = mysqli_query($conn, $sql);

$salesResult = mysqli_fetch_assoc($result);

$totalSales =
    (float) ($salesResult['total_sales'] ?? 0);


// Calculate total expenses
$sql = "SELECT SUM(amount) AS total_expenses
        FROM expenses";

$result = mysqli_query($conn, $sql);

$expensesResult = mysqli_fetch_assoc($result);

$totalExpenses =
    (float) ($expensesResult['total_expenses'] ?? 0);


// Calculate estimated profit
$estimatedProfit =
    $totalSales -
    $totalExpenses;


// Determine dashboard alerts
$highMortality =
    $mortalityRate >= 10;

$lowBirdStock =
    $birdsRemaining > 0 &&
    $birdsRemaining <= 20;

$noBirdStock =
    $totalBirds > 0 &&
    $birdsRemaining == 0;

$financialLoss =
    $estimatedProfit < 0;


// Determine the overall farm status
$farmStatus = "Good";

$farmStatusClass = "farm-status-good";

if(
    $overdueTotal > 0 ||
    $highMortality ||
    $financialLoss ||
    $noBirdStock
){

    $farmStatus = "Needs Immediate Attention";

    $farmStatusClass = "farm-status-danger";

}elseif(
    $upcomingTotal > 0 ||
    $lowBirdStock ||
    $mortalityRate >= 5
){

    $farmStatus = "Needs Attention";

    $farmStatusClass = "farm-status-warning";

}

// Retrieve the five most recent sales
$recentSalesQuery = "SELECT
                        customer_name,
                        bird_batch,
                        birds_sold,
                        total_amount,
                        sale_date
                     FROM sales
                     ORDER BY sale_date DESC, id DESC
                     LIMIT 5";

$recentSales = mysqli_query(
    $conn,
    $recentSalesQuery
);


// Retrieve the five most recent expenses
$recentExpensesQuery = "SELECT
                            expense_name,
                            expense_category,
                            amount,
                            expense_date
                        FROM expenses
                        ORDER BY expense_date DESC, id DESC
                        LIMIT 5";

$recentExpenses = mysqli_query(
    $conn,
    $recentExpensesQuery
);


// Retrieve the five most recent mortality records
$recentMortalityQuery = "SELECT
                            bird_batch,
                            number_dead,
                            cause_of_death,
                            mortality_date
                         FROM mortality
                         ORDER BY mortality_date DESC, id DESC
                         LIMIT 5";

$recentMortality = mysqli_query(
    $conn,
    $recentMortalityQuery
);

// Prepare financial chart percentages
$highestFinancialValue = max(
    $totalSales,
    $totalExpenses,
    1
);

$salesChartPercentage =
    ($totalSales / $highestFinancialValue) * 100;

$expensesChartPercentage =
    ($totalExpenses / $highestFinancialValue) * 100;


// Prepare bird distribution percentages
if($totalBirds > 0){

    $birdsSoldPercentage =
        ($totalBirdsSold / $totalBirds) * 100;

    $birdsDeadPercentage =
        ($totalMortality / $totalBirds) * 100;

    $birdsRemainingPercentage =
        ($birdsRemaining / $totalBirds) * 100;

}else{

    $birdsSoldPercentage = 0;

    $birdsDeadPercentage = 0;

    $birdsRemainingPercentage = 0;

}


// Prevent percentages from exceeding 100
$birdsSoldPercentage = min(
    $birdsSoldPercentage,
    100
);

$birdsDeadPercentage = min(
    $birdsDeadPercentage,
    100
);

$birdsRemainingPercentage = min(
    $birdsRemainingPercentage,
    100
);
?>

<!DOCTYPE html>

<html>

<head>

    <title>Dashboard</title>

    

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<?php include "includes/sidebar.php"; ?>


<div class="content modern-dashboard">


    <div class="dashboard-header">

        <div>

            <h1>

                Welcome,
                <?php
                echo htmlspecialchars(
                    $_SESSION['username']
                );
                ?>

            </h1>

            <p>
                Here is today's overview of your poultry farm.
            </p>

        </div>


        <div class="<?php echo $farmStatusClass; ?>">

            <span>Farm Status</span>

            <strong>
                <?php echo $farmStatus; ?>
            </strong>

        </div>

    </div>


    <!-- Dashboard alerts -->

    <div class="dashboard-alerts">


        <?php if($overdueTotal > 0){ ?>

            <div class="modern-alert danger-alert">

                <div>

                    <h3>Overdue Vaccinations</h3>

                    <p>

                        You have

                        <strong>
                            <?php echo $overdueTotal; ?>
                        </strong>

                        overdue vaccination record(s).

                    </p>

                </div>

                <a href="vaccination.php">
                    View Records
                </a>

            </div>

        <?php } ?>


        <?php if($upcomingTotal > 0){ ?>

            <div class="modern-alert warning-alert">

                <div>

                    <h3>Upcoming Vaccinations</h3>

                    <p>

                        You have

                        <strong>
                            <?php echo $upcomingTotal; ?>
                        </strong>

                        vaccination record(s) due within three days.

                    </p>

                </div>

                <a href="vaccination.php">
                    View Records
                </a>

            </div>

        <?php } ?>


        <?php if($highMortality){ ?>

            <div class="modern-alert danger-alert">

                <div>

                    <h3>High Mortality Rate</h3>

                    <p>

                        The farm mortality rate is

                        <strong>

                            <?php
                            echo number_format(
                                $mortalityRate,
                                2
                            );
                            ?>%

                        </strong>

                    </p>

                </div>

                <a href="mortality.php">
                    Investigate
                </a>

            </div>

        <?php } ?>


        <?php if($lowBirdStock){ ?>

            <div class="modern-alert warning-alert">

                <div>

                    <h3>Low Bird Stock</h3>

                    <p>

                        Only

                        <strong>
                            <?php echo $birdsRemaining; ?>
                        </strong>

                        bird(s) are estimated to remain.

                    </p>

                </div>

                <a href="birds.php">
                    View Birds
                </a>

            </div>

        <?php } ?>


        <?php if($noBirdStock){ ?>

            <div class="modern-alert danger-alert">

                <div>

                    <h3>No Birds Remaining</h3>

                    <p>
                        The system estimates that no birds remain in stock.
                    </p>

                </div>

                <a href="birds.php">
                    View Records
                </a>

            </div>

        <?php } ?>


        <?php if($financialLoss){ ?>

            <div class="modern-alert danger-alert">

                <div>

                    <h3>Financial Warning</h3>

                    <p>

                        Expenses currently exceed sales by

                        <strong>

                            K<?php
                            echo number_format(
                                abs($estimatedProfit),
                                2
                            );
                            ?>

                        </strong>

                    </p>

                </div>

                <a href="reports.php">
                    View Reports
                </a>

            </div>

        <?php } ?>


    </div>


    <!-- Main summary cards -->

    <h2 class="dashboard-section-title">
        Farm Overview
    </h2>


    <div class="modern-card-grid">


        <div class="modern-card">

            <div class="modern-card-label">
                Bird Batches
            </div>

            <div class="modern-card-number">
                <?php echo $totalBatches; ?>
            </div>

            <a href="birds.php">
                View batches
            </a>

        </div>


        <div class="modern-card">

            <div class="modern-card-label">
                Total Birds Placed
            </div>

            <div class="modern-card-number">
                <?php echo $totalBirds; ?>
            </div>

            <a href="birds.php">
                View bird records
            </a>

        </div>


        <div class="modern-card featured-card">

            <div class="modern-card-label">
                Birds Remaining
            </div>

            <div class="modern-card-number">
                <?php echo $birdsRemaining; ?>
            </div>

            <small>
                Estimated current stock
            </small>

        </div>


        <div class="modern-card">

            <div class="modern-card-label">
                Birds Sold
            </div>

            <div class="modern-card-number">
                <?php echo $totalBirdsSold; ?>
            </div>

            <a href="sales.php">
                View sales
            </a>

        </div>


        <div class="modern-card">

            <div class="modern-card-label">
                Total Mortality
            </div>

            <div class="modern-card-number">
                <?php echo $totalMortality; ?>
            </div>

            <a href="mortality.php">
                View mortality
            </a>

        </div>


        <div class="modern-card">

            <div class="modern-card-label">
                Mortality Rate
            </div>

            <div class="modern-card-number">

                <?php
                echo number_format(
                    $mortalityRate,
                    2
                );
                ?>%

            </div>

            <small>
                Based on total birds placed
            </small>

        </div>


        <div class="modern-card">

            <div class="modern-card-label">
                Feed Records
            </div>

            <div class="modern-card-number">
                <?php echo $totalFeedRecords; ?>
            </div>

            <a href="feed.php">
                View feed records
            </a>

        </div>


        <div class="modern-card">

            <div class="modern-card-label">
                Total Feed Bags
            </div>

            <div class="modern-card-number">

                <?php
                echo number_format(
                    $totalFeedBags,
                    0
                );
                ?>

            </div>

            <a href="feed.php">
                Manage feed
            </a>

        </div>


    </div>


    <!-- Financial cards -->

    <h2 class="dashboard-section-title">
        Financial Overview
    </h2>


    <div class="financial-card-grid">


        <div class="financial-card">

            <span>Total Sales</span>

            <strong>

                K<?php
                echo number_format(
                    $totalSales,
                    2
                );
                ?>

            </strong>

            <a href="sales.php">
                View sales records
            </a>

        </div>


        <div class="financial-card">

            <span>Total Expenses</span>

            <strong>

                K<?php
                echo number_format(
                    $totalExpenses,
                    2
                );
                ?>

            </strong>

            <a href="expenses.php">
                View expense records
            </a>

        </div>


        <div class="financial-card">

            <span>
                Estimated
                <?php
                echo $estimatedProfit >= 0
                    ? "Profit"
                    : "Loss";
                ?>
            </span>

            <strong class="<?php echo $estimatedProfit >= 0
                ? 'profit-value'
                : 'loss-value'; ?>">

                K<?php
                echo number_format(
                    abs($estimatedProfit),
                    2
                );
                ?>

            </strong>

            <a href="reports.php">
                Open reports
            </a>

        </div>


    </div>


    <!-- Visual analytics -->

<h2 class="dashboard-section-title">
    Visual Analytics
</h2>


<div class="analytics-grid">


    <!-- Financial comparison chart -->

    <div class="analytics-card">

        <div class="analytics-card-header">

            <div>

                <h3>Sales vs Expenses</h3>

                <p>
                    Overall financial comparison
                </p>

            </div>

            <a href="reports.php">
                Reports
            </a>

        </div>


        <div class="chart-row">

            <div class="chart-label">

                <span>Sales</span>

                <strong>
                    K<?php echo number_format($totalSales, 2); ?>
                </strong>

            </div>

            <div class="chart-track">

                <div
                    class="chart-bar sales-chart-bar"
                    style="width:
                    <?php
                    echo number_format(
                        $salesChartPercentage,
                        2,
                        '.',
                        ''
                    );
                    ?>%;"
                >
                </div>

            </div>

        </div>


        <div class="chart-row">

            <div class="chart-label">

                <span>Expenses</span>

                <strong>
                    K<?php echo number_format($totalExpenses, 2); ?>
                </strong>

            </div>

            <div class="chart-track">

                <div
                    class="chart-bar expenses-chart-bar"
                    style="width:
                    <?php
                    echo number_format(
                        $expensesChartPercentage,
                        2,
                        '.',
                        ''
                    );
                    ?>%;"
                >
                </div>

            </div>

        </div>


        <div class="financial-chart-result">

            <span>
                Current result
            </span>

            <strong class="<?php echo $estimatedProfit >= 0
                ? 'analytics-profit'
                : 'analytics-loss'; ?>">

                <?php
                echo $estimatedProfit >= 0
                    ? "Profit: "
                    : "Loss: ";
                ?>

                K<?php
                echo number_format(
                    abs($estimatedProfit),
                    2
                );
                ?>

            </strong>

        </div>

    </div>


    <!-- Bird distribution chart -->

    <div class="analytics-card">

        <div class="analytics-card-header">

            <div>

                <h3>Bird Stock Distribution</h3>

                <p>
                    Current flock performance
                </p>

            </div>

            <a href="birds.php">
                Birds
            </a>

        </div>


        <div class="distribution-summary">

            <div>

                <strong>
                    <?php echo $totalBirds; ?>
                </strong>

                <span>
                    Birds placed
                </span>

            </div>

            <div>

                <strong>
                    <?php echo $totalBirdsSold; ?>
                </strong>

                <span>
                    Birds sold
                </span>

            </div>

            <div>

                <strong>
                    <?php echo $totalMortality; ?>
                </strong>

                <span>
                    Birds dead
                </span>

            </div>

            <div>

                <strong>
                    <?php echo $birdsRemaining; ?>
                </strong>

                <span>
                    Remaining
                </span>

            </div>

        </div>


        <div class="stacked-chart">

            <?php if($birdsRemainingPercentage > 0){ ?>

                <div
                    class="stacked-section remaining-section"
                    style="width:
                    <?php
                    echo number_format(
                        $birdsRemainingPercentage,
                        2,
                        '.',
                        ''
                    );
                    ?>%;"
                    title="Birds Remaining"
                >
                </div>

            <?php } ?>


            <?php if($birdsSoldPercentage > 0){ ?>

                <div
                    class="stacked-section sold-section"
                    style="width:
                    <?php
                    echo number_format(
                        $birdsSoldPercentage,
                        2,
                        '.',
                        ''
                    );
                    ?>%;"
                    title="Birds Sold"
                >
                </div>

            <?php } ?>


            <?php if($birdsDeadPercentage > 0){ ?>

                <div
                    class="stacked-section dead-section"
                    style="width:
                    <?php
                    echo number_format(
                        $birdsDeadPercentage,
                        2,
                        '.',
                        ''
                    );
                    ?>%;"
                    title="Birds Dead"
                >
                </div>

            <?php } ?>

        </div>


        <div class="chart-legend">

            <div>

                <span class="legend-box remaining-legend"></span>

                Remaining

            </div>

            <div>

                <span class="legend-box sold-legend"></span>

                Sold

            </div>

            <div>

                <span class="legend-box dead-legend"></span>

                Dead

            </div>

        </div>

    </div>


</div>

<div class="performance-progress-grid">


    <div class="progress-card">

        <div class="progress-card-heading">

            <span>Birds Sold</span>

            <strong>

                <?php
                echo number_format(
                    $birdsSoldPercentage,
                    1
                );
                ?>%

            </strong>

        </div>

        <div class="progress-track">

            <div
                class="progress-fill sold-progress"
                style="width:
                <?php
                echo number_format(
                    $birdsSoldPercentage,
                    2,
                    '.',
                    ''
                );
                ?>%;"
            >
            </div>

        </div>

        <small>
            Percentage of birds placed that have been sold
        </small>

    </div>


    <div class="progress-card">

        <div class="progress-card-heading">

            <span>Mortality</span>

            <strong>

                <?php
                echo number_format(
                    $birdsDeadPercentage,
                    1
                );
                ?>%

            </strong>

        </div>

        <div class="progress-track">

            <div
                class="progress-fill mortality-progress"
                style="width:
                <?php
                echo number_format(
                    $birdsDeadPercentage,
                    2,
                    '.',
                    ''
                );
                ?>%;"
            >
            </div>

        </div>

        <small>
            Percentage of birds placed that have died
        </small>

    </div>


    <div class="progress-card">

        <div class="progress-card-heading">

            <span>Birds Remaining</span>

            <strong>

                <?php
                echo number_format(
                    $birdsRemainingPercentage,
                    1
                );
                ?>%

            </strong>

        </div>

        <div class="progress-track">

            <div
                class="progress-fill remaining-progress"
                style="width:
                <?php
                echo number_format(
                    $birdsRemainingPercentage,
                    2,
                    '.',
                    ''
                );
                ?>%;"
            >
            </div>

        </div>

        <small>
            Estimated percentage of birds still in stock
        </small>

    </div>


</div>
    <!-- Quick actions -->

    <h2 class="dashboard-section-title">
        Quick Actions
    </h2>


    <div class="quick-actions">

        <a href="birds.php">
            Manage Birds
        </a>

        <a href="feed.php">
            Record Feed
        </a>

        <a href="sales.php">
            Record Sale
        </a>

        <a href="vaccination.php">
            Add Vaccination
        </a>

        <a href="mortality.php">
            Record Mortality
        </a>

        <a href="expenses.php">
            Add Expense
        </a>

        <a href="reports.php">
            Generate Report
        </a>

    </div>

    <h2 class="dashboard-section-title">
    Recent Farm Activity
</h2>

<div class="recent-activity-grid">

<div class="activity-panel">

    <div class="activity-panel-header">

        <div>

            <h3>Recent Sales</h3>

            <p>
                The latest five sales records
            </p>

        </div>

        <a href="sales.php">
            View All
        </a>

    </div>


    <?php if(
        $recentSales &&
        mysqli_num_rows($recentSales) > 0
    ){ ?>

        <div class="activity-table-container">

            <table class="activity-table">

                <tr>

                    <th>Customer</th>
                    <th>Batch</th>
                    <th>Birds</th>
                    <th>Amount</th>
                    <th>Date</th>

                </tr>

                <?php while(
                    $sale = mysqli_fetch_assoc($recentSales)
                ){ ?>

                    <tr>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $sale['customer_name']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $sale['bird_batch']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo (int) $sale['birds_sold'];
                            ?>
                        </td>

                        <td>
                            K<?php
                            echo number_format(
                                $sale['total_amount'],
                                2
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $sale['sale_date']
                            );
                            ?>
                        </td>

                    </tr>

                <?php } ?>

            </table>

        </div>

    <?php }else{ ?>

        <div class="activity-empty">

            No sales have been recorded yet.

        </div>

    <?php } ?>

</div>

<div class="activity-panel">

    <div class="activity-panel-header">

        <div>

            <h3>Recent Expenses</h3>

            <p>
                The latest five expense records
            </p>

        </div>

        <a href="expenses.php">
            View All
        </a>

    </div>


    <?php if(
        $recentExpenses &&
        mysqli_num_rows($recentExpenses) > 0
    ){ ?>

        <div class="activity-table-container">

            <table class="activity-table">

                <tr>

                    <th>Expense</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Date</th>

                </tr>

                <?php while(
                    $expense = mysqli_fetch_assoc($recentExpenses)
                ){ ?>

                    <tr>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $expense['expense_name']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $expense['expense_category']
                            );
                            ?>
                        </td>

                        <td>
                            K<?php
                            echo number_format(
                                $expense['amount'],
                                2
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $expense['expense_date']
                            );
                            ?>
                        </td>

                    </tr>

                <?php } ?>

            </table>

        </div>

    <?php }else{ ?>

        <div class="activity-empty">

            No expenses have been recorded yet.

        </div>

    <?php } ?>

</div>

<div class="activity-panel">

    <div class="activity-panel-header">

        <div>

            <h3>Recent Mortality</h3>

            <p>
                The latest five mortality records
            </p>

        </div>

        <a href="mortality.php">
            View All
        </a>

    </div>


    <?php if(
        $recentMortality &&
        mysqli_num_rows($recentMortality) > 0
    ){ ?>

        <div class="activity-table-container">

            <table class="activity-table">

                <tr>

                    <th>Batch</th>
                    <th>Dead Birds</th>
                    <th>Cause</th>
                    <th>Date</th>

                </tr>

                <?php while(
                    $death = mysqli_fetch_assoc($recentMortality)
                ){ ?>

                    <tr>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $death['bird_batch']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo (int) $death['number_dead'];
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $death['cause_of_death']
                            );
                            ?>
                        </td>

                        <td>
                            <?php
                            echo htmlspecialchars(
                                $death['mortality_date']
                            );
                            ?>
                        </td>

                    </tr>

                <?php } ?>

            </table>

        </div>

    <?php }else{ ?>

        <div class="activity-empty">

            No mortality records have been added yet.

        </div>

    <?php } ?>

</div>

</div>

</div>

</body>

</html>