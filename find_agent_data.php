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
                    'contact_subject' => 'Agent enquiry - Johannesburg',
                ],
                [
                    'city' => 'Pretoria',
                    'name' => 'Sir Francis regional support',
                    'details' => 'Pretoria enquiries are currently routed to Sir Francis support until a local agent is appointed.',
                    'query' => 'Pretoria, South Africa',
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
                    'contact_subject' => 'Agent enquiry - Kimberley',
                ],
            ],
        ],
    ];
}
