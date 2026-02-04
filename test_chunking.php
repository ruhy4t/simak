<?php

function testChunking($totalItems)
{
    echo "Testing with $totalItems items:\n";

    // Create dummy items
    $items = range(1, $totalItems);
    $ITEMS_PER_PAGE = 8;

    // Logic from the view
    $chunks = array_chunk($items, $ITEMS_PER_PAGE);

    // Check the last chunk to ensure TTD fits
    $lastChunkIndex = count($chunks) - 1;
    $lastChunk = $chunks[$lastChunkIndex];

    echo "  Initial Chunks: ";
    foreach ($chunks as $c)
        echo count($c) . " ";
    echo "\n";

    // THRESHOLD TO PREVENT CRAMPED TTD:
    if (count($lastChunk) > 6) {
        $lastItem = array_pop($chunks[$lastChunkIndex]);
        $chunks[] = [$lastItem];
        echo "  > 6 items using last chunk logic triggered.\n";
    }

    echo "  Final Chunks:   ";
    foreach ($chunks as $c)
        echo count($c) . " ";
    echo "\n\n";
}

testChunking(6);
testChunking(7);
testChunking(8);
testChunking(9);
testChunking(16);
