<?php

session_start();

if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit();
}

include "config/database.php";

$start_date = "";
$end_date = "";

$totalSales = 0;
$totalExpenses = 0;
$profit = 0;

$salesResult = null;
$expenseResult = null;
$batchPerformanceResult = null;

$errorMessage = "";

if(isset($_POST['generate'])){

    $start_date = mysqli_real_escape_string(
        $conn,
        $_POST['start_date']
    );

    $end_date = mysqli_real_escape_string(
        $conn,
        $_POST['end_date']
    );

    if($start_date > $end_date){

        $errorMessage = "The start date cannot be later than the end date.";

    }else{

        // Retrieve sales records for the selected period
        $salesQuery = "SELECT *
                       FROM sales
                       WHERE sale_date BETWEEN '$start_date' AND '$end_date'
                       ORDER BY sale_date DESC";

        $salesResult = mysqli_query($conn, $salesQuery);


        // Retrieve expense records for the selected period
        $expenseQuery = "SELECT *
                         FROM expenses
                         WHERE expense_date BETWEEN '$start_date' AND '$end_date'
                         ORDER BY expense_date DESC";

        $expenseResult = mysqli_query($conn, $expenseQuery);


        // Calculate total sales
        $salesTotalQuery = "SELECT SUM(total_amount) AS total_sales
                            FROM sales
                            WHERE sale_date BETWEEN '$start_date' AND '$end_date'";

        $salesTotalResult = mysqli_query($conn, $salesTotalQuery);

        $salesTotalRow = mysqli_fetch_assoc($salesTotalResult);

        $totalSales = $salesTotalRow['total_sales'] ?? 0;


        // Calculate total expenses
        $expenseTotalQuery = "SELECT SUM(amount) AS total_expenses
                              FROM expenses
                              WHERE expense_date BETWEEN '$start_date' AND '$end_date'";

        $expenseTotalResult = mysqli_query($conn, $expenseTotalQuery);

        $expenseTotalRow = mysqli_fetch_assoc($expenseTotalResult);

        $totalExpenses = $expenseTotalRow['total_expenses'] ?? 0;


        // Calculate profit
        $profit = $totalSales - $totalExpenses;

        // Generate bird batch performance information
$batchPerformanceQuery = "
    SELECT

        b.batch_name AS bird_batch,

        b.quantity AS birds_placed,

        COALESCE(
            (
                SELECT SUM(m.number_dead)
                FROM mortality m
                WHERE m.bird_batch = b.batch_name
                AND m.mortality_date BETWEEN '$start_date' AND '$end_date'
            ),
            0
        ) AS birds_dead,

        COALESCE(
            (
                SELECT SUM(s.birds_sold)
                FROM sales s
                WHERE s.bird_batch = b.batch_name
                AND s.sale_date BETWEEN '$start_date' AND '$end_date'
            ),
            0
        ) AS birds_sold

    FROM birds b

    ORDER BY b.id DESC
";

$batchPerformanceResult = mysqli_query(
    $conn,
    $batchPerformanceQuery
);

    }

}

?>

<!DOCTYPE html>

<html>

<head>

    <title>Reports</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<?php include "includes/sidebar.php"; ?>

<div class="content">

  <div class="page-header clean-page-header">

    <div class="page-header-content">

        <h1>Reports</h1>

        <p>
            Generate financial and bird performance reports
            for a selected period.
        </p>

    </div>

</div>

  <div class="module-card">

    <div class="module-card-header">

        <div>

            <h2>Generate Report</h2>

            <p>
                Choose a start date and end date
                for the reporting period.
            </p>

        </div>

    </div>


    <form
        method="POST"
        class="modern-module-form"
    >

        <div class="form-grid">

            <div class="form-field">

                <label for="start_date">
                    Start Date
                </label>

                <input
                    type="date"
                    id="start_date"
                    name="start_date"
                    value="<?php
                    echo htmlspecialchars(
                        $start_date
                    );
                    ?>"
                    required
                >

            </div>


            <div class="form-field">

                <label for="end_date">
                    End Date
                </label>

                <input
                    type="date"
                    id="end_date"
                    name="end_date"
                    value="<?php
                    echo htmlspecialchars(
                        $end_date
                    );
                    ?>"
                    required
                >

            </div>

        </div>


        <div class="form-actions">

            <button
                type="submit"
                name="generate"
                class="primary-action-button"
            >
                Generate Report
            </button>

        </div>

    </form>

