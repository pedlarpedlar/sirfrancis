<?php
$free_shipping_amount = isset($free_shipping_amount) ? $free_shipping_amount : (function_exists('getCandybirdFreeShippingAmount') ? getCandybirdFreeShippingAmount() : 750);
$return_window = isset($return_window) ? $return_window : '14 days';
$hotline = isset($hotline) ? $hotline : '';

// Define the base delay and increment
$baseDelay = 0.3;
$delayIncrement = 0.1;

// Array containing slide data
// Define the slides array with dynamic delays
$slides = [
    [
        'bg_img' => 'bg-img1',
        'title' => 'Ensuring Quality',
        'subtitle' => 'Specialising in over 150 types of nuts, dried fruit & healthy mixes',
        'description' => 'Our goal is simple: to make healthy snacking convenient and enjoyable for all. With a focus on quality and integrity, we invite you to join us on a journey to better health and wellness, one bite at a time.',
        'button_text' => 'Read More',
        'button_link' => 'https://www.candybird.co.za/about',
        'animation' => [
            'text' => 'fadeInLeft',
            'title' => 'fadeInLeft',
            'description' => 'fadeInUp',
            'button' => 'fadeInLeft'
        ],
        'delay_in_button' => $baseDelay,
        'delay_in_title' => $baseDelay,
        'delay_in_description' => $baseDelay
        
        
    ],
    [
        'bg_img' => 'bg-img2',
        'title' => 'Classy Gifting & Personalisation',
        'subtitle' => 'Classy, Quality, Perfection',
        'description' => 'We believe every gift should be as unique as the person receiving it. Our gifting and personalization service allows you to create memorable presents that stand out.',
        'button_text' => 'Browse Gifting Range',
        'button_link' => 'products?category=Gifting',
        'animation' => [
            'text' => 'fadeInRight',
            'title' => 'fadeInRight',
            'description' => 'fadeInUp',
            'button' => 'fadeInRight'
        ],
        'delay_in_button' => $baseDelay + $delayIncrement,
        'delay_in_title' => $baseDelay + $delayIncrement,
        'delay_in_description' => $baseDelay + $delayIncrement
        
        
    ],
    [
        'bg_img' => 'bg-img3',
        'title' => 'Free Shipping',
        'subtitle' => 'Enjoy free shipping',
        'description' => 'Enjoy free shipping on all orders over R'.$free_shipping_amount.'. Get your neighbours to order together and save on delivery costs!',
        'button_text' => 'Shop Now',
        'button_link' => 'products',
        'animation' => [
            'text' => 'fadeInDown',
            'title' => 'fadeInDown',
            'description' => 'fadeInUp',
            'button' => 'fadeInDown'
        ],
        'delay_in_button' => $baseDelay + 2 * $delayIncrement,
        'delay_in_title' => $baseDelay + 2 * $delayIncrement,
        'delay_in_description' => $baseDelay + 2 * $delayIncrement
        
        
    ],
    [
        'bg_img' => 'bg-img4',
        'title' => 'Bulk Orders',
        'subtitle' => 'Special Reseller Packs available',
        'description' => 'Order in bulk and get special discounts. Ideal for businesses, events, or large gatherings.',
        'button_text' => 'View Reseller Options',
        'button_link' => 'products?category=For%20Resellers',
        'animation' => [
            'text' => 'fadeInUp',
            'title' => 'fadeInUp',
            'description' => 'fadeInUp',
            'button' => 'fadeInUp'
        ],
        'delay_in_button' => $baseDelay + 3 * $delayIncrement,
        'delay_in_title' => $baseDelay + 3 * $delayIncrement,
        'delay_in_description' => $baseDelay + 3 * $delayIncrement
        
        
    ],
    [
        'bg_img' => 'bg-img5',
        'title' => 'Wholesale',
        'subtitle' => 'Partner with Us',
        'description' => 'Become a wholesaler and enjoy great rates on our full range of products. Partner with us for mutual success.',
        'button_text' => 'Join Us',
        'button_link' => 'https://www.candybird.co.za/wholesale',
        'animation' => [
            'text' => 'fadeInLeft',
            'title' => 'fadeInLeft',
            'description' => 'fadeInLeft',
            'button' => 'fadeInLeft'
        ],
        'delay_in_button' => $baseDelay + 4 * $delayIncrement,
        'delay_in_title' => $baseDelay + 4 * $delayIncrement,
        'delay_in_description' => $baseDelay + 4 * $delayIncrement
        
        
    ],
    [
        'bg_img' => 'bg-img6',
        'title' => 'Global Services',
        'subtitle' => 'Worldwide Delivery',
        'description' => 'We offer global shipping to ensure our products reach you no matter where you are. Shipping is available right to your door with our international services.',
        'button_text' => 'Explore More',
        'button_link' => 'https://www.candybird.co.za/global-services',
        'animation' => [
            'text' => 'fadeInRight',
            'title' => 'fadeInRight',
            'description' => 'fadeInRight',
            'button' => 'fadeInRight'
        ],
        'delay_in_button' => $baseDelay + 5 * $delayIncrement,
        'delay_in_title' => $baseDelay + 5 * $delayIncrement,
        'delay_in_description' => $baseDelay + 5 * $delayIncrement
        
        
    ],
    [
        'bg_img' => 'bg-img8',
        'title' => 'Live Pricelist',
        'subtitle' => 'Competitive pricing on Quality items',
        'description' => 'Our pricelist is updated and live on the website accessible at any time for your convenience',
        'button_text' => 'View Pricelist',
        'button_link' => 'pricelist',
        'animation' => [
            'text' => 'fadeInDown',
            'title' => 'fadeInDown',
            'description' => 'fadeInDown',
            'button' => 'fadeInDown'
        ],
        'delay_in_button' => $baseDelay + 6 * $delayIncrement,
        'delay_in_title' => $baseDelay + 6 * $delayIncrement,
        'delay_in_description' => $baseDelay + 6 * $delayIncrement
        
        
    ],
    [
        'bg_img' => 'bg-img7',
        'title' => 'Contact Us',
        'subtitle' => 'We Are Here to Help',
        'description' => 'Have any questions? Reach out to us and we’ll be happy to assist you with anything you need.',
        'button_text' => 'Get in Touch',
        'button_link' => 'https://www.candybird.co.za/contact',
        'animation' => [
            'text' => 'fadeInRight',
            'title' => 'fadeInRight',
            'description' => 'fadeInUp',
            'button' => 'fadeInRight'
        ],
        'delay_in_button' => $baseDelay + 7 * $delayIncrement,
        'delay_in_title' => $baseDelay + 7 * $delayIncrement,
        'delay_in_description' => $baseDelay + 7 * $delayIncrement
        
        
    ]
];




// Array containing static media information
$staticMedia = [
    [
        'img' => '2.png',
        'title' => 'Free Shipping',
        'text' => "Free shipping to your nearest Pudo locker on all orders over R$free_shipping_amount"
    ],
    [
        'img' => '3.png',
        'title' => '100% Satisfaction',
        'text' => "Unsatisfied with your purchase? Return within $return_window to get a full refund."
    ],
    [
        'img' => '4.png',
        'title' => '100% Payment Secure',
        'text' => 'Your payments are safely processed with Payfast.'
    ],
    [
        'img' => '5.png',
        'title' => 'Open 24/7',
        'text' => "Our online chat is open 24 hours a day, WhatsApp $hotline"
    ]
];


// Array containing brand information
$brands = [
    ['img' => '1.png', 'alt' => 'wholesale'],
    ['img' => '2.png', 'alt' => 'free delivery'],
    ['img' => '3.png', 'alt' => 'gifting'],
    ['img' => '4.png', 'alt' => 'private labelling'],
    ['img' => '5.png', 'alt' => 'buyer protection'],
    ['img' => '2.png', 'alt' => 'free delivery'],
    ['img' => '3.png', 'alt' => 'gifting']
];
