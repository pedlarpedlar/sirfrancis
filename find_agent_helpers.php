<?php

function sfAgentRegionsBase()
{
    return [
        'kwazulu-natal' => ['label' => 'KwaZulu-Natal', 'query' => 'Durban, South Africa'],
        'gauteng' => ['label' => 'Gauteng', 'query' => 'Johannesburg, South Africa'],
        'western-cape' => ['label' => 'Western Cape', 'query' => 'Cape Town, South Africa'],
        'eastern-cape' => ['label' => 'Eastern Cape', 'query' => 'Gqeberha, South Africa'],
        'free-state' => ['label' => 'Free State', 'query' => 'Bloemfontein, South Africa'],
        'north-west' => ['label' => 'North West', 'query' => 'Rustenburg, South Africa'],
        'mpumalanga' => ['label' => 'Mpumalanga', 'query' => 'Mbombela, South Africa'],
        'limpopo' => ['label' => 'Limpopo', 'query' => 'Polokwane, South Africa'],
        'northern-cape' => ['label' => 'Northern Cape', 'query' => 'Kimberley, South Africa'],
    ];
}

function sfAgentEnsureTable($conn)
{
    if (!($conn instanceof mysqli)) {
        return false;
    }

    return (bool) $conn->query("CREATE TABLE IF NOT EXISTS sirfrancis_agents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        province_slug VARCHAR(80) NOT NULL,
        city VARCHAR(120) NOT NULL,
        business_name VARCHAR(180) NOT NULL,
        address VARCHAR(255) NOT NULL,
        phone VARCHAR(60) NOT NULL,
        google_maps_query VARCHAR(255) NULL,
        lat DECIMAL(10,7) NULL,
        lng DECIMAL(10,7) NULL,
        notes TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_agent_region_active (province_slug, is_active, sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function sfAgentText($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function sfFindAgentRegions($conn = null)
{
    $regions = [];
    foreach (sfAgentRegionsBase() as $slug => $region) {
        $regions[$slug] = [
            'label' => $region['label'],
            'title' => $region['label'] . ' enquiries',
            'name' => 'Nearest Sir Francis support',
            'details' => 'No dedicated agent is listed for this region yet. Contact Sir Francis support or suggest a local supplier.',
            'query' => $region['query'],
            'direct' => false,
            'city_agents' => [],
        ];
    }

    if (!($conn instanceof mysqli) || !sfAgentEnsureTable($conn)) {
        return $regions;
    }

    $result = $conn->query("SELECT * FROM sirfrancis_agents WHERE is_active = 1 ORDER BY province_slug ASC, sort_order ASC, business_name ASC");
    if (!$result) {
        return $regions;
    }

    while ($row = $result->fetch_assoc()) {
        $slug = (string) ($row['province_slug'] ?? '');
        if (!isset($regions[$slug])) {
            continue;
        }
        $address = trim((string) ($row['address'] ?? ''));
        $phone = trim((string) ($row['phone'] ?? ''));
        $details = $address;
        if ($phone !== '') {
            $details .= ($details !== '' ? ' | ' : '') . 'Phone: ' . $phone;
        }
        $cityAgent = [
            'city' => (string) ($row['city'] ?? ''),
            'name' => (string) ($row['business_name'] ?? ''),
            'details' => $details,
            'address' => $address,
            'phone' => $phone,
            'query' => trim((string) ($row['google_maps_query'] ?? '')) ?: $address,
            'lat' => $row['lat'] !== null ? (float) $row['lat'] : null,
            'lng' => $row['lng'] !== null ? (float) $row['lng'] : null,
            'contact_subject' => 'Agent enquiry - ' . (string) ($row['business_name'] ?? $regions[$slug]['label']),
        ];
        $regions[$slug]['city_agents'][] = $cityAgent;
    }

    foreach ($regions as $slug => $region) {
        if (!empty($region['city_agents'])) {
            $firstAgent = $region['city_agents'][0];
            $regions[$slug]['title'] = $region['label'] . ' agents';
            $regions[$slug]['name'] = $firstAgent['name'];
            $regions[$slug]['details'] = $firstAgent['details'];
            $regions[$slug]['query'] = $firstAgent['query'];
            $regions[$slug]['direct'] = true;
        }
    }

    return $regions;
}
