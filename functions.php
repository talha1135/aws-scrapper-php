<?php
// functions.php

// Helper function to safely get text from a crawler
function safe_get_text($node) {
    return $node ? trim($node->nodeValue) : '';
}
