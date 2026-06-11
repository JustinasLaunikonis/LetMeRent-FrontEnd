<?php

function findListingById($listingId)
{
    global $lookupError;

    $searchParams = [
        ['id' => $listingId, 'limit' => 1, 'skip' => 0],
        ['_id' => $listingId, 'limit' => 1, 'skip' => 0],
    ];

    foreach ($searchParams as $params) {
        $result = fetchFromApi($params);
        if (isset($result['error'])) {
            $lookupError = $result['error'];
            continue;
        }

        foreach ($result['data'] as $listing) {
            if (!is_array($listing)) {
                continue;
            }

            if (listingMatchesId($listing, $listingId)) {
                return $listing;
            }
        }
    }

    $pageSize = 200;
    $skip = 0;

    while (true) {
        $result = fetchFromApi([
            'limit' => $pageSize,
            'skip' => $skip,
        ]);

        if (isset($result['error'])) {
            $lookupError = $result['error'];
            return [];
        }

        foreach ($result['data'] as $listing) {
            if (!is_array($listing)) {
                continue;
            }

            if (listingMatchesId($listing, $listingId)) {
                return $listing;
            }
        }

        $returned = count($result['data']);
        if (isset($result['count'])) {
            $count = (int) $result['count'];
        } else {
            $count = $returned;
        }
        $skip += $returned;

        if ($returned === 0 || $skip >= $count) {
            break;
        }
    }

    return [];
}

function listingMatchesId($listing, $listingId)
{
    $idValue = '';
    if (isset($listing['id'])) {
        $idValue = (string) $listing['id'];
    }

    $mongoIdValue = '';
    if (isset($listing['_id'])) {
        $mongoIdValue = (string) $listing['_id'];
    }

    if ($idValue === $listingId || $mongoIdValue === $listingId) {
        return true;
    }

    return false;
}