</div>

    <?php if($errorMessage != ""){ ?>

        <div class="message-box error">

            <?php echo htmlspecialchars($errorMessage); ?>

        </div>

    <?php } ?>


    <?php if(isset($_POST['generate']) && $errorMessage == ""){ ?>

        <br><br>

        <div class="report-header-card">

    <div>

        <h2>Poultry Farm Financial Report</h2>

        <p>
            Report period:
            <strong>
                <?php echo htmlspecialchars($start_date); ?>
            </strong>
            to
            <strong>
                <?php echo htmlspecialchars($end_date); ?>
            </strong>
        </p>

    </div>


    <button
        type="button"
        class="print-button"
        onclick="window.print()"
    >
        Print Report
    </button>

</div>

        <div class="report-summary">

            <div class="summary-card">

                <h3>Total Sales</h3>

                <h2>
                    K<?php echo number_format($totalSales, 2); ?>
                </h2>

            </div>


            <div class="summary-card">

                <h3>Total Expenses</h3>

                <h2>
                    K<?php echo number_format($totalExpenses, 2); ?>
                </h2>

            </div>


            <div class="summary-card">

                <h3>Profit</h3>

                <h2 class="<?php echo $profit >= 0 ? 'profit-value' : 'loss-value'; ?>">

                    K<?php echo number_format($profit, 2); ?>

                </h2>

            </div>

        </div>




        <div class="records-card report-section-card">

    <div class="records-card-header">

        <div>

            <h2>Sales Report</h2>

            <p>
                Sales recorded within the selected reporting period.
            </p>

        </div>

    </div>

        <?php if(mysqli_num_rows($salesResult) > 0){ ?>

            <table class="report-table">

                <tr>

                    <th>Customer</th>
                    <th>Bird Batch</th>
                    <th>Birds Sold</th>
                    <th>Total</th>
                    <th>Date</th>

                </tr>

                <?php while($sale = mysqli_fetch_assoc($salesResult)){ ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($sale['customer_name']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($sale['bird_batch']); ?>
                        </td>

                        <td>
                            <?php echo $sale['birds_sold']; ?>
                        </td>

                        <td>
                            K<?php echo number_format($sale['total_amount'], 2); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($sale['sale_date']); ?>
                        </td>

                    </tr>

                <?php } ?>

            </table>

        <?php }else{ ?>

            <div class="no-records-message">

                No sales records were found for the selected period.

            </div>

        <?php } ?>

        </div>


        <br><br>

        <div class="records-card report-section-card">

    <div class="records-card-header">

        <div>

            <h2>Expense Report</h2>

            <p>
                Farm expenses recorded within the selected reporting period.
            </p>

        </div>

    </div>

        <?php if(mysqli_num_rows($expenseResult) > 0){ ?>

            <table class="report-table">

                <tr>

                    <th>Expense</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Date</th>

                </tr>

                <?php while($expense = mysqli_fetch_assoc($expenseResult)){ ?>

                    <tr>

                        <td>
                            <?php echo htmlspecialchars($expense['expense_name']); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($expense['expense_category']); ?>
                        </td>

                        <td>
                            K<?php echo number_format($expense['amount'], 2); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($expense['expense_date']); ?>
                        </td>

                    </tr>

                <?php } ?>

            </table>

        <?php }else{ ?>

            <div class="no-records-message">

                No expense records were found for the selected period.

            </div>

        <?php } ?>
        </div>

        <br><br>

<div class="records-card report-section-card">

    <div class="records-card-header">

        <div>

            <h2>Bird Batch Performance Report</h2>

            <p>
                Compare bird placement, sales, mortality
                and estimated remaining stock by batch.
            </p>

        </div>

    </div>

<?php if(
    $batchPerformanceResult &&
    mysqli_num_rows($batchPerformanceResult) > 0
){ ?>

    <table class="report-table">

        <tr>

            <th>Bird Batch</th>
            <th>Birds Placed</th>
            <th>Birds Dead</th>
            <th>Birds Sold</th>
            <th>Estimated Remaining</th>
            <th>Mortality Rate</th>
            <th>Status</th>

        </tr>

        <?php while(
            $batch = mysqli_fetch_assoc($batchPerformanceResult)
        ){ ?>

            <?php

            $birdsPlaced = (int) $batch['birds_placed'];

            $birdsDead = (int) $batch['birds_dead'];

            $birdsSold = (int) $batch['birds_sold'];

            $birdsRemaining =
                $birdsPlaced -
                $birdsDead -
                $birdsSold;

            if($birdsRemaining < 0){

                $birdsRemaining = 0;

            }


            if($birdsPlaced > 0){

                $mortalityRate =
                    ($birdsDead / $birdsPlaced) * 100;

            }else{

                $mortalityRate = 0;

            }


            if($mortalityRate >= 10){

                $performanceStatus = "Critical";

                $statusClass = "status-critical";

            }elseif($mortalityRate >= 5){

                $performanceStatus = "Needs Attention";

                $statusClass = "status-warning";

            }else{

                $performanceStatus = "Good";

                $statusClass = "status-good";

            }

            ?>

            <tr>

                <td>
                    <?php
                    echo htmlspecialchars(
                        $batch['bird_batch']
                    );
                    ?>
                </td>

                <td>
                    <?php echo $birdsPlaced; ?>
                </td>

                <td>
                    <?php echo $birdsDead; ?>
                </td>

                <td>
                    <?php echo $birdsSold; ?>
                </td>

                <td>
                    <?php echo $birdsRemaining; ?>
                </td>

                <td>
                    <?php
                    echo number_format(
                        $mortalityRate,
                        2
                    );
                    ?>%
                </td>

                <td>

                    <span class="<?php echo $statusClass; ?>">

                        <?php echo $performanceStatus; ?>

                    </span>

                </td>

            </tr>

        <?php } ?>

    </table>

<?php }else{ ?>

    <div class="no-records-message">

        No bird batch records were found.

    </div>

<?php } ?>

    <?php } ?>
    </div>

</div>

</body>

</html>