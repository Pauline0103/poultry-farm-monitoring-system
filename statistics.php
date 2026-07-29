<?php

session_start();


// Protect the page
if(!isset($_SESSION['username'])){

    header("Location: login.php");

    exit();

}


include "config/database.php";


// Current month information
$currentMonthStart = date("Y-m-01");

$currentMonthEnd = date("Y-m-t");


// -------------------------------------------------
// 1. CURRENT MONTH SALES
// -------------------------------------------------

$currentSalesQuery = "
    SELECT
        COALESCE(
            SUM(total_amount),
            0
        ) AS current_sales
    FROM sales
    WHERE sale_date
    BETWEEN ? AND ?
";

$currentSalesStatement =
    mysqli_prepare(
        $conn,
        $currentSalesQuery
    );

$currentMonthSales = 0;


if($currentSalesStatement){

    mysqli_stmt_bind_param(
        $currentSalesStatement,
        "ss",
        $currentMonthStart,
        $currentMonthEnd
    );

    mysqli_stmt_execute(
        $currentSalesStatement
    );

    $currentSalesResult =
        mysqli_stmt_get_result(
            $currentSalesStatement
        );

    $currentSalesRecord =
        mysqli_fetch_assoc(
            $currentSalesResult
        );

    $currentMonthSales =
        (float) (
            $currentSalesRecord['current_sales'] ?? 0
        );

    mysqli_stmt_close(
        $currentSalesStatement
    );

}


// -------------------------------------------------
// 2. CURRENT MONTH EXPENSES
// -------------------------------------------------

$currentExpensesQuery = "
    SELECT
        COALESCE(
            SUM(amount),
            0
        ) AS current_expenses
    FROM expenses
    WHERE expense_date
    BETWEEN ? AND ?
";

$currentExpensesStatement =
    mysqli_prepare(
        $conn,
        $currentExpensesQuery
    );

$currentMonthExpenses = 0;


if($currentExpensesStatement){

    mysqli_stmt_bind_param(
        $currentExpensesStatement,
        "ss",
        $currentMonthStart,
        $currentMonthEnd
    );

    mysqli_stmt_execute(
        $currentExpensesStatement
    );

    $currentExpensesResult =
        mysqli_stmt_get_result(
            $currentExpensesStatement
        );

    $currentExpensesRecord =
        mysqli_fetch_assoc(
            $currentExpensesResult
        );

    $currentMonthExpenses =
        (float) (
            $currentExpensesRecord['current_expenses'] ?? 0
        );

    mysqli_stmt_close(
        $currentExpensesStatement
    );

}


// Current monthly profit or loss
$currentMonthProfit =
    $currentMonthSales -
    $currentMonthExpenses;


// -------------------------------------------------
// 3. CURRENT MONTH BIRDS SOLD
// -------------------------------------------------

$currentBirdsSoldQuery = "
    SELECT
        COALESCE(
            SUM(birds_sold),
            0
        ) AS current_birds_sold
    FROM sales
    WHERE sale_date
    BETWEEN ? AND ?
";

$currentBirdsSoldStatement =
    mysqli_prepare(
        $conn,
        $currentBirdsSoldQuery
    );

$currentMonthBirdsSold = 0;


if($currentBirdsSoldStatement){

    mysqli_stmt_bind_param(
        $currentBirdsSoldStatement,
        "ss",
        $currentMonthStart,
        $currentMonthEnd
    );

    mysqli_stmt_execute(
        $currentBirdsSoldStatement
    );

    $currentBirdsSoldResult =
        mysqli_stmt_get_result(
            $currentBirdsSoldStatement
        );

    $currentBirdsSoldRecord =
        mysqli_fetch_assoc(
            $currentBirdsSoldResult
        );

    $currentMonthBirdsSold =
        (int) (
            $currentBirdsSoldRecord[
                'current_birds_sold'
            ] ?? 0
        );

    mysqli_stmt_close(
        $currentBirdsSoldStatement
    );

}


// -------------------------------------------------
// 4. PREPARE THE LAST SIX MONTHS
// -------------------------------------------------

$monthlyStatistics = [];

$highestMonthlyValue = 1;


// Start five months ago, including the current month
for($monthOffset = 5; $monthOffset >= 0; $monthOffset--){


    $monthTimestamp =
        strtotime(
            "-$monthOffset months"
        );


    $monthStart =
        date(
            "Y-m-01",
            $monthTimestamp
        );


    $monthEnd =
        date(
            "Y-m-t",
            $monthTimestamp
        );


    $monthLabel =
        date(
            "M Y",
            $monthTimestamp
        );


    // Get sales for this month
    $monthlySalesQuery = "
        SELECT
            COALESCE(
                SUM(total_amount),
                0
            ) AS monthly_sales
        FROM sales
        WHERE sale_date
        BETWEEN ? AND ?
    ";

    $monthlySalesStatement =
        mysqli_prepare(
            $conn,
            $monthlySalesQuery
        );

    $monthlySales = 0;


    if($monthlySalesStatement){

        mysqli_stmt_bind_param(
            $monthlySalesStatement,
            "ss",
            $monthStart,
            $monthEnd
        );

        mysqli_stmt_execute(
            $monthlySalesStatement
        );

        $monthlySalesResult =
            mysqli_stmt_get_result(
                $monthlySalesStatement
            );

        $monthlySalesRecord =
            mysqli_fetch_assoc(
                $monthlySalesResult
            );

        $monthlySales =
            (float) (
                $monthlySalesRecord[
                    'monthly_sales'
                ] ?? 0
            );

        mysqli_stmt_close(
            $monthlySalesStatement
        );

    }


    // Get expenses for this month
    $monthlyExpensesQuery = "
        SELECT
            COALESCE(
                SUM(amount),
                0
            ) AS monthly_expenses
        FROM expenses
        WHERE expense_date
        BETWEEN ? AND ?
    ";

    $monthlyExpensesStatement =
        mysqli_prepare(
            $conn,
            $monthlyExpensesQuery
        );

    $monthlyExpenses = 0;


    if($monthlyExpensesStatement){

        mysqli_stmt_bind_param(
            $monthlyExpensesStatement,
            "ss",
            $monthStart,
            $monthEnd
        );

        mysqli_stmt_execute(
            $monthlyExpensesStatement
        );

        $monthlyExpensesResult =
            mysqli_stmt_get_result(
                $monthlyExpensesStatement
            );

        $monthlyExpensesRecord =
            mysqli_fetch_assoc(
                $monthlyExpensesResult
            );

        $monthlyExpenses =
            (float) (
                $monthlyExpensesRecord[
                    'monthly_expenses'
                ] ?? 0
            );

        mysqli_stmt_close(
            $monthlyExpensesStatement
        );

    }


    $monthlyProfit =
        $monthlySales -
        $monthlyExpenses;


    $monthlyStatistics[] = [

        "label" => $monthLabel,

        "sales" => $monthlySales,

        "expenses" => $monthlyExpenses,

        "profit" => $monthlyProfit

    ];


    $highestMonthlyValue = max(

        $highestMonthlyValue,

        $monthlySales,

        $monthlyExpenses

    );

}


// -------------------------------------------------
// 5. BIRDS SOLD BY BATCH
// -------------------------------------------------

$batchSalesQuery = "
    SELECT
        bird_batch,
        COALESCE(
            SUM(birds_sold),
            0
        ) AS total_birds_sold
    FROM sales
    GROUP BY bird_batch
    ORDER BY total_birds_sold DESC
";

$batchSalesResult =
    mysqli_query(
        $conn,
        $batchSalesQuery
    );

$batchStatistics = [];

$highestBatchSales = 1;


if($batchSalesResult){

    while(
        $batchRecord =
        mysqli_fetch_assoc(
            $batchSalesResult
        )
    ){

        $batchBirdsSold =
            (int) $batchRecord[
                'total_birds_sold'
            ];


        $batchStatistics[] = [

            "batch_name" =>
                $batchRecord['bird_batch'],

            "birds_sold" =>
                $batchBirdsSold

        ];


        $highestBatchSales = max(

            $highestBatchSales,

            $batchBirdsSold

        );

    }

}


// -------------------------------------------------
// 6. EXPENSES BY CATEGORY
// -------------------------------------------------

$categoryExpensesQuery = "
    SELECT
        expense_category,
        COALESCE(
            SUM(amount),
            0
        ) AS category_total
    FROM expenses
    GROUP BY expense_category
    ORDER BY category_total DESC
";

$categoryExpensesResult =
    mysqli_query(
        $conn,
        $categoryExpensesQuery
    );

$categoryStatistics = [];

$highestCategoryExpense = 1;


if($categoryExpensesResult){

    while(
        $categoryRecord =
        mysqli_fetch_assoc(
            $categoryExpensesResult
        )
    ){

        $categoryTotal =
            (float) $categoryRecord[
                'category_total'
            ];


        $categoryStatistics[] = [

            "category" =>
                $categoryRecord[
                    'expense_category'
                ],

            "total" =>
                $categoryTotal

        ];


        $highestCategoryExpense = max(

            $highestCategoryExpense,

            $categoryTotal

        );

    }

}

?>

<!DOCTYPE html>

<html>

<head>

    <title>Farm Statistics</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<?php include "includes/sidebar.php"; ?>


