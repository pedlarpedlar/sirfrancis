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
        'title' => 'Collagen & Gelatine',
        'subtitle' => 'Wholesaler',
        'description' => 'Established in 2014, Sir Francis has been the foremost provider of premium fish gelatine, marine collagen, peptides and tripeptides in South Africa.',
        'button_text' => 'Read More',
        'button_link' => 'about',
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
        'title' => 'Create Your Own Brand',
        'subtitle' => 'Private Labelling',
        'description' => 'Whether it is collagen peptides, sea moss, gelatine, cosmetic tripeptides or a custom formulation, we help bring your supplement brand to life.',
        'button_text' => 'Book a Consult',
        'button_link' => 'private_labelling',
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
        'title' => 'Open to Retail',
        'subtitle' => 'Shop Online',
        'description' => 'Purchase our retail range direct to the public at wholesale prices, with delivery options across South Africa.',
        'button_text' => 'Online Store',
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
        'title' => 'Local Stockist',
        'subtitle' => 'Become a Stockist',
        'description' => 'Apply to become a local stockist and build your income with a premium Sir Francis product range.',
        'button_text' => 'Boost Your Income',
        'button_link' => 'resellers',
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
        'description' => 'Buy bulk marine collagen, fish gelatine and wellness ingredients for your business, clinic, brand or distribution network.',
        'button_text' => 'Join Us',
        'button_link' => 'wholesale-pricelist',
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
        'subtitle' => 'Custom Supply',
        'description' => 'Speak to us about bulk supply, formulation support and private-label solutions for local and international projects.',
        'button_text' => 'Explore More',
        'button_link' => 'global-services',
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
        'subtitle' => 'Current pricing',
        'description' => 'Our pricelist is updated and available online for quick bulk and retail reference.',
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
        'button_link' => 'contact',
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
        'text' => "Free shipping to your nearest Pudo locker on all orders over R$free_shipping_amount",
        'link' => 'delivery_policy'
    ],
    [
        'img' => '3.png',
        'title' => '100% Satisfaction',
        'text' => "Unsatisfied with your purchase? Return within $return_window to get a full refund.",
        'link' => 'return_policy'
    ],
    [
        'img' => '4.png',
        'title' => '100% Payment Secure',
        'text' => 'Your payments are safely processed with Payfast.',
        'link' => 'terms'
    ],
    [
        'img' => '5.png',
        'title' => 'Open 24/7',
        'text' => "Our online chat is open 24 hours a day, WhatsApp $hotline",
        'link' => 'contact'
    ]
];


// Array containing brand information
$brands = [
    ['img' => '1.png', 'alt' => 'wholesale', 'link' => 'wholesale'],
    ['img' => '2.png', 'alt' => 'free delivery', 'link' => 'delivery_policy'],
    ['img' => '3.png', 'alt' => 'gifting', 'link' => 'gifting'],
    ['img' => '4.png', 'alt' => 'private labelling', 'link' => 'private_labelling'],
    ['img' => '5.png', 'alt' => 'buyer protection', 'link' => 'return_policy'],
    ['img' => '2.png', 'alt' => 'free delivery', 'link' => 'delivery_policy'],
    ['img' => '3.png', 'alt' => 'gifting', 'link' => 'gifting']
];
