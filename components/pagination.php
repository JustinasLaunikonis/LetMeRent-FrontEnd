<?php
// Page navigation buttons. The variables $totalPages, $page come from
// components/listings.php.

if ($totalPages > 1) {
    // Build the URL
    $queryParams = $_GET;
    unset($queryParams['page']);

    if (!empty($queryParams)) {
        $baseUrl = '?' . http_build_query($queryParams) . '&page=';
    } else {
        $baseUrl = '?page=';
    }

    echo '<nav class="pagination">';

    // Previous button
    if ($page > 1) {
        $prevPage = $page - 1;
        echo '<a href="' . $baseUrl . $prevPage . '" class="pagination-btn">&#8592; Prev</a>';
    } else {
        echo '<span class="pagination-btn pagination-btn--disabled">&#8592; Prev</span>';
    }

    // Page number buttons with ellipsis form for long page counts (34414 for example)
    for ($i = 1; $i <= $totalPages; $i++) {
        $isFirst   = ($i === 1);
        $isLast    = ($i === $totalPages);
        $isNearCurrent = ($i >= $page - 2 && $i <= $page + 2);

        if ($isFirst || $isLast || $isNearCurrent) {
            if ($i === $page) {
                echo '<span class="pagination-btn pagination-btn--active">' . $i . '</span>';
            } else {
                echo '<a href="' . $baseUrl . $i . '" class="pagination-btn">' . $i . '</a>';
            }
        } else if ($i === $page - 3 || $i === $page + 3) {
            echo '<span class="pagination-ellipsis">…</span>';
        }
    }

    // Next button
    if ($page < $totalPages) {
        $nextPage = $page + 1;
        echo '<a href="' . $baseUrl . $nextPage . '" class="pagination-btn">Next &#8594;</a>';
    } else {
        echo '<span class="pagination-btn pagination-btn--disabled">Next &#8594;</span>';
    }

    echo '</nav>';
}
?>
