<?php

// Include the Stripe PHP library
require_once 'stripe-php/init.php';

// Set your Stripe API keys
$stripe_secret_key = 'sk_test_51OxYrSRogH7j6YJA6HUWGRaqkSbAvFLmOLg95WHfWJIjfyFh1HcpDKyhqvIk1HdQA1sqbyMbLvZAjEY5QxW4NlPa00QZPAd1hK'; // Replace with your actual secret key
\Stripe\Stripe::setApiKey($stripe_secret_key);

// Define your product details
$product_name = 'Product Name';
$product_description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
$product_price = 9999; // Product price in cents (e.g., $99.99 is 9999)

// Create a new Checkout Session
$session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items' => [[
        'price_data' => [
            'currency' => 'usd',
            'product_data' => [
                'name' => $product_name,
                'description' => $product_description,
            ],
            'unit_amount' => $product_price,
        ],
        'quantity' => 1,
    ]],
    'mode' => 'payment',
    'success_url' => 'https://example.com/success', // Replace with your success URL
    'cancel_url' => 'https://example.com/cancel', // Replace with your cancel URL
]);

// Return the session ID as JSON response
header('Content-Type: application/json');
echo json_encode(['id' => $session->id]);
