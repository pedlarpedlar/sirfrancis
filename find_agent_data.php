<?php

/*
 * Add or edit Find Agent listings here.
 *
 * Each top-level key is the region slug used by find-agent.php.
 * Add real agents under city_agents with city, name, details, query and contact_subject.
 */
function sfFindAgentRegions()
{
    return [
        'kwazulu-natal' => [
            'label' => 'KwaZulu-Natal',
            'title' => 'Durban support point',
            'name' => 'Sir Francis Durban',
            'details' => 'KwaZulu-Natal regional support for retail, wholesale, private labelling and procurement enquiries.',
            'query' => 'Durban, South Africa',
            'direct' => true,
            'city_agents' => [
                [
                    'city' => 'Durban',
                    'name' => 'Sir Francis Durban',
                    'details' => 'Default Sir Francis support point for trade, retail and procurement enquiries.',
                    'query' => 'Durban, South Africa',
                    'lat' => -29.8587,
                    'lng' => 31.0218,
                    'contact_subject' => 'Agent enquiry - Durban',
                ],
            ],
        ],
        'gauteng' => [
            'label' => 'Gauteng',
            'title' => 'Gauteng enquiries',
            'name' => 'Nearest Sir Francis support',
            'details' => 'No dedicated Gauteng agent is listed yet. We will route your enquiry to Durban while we review local supplier suggestions.',
            'query' => 'Johannesburg, South Africa',
            'city_agents' => [
                [
                    'city' => 'Johannesburg',
                    'name' => 'Sir Francis regional support',
                    'details' => 'Johannesburg enquiries are currently routed to Sir Francis support until a local agent is appointed.',
                    'query' => 'Johannesburg, South Africa',
                    'lat' => -26.2041,
                    'lng' => 28.0473,
                    'contact_subject' => 'Agent enquiry - Johannesburg',
                ],
                [
                    'city' => 'Pretoria',
                    'name' => 'Sir Francis regional support',
                    'details' => 'Pretoria enquiries are currently routed to Sir Francis support until a local agent is appointed.',
                    'query' => 'Pretoria, South Africa',
                    'lat' => -25.7479,
                    'lng' => 28.2293,
                    'contact_subject' => 'Agent enquiry - Pretoria',
                ],
            ],
        ],
        'western-cape' => [
            'label' => 'Western Cape',
            'title' => 'Western Cape enquiries',
            'name' => 'Nearest Sir Francis support',
            'details' => 'No dedicated Western Cape agent is listed yet. Suggest a trusted Cape Town supplier or contact Durban support.',
            'query' => 'Cape Town, South Africa',
            'city_agents' => [
                [
                    'city' => 'Cape Town',
                    'name' => 'Sir Francis regional support',
                    'details' => 'Cape Town enquiries are currently routed to Sir Francis support until a local agent is appointed.',
                    'query' => 'Cape Town, South Africa',
                    'lat' => -33.9249,
                    'lng' => 18.4241,
                    'contact_subject' => 'Agent enquiry - Cape Town',
                ],
            ],
        ],
        'eastern-cape' => [
            'label' => 'Eastern Cape',
            'title' => 'Eastern Cape enquiries',
            'name' => 'Nearest Sir Francis support',
            'details' => 'No dedicated Eastern Cape agent is listed yet. Suggest a local agent or contact Durban support.',
            'query' => 'Gqeberha, South Africa',
            'city_agents' => [
                [
                    'city' => 'Gqeberha',
                    'name' => 'Sir Francis regional support',
                    'details' => 'Gqeberha enquiries are currently routed to Sir Francis support until a local agent is appointed.',
                    'query' => 'Gqeberha, South Africa',
                    'lat' => -33.9608,
                    'lng' => 25.6022,
                    'contact_subject' => 'Agent enquiry - Gqeberha',
                ],
            ],
        ],
        'free-state' => [
            'label' => 'Free State',
            'title' => 'Free State enquiries',
            'name' => 'Nearest Sir Francis support',
            'details' => 'No dedicated Free State agent is listed yet. Suggest a local supplier or contact Durban support.',
            'query' => 'Bloemfontein, South Africa',
            'city_agents' => [
                [
                    'city' => 'Bloemfontein',
                    'name' => 'Sir Francis regional support',
                    'details' => 'Bloemfontein enquiries are currently routed to Sir Francis support until a local agent is appointed.',
                    'query' => 'Bloemfontein, South Africa',
                    'lat' => -29.0852,
                    'lng' => 26.1596,
                    'contact_subject' => 'Agent enquiry - Bloemfontein',
                ],
            ],
        ],
        'north-west' => [
            'label' => 'North West',
            'title' => 'North West enquiries',
            'name' => 'Nearest Sir Francis support',
            'details' => 'No dedicated North West agent is listed yet. Suggest a local supplier or contact Durban support.',
            'query' => 'Rustenburg, South Africa',
            'city_agents' => [
                [
                    'city' => 'Rustenburg',
                    'name' => 'Sir Francis regional support',
                    'details' => 'Rustenburg enquiries are currently routed to Sir Francis support until a local agent is appointed.',
                    'query' => 'Rustenburg, South Africa',
                    'lat' => -25.6676,
                    'lng' => 27.2421,
                    'contact_subject' => 'Agent enquiry - Rustenburg',
                ],
            ],
        ],
        'mpumalanga' => [
            'label' => 'Mpumalanga',
            'title' => 'Mpumalanga enquiries',
            'name' => 'Nearest Sir Francis support',
            'details' => 'No dedicated Mpumalanga agent is listed yet. Suggest a local supplier or contact Durban support.',
            'query' => 'Mbombela, South Africa',
            'city_agents' => [
                [
                    'city' => 'Mbombela',
                    'name' => 'Sir Francis regional support',
                    'details' => 'Mbombela enquiries are currently routed to Sir Francis support until a local agent is appointed.',
                    'query' => 'Mbombela, South Africa',
                    'lat' => -25.4753,
                    'lng' => 30.9694,
                    'contact_subject' => 'Agent enquiry - Mbombela',
                ],
            ],
        ],
        'limpopo' => [
            'label' => 'Limpopo',
            'title' => 'Limpopo enquiries',
            'name' => 'Nearest Sir Francis support',
            'details' => 'No dedicated Limpopo agent is listed yet. Suggest a local supplier or contact Durban support.',
            'query' => 'Polokwane, South Africa',
            'city_agents' => [
                [
                    'city' => 'Polokwane',
                    'name' => 'Sir Francis regional support',
                    'details' => 'Polokwane enquiries are currently routed to Sir Francis support until a local agent is appointed.',
                    'query' => 'Polokwane, South Africa',
                    'lat' => -23.8962,
                    'lng' => 29.4486,
                    'contact_subject' => 'Agent enquiry - Polokwane',
                ],
            ],
        ],
        'northern-cape' => [
            'label' => 'Northern Cape',
            'title' => 'Northern Cape enquiries',
            'name' => 'Nearest Sir Francis support',
            'details' => 'No dedicated Northern Cape agent is listed yet. Suggest a local supplier or contact Durban support.',
            'query' => 'Kimberley, South Africa',
            'city_agents' => [
                [
                    'city' => 'Kimberley',
                    'name' => 'Sir Francis regional support',
                    'details' => 'Kimberley enquiries are currently routed to Sir Francis support until a local agent is appointed.',
                    'query' => 'Kimberley, South Africa',
                    'lat' => -28.7282,
                    'lng' => 24.7499,
                    'contact_subject' => 'Agent enquiry - Kimberley',
                ],
            ],
        ],
    ];
}

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    $regions = sfFindAgentRegions();
    header('Content-Type: text/html; charset=UTF-8');
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sir Francis Agent Data</title>
        <style>
            body { background:#f8f5ee; color:#172235; font-family:Arial, sans-serif; line-height:1.5; margin:0; padding:28px; }
            main { background:#fff; border:1px solid #d8c895; max-width:920px; padding:24px; }
            h1 { color:#172235; margin-top:0; }
            code { background:#f2ead9; padding:2px 5px; }
            table { border-collapse:collapse; margin-top:16px; width:100%; }
            th, td { border:1px solid #e3d6bd; padding:9px; text-align:left; vertical-align:top; }
            th { background:#172235; color:#CEBD88; }
        </style>
    </head>
    <body>
        <main>
            <h1>Sir Francis Agent Data</h1>
            <p>This file feeds the public Find an Agent page. Edit agent regions and city agents in <code>find_agent_data.php</code>.</p>
            <p>For each city agent, update <code>city</code>, <code>name</code>, <code>details</code>, <code>query</code>, <code>lat</code>, <code>lng</code> and <code>contact_subject</code>.</p>
            <table>
                <thead>
                    <tr>
                        <th>Region</th>
                        <th>Cities</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($regions as $region): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($region['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php
                                $cities = $region['city_agents'] ?? [];
                                echo htmlspecialchars(implode(', ', array_map(static function($city) {
                                    return (string) ($city['city'] ?? $city['name'] ?? '');
                                }, is_array($cities) ? $cities : [])), ENT_QUOTES, 'UTF-8');
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </body>
    </html>
    <?php
    exit;
}
