<?php

/**
 * Vercel Serverless Entry Point
 * 
 * This file forwards all requests to Laravel's public/index.php
 * Required for Vercel's PHP serverless function structure.
 */

// Forward to Laravel's entry point
require __DIR__ . '/../public/index.php';