<div class="content">


    <!-- Page heading -->

    <div class="statistics-page-heading">

        <div>

            <h1>Farm Statistics</h1>

            <p>
                Review sales, expenses and flock
                performance trends.
            </p>

        </div>

        <a
            href="reports.php"
            class="statistics-report-button"
        >
            Open Reports
        </a>

    </div>


    <!-- Summary cards -->

    <h2 class="dashboard-section-title">
        Current Month Summary
    </h2>


    <div class="statistics-summary-grid">


        <div class="statistics-summary-card">

            <span>
                Monthly Sales
            </span>

            <strong>
                K<?php
                echo number_format(
                    $currentMonthSales,
                    2
                );
                ?>
            </strong>

            <small>
                Sales revenue recorded this month
            </small>

        </div>


        <div class="statistics-summary-card">

            <span>
                Monthly Expenses
            </span>

            <strong>
                K<?php
                echo number_format(
                    $currentMonthExpenses,
                    2
                );
                ?>
            </strong>

            <small>
                Farm expenses recorded this month
            </small>

        </div>


        <div class="statistics-summary-card">

            <span>
                Monthly Result
            </span>

            <strong class="<?php
                echo $currentMonthProfit >= 0
                    ? 'positive-statistic'
                    : 'negative-statistic';
            ?>">

                <?php
                echo $currentMonthProfit >= 0
                    ? "Profit: "
                    : "Loss: ";
                ?>

                K<?php
                echo number_format(
                    abs($currentMonthProfit),
                    2
                );
                ?>

            </strong>

            <small>
                Sales minus expenses
            </small>

        </div>


        <div class="statistics-summary-card">

            <span>
                Birds Sold
            </span>

            <strong>
                <?php
                echo number_format(
                    $currentMonthBirdsSold
                );
                ?>
            </strong>

            <small>
                Birds sold during the current month
            </small>

        </div>


    </div>


    <!-- Monthly financial trends -->

    <h2 class="dashboard-section-title">
        Six-Month Financial Trend
    </h2>


    <div class="statistics-chart-card">


        <div class="statistics-chart-heading">

            <div>

                <h3>
                    Monthly Sales and Expenses
                </h3>

                <p>
                    Comparison for the last six months
                </p>

            </div>


            <div class="statistics-chart-key">

                <span>
                    <i class="sales-key"></i>
                    Sales
                </span>

                <span>
                    <i class="expenses-key"></i>
                    Expenses
                </span>

            </div>

        </div>


        <div class="monthly-chart">


            <?php foreach(
                $monthlyStatistics
                as $monthlyRecord
            ){ ?>


                <?php

                $salesHeight =
                    (
                        $monthlyRecord['sales'] /
                        $highestMonthlyValue
                    ) * 100;


                $expensesHeight =
                    (
                        $monthlyRecord['expenses'] /
                        $highestMonthlyValue
                    ) * 100;

                ?>


                <div class="monthly-chart-column">


                    <div class="monthly-bar-area">


                        <div
                            class="vertical-chart-bar monthly-sales-bar"
                            style="height:<?php
                                echo number_format(
                                    $salesHeight,
                                    2,
                                    '.',
                                    ''
                                );
                            ?>%;"
                            title="Sales: K<?php
                                echo number_format(
                                    $monthlyRecord['sales'],
                                    2
                                );
                            ?>"
                        >
                        </div>


                        <div
                            class="vertical-chart-bar monthly-expenses-bar"
                            style="height:<?php
                                echo number_format(
                                    $expensesHeight,
                                    2,
                                    '.',
                                    ''
                                );
                            ?>%;"
                            title="Expenses: K<?php
                                echo number_format(
                                    $monthlyRecord['expenses'],
                                    2
                                );
                            ?>"
                        >
                        </div>


                    </div>


                    <strong class="monthly-chart-label">

                        <?php
                        echo htmlspecialchars(
                            $monthlyRecord['label']
                        );
                        ?>

                    </strong>


                    <span class="<?php
                        echo $monthlyRecord['profit'] >= 0
                            ? 'monthly-profit'
                            : 'monthly-loss';
                    ?>">

                        <?php
                        echo $monthlyRecord['profit'] >= 0
                            ? "Profit "
                            : "Loss ";
                        ?>

                        K<?php
                        echo number_format(
                            abs(
                                $monthlyRecord['profit']
                            ),
                            2
                        );
                        ?>

                    </span>


                </div>


            <?php } ?>


        </div>


    </div>


    <!-- Batch and expense charts -->

    <div class="statistics-two-column-grid">


        <!-- Birds sold by batch -->

        <div class="statistics-chart-card">

            <div class="statistics-chart-heading">

                <div>

                    <h3>
                        Birds Sold by Batch
                    </h3>

                    <p>
                        Total birds sold from each batch
                    </p>

                </div>

            </div>


            <?php if(
                count($batchStatistics) > 0
            ){ ?>


                <div class="horizontal-statistics-list">


                    <?php foreach(
                        $batchStatistics
                        as $batchRecord
                    ){ ?>


                        <?php

                        $batchPercentage =
                            (
                                $batchRecord['birds_sold'] /
                                $highestBatchSales
                            ) * 100;

                        ?>


                        <div class="horizontal-statistic">


                            <div class="horizontal-statistic-heading">

                                <span>
                                    <?php
                                    echo htmlspecialchars(
                                        $batchRecord[
                                            'batch_name'
                                        ]
                                    );
                                    ?>
                                </span>

                                <strong>
                                    <?php
                                    echo number_format(
                                        $batchRecord[
                                            'birds_sold'
                                        ]
                                    );
                                    ?>
                                    birds
                                </strong>

                            </div>


                            <div class="horizontal-statistic-track">

                                <div
                                    class="horizontal-statistic-fill batch-statistic-fill"
                                    style="width:<?php
                                        echo number_format(
                                            $batchPercentage,
                                            2,
                                            '.',
                                            ''
                                        );
                                    ?>%;"
                                >
                                </div>

                            </div>


                        </div>


                    <?php } ?>


                </div>


            <?php }else{ ?>


                <div class="statistics-empty-state">

                    No sales have been recorded yet.

                </div>


            <?php } ?>


        </div>


        <!-- Expenses by category -->

        <div class="statistics-chart-card">

            <div class="statistics-chart-heading">

                <div>

                    <h3>
                        Expenses by Category
                    </h3>

                    <p>
                        Total spending in each category
                    </p>

                </div>

            </div>


            <?php if(
                count($categoryStatistics) > 0
            ){ ?>


                <div class="horizontal-statistics-list">


                    <?php foreach(
                        $categoryStatistics
                        as $categoryRecord
                    ){ ?>


                        <?php

                        $categoryPercentage =
                            (
                                $categoryRecord['total'] /
                                $highestCategoryExpense
                            ) * 100;

                        ?>


                        <div class="horizontal-statistic">


                            <div class="horizontal-statistic-heading">

                                <span>
                                    <?php
                                    echo htmlspecialchars(
                                        $categoryRecord[
                                            'category'
                                        ]
                                    );
                                    ?>
                                </span>

                                <strong>
                                    K<?php
                                    echo number_format(
                                        $categoryRecord[
                                            'total'
                                        ],
                                        2
                                    );
                                    ?>
                                </strong>

                            </div>


                            <div class="horizontal-statistic-track">

                                <div
                                    class="horizontal-statistic-fill category-statistic-fill"
                                    style="width:<?php
                                        echo number_format(
                                            $categoryPercentage,
                                            2,
                                            '.',
                                            ''
                                        );
                                    ?>%;"
                                >
                                </div>

                            </div>


                        </div>


                    <?php } ?>


                </div>


            <?php }else{ ?>


                <div class="statistics-empty-state">

                    No expense records have been added yet.

                </div>


            <?php } ?>


        </div>


    </div>


    <!-- Monthly details table -->

    <h2 class="dashboard-section-title">
        Monthly Financial Details
    </h2>


    <div class="table-responsive">

        <table
            border="1"
            cellpadding="10"
            cellspacing="0"
            width="100%"
        >

            <tr>

                <th>Month</th>
                <th>Sales</th>
                <th>Expenses</th>
                <th>Profit/Loss</th>
                <th>Status</th>

            </tr>


            <?php foreach(
                $monthlyStatistics
                as $monthlyRecord
            ){ ?>

                <tr>

                    <td>
                        <?php
                        echo htmlspecialchars(
                            $monthlyRecord['label']
                        );
                        ?>
                    </td>

                    <td>
                        K<?php
                        echo number_format(
                            $monthlyRecord['sales'],
                            2
                        );
                        ?>
                    </td>

                    <td>
                        K<?php
                        echo number_format(
                            $monthlyRecord['expenses'],
                            2
                        );
                        ?>
                    </td>

                    <td>
                        K<?php
                        echo number_format(
                            abs(
                                $monthlyRecord['profit']
                            ),
                            2
                        );
                        ?>
                    </td>

                    <td>

                        <span class="<?php
                            echo $monthlyRecord['profit'] >= 0
                                ? 'statistics-status-profit'
                                : 'statistics-status-loss';
                        ?>">

                            <?php
                            echo $monthlyRecord['profit'] >= 0
                                ? "Profit"
                                : "Loss";
                            ?>

                        </span>

                    </td>

                </tr>

            <?php } ?>


        </table>

    </div>


</div>

</body>

</html>