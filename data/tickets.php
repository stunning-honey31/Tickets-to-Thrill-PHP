<?php
/**
 * Ticket catalog. In a production app this would live in the database;
 * kept as a plain PHP array here since the lineup rarely changes and it
 * keeps the demo self-contained with no seeding step required.
 *
 * Each ticket: id, label (category shown on the card), name, blurb,
 * bullets, price, was (optional pre-discount price), per (unit label),
 * badge (optional ribbon text), category (used for filtering).
 */

function ticket_catalog(): array
{
    return [
        'individual' => [
            'title' => 'Single-entry passes',
            'eyebrow' => '🎫 Individual',
            'tickets' => [
                [
                    'id' => 'ind1', 'label' => 'Individual', 'name' => 'Single Day Pass',
                    'blurb' => 'Unlimited rides for one calendar day.',
                    'bullets' => ['All rides & attractions', 'Valid any single day'],
                    'price' => 599, 'per' => 'per person', 'category' => 'individual',
                ],
                [
                    'id' => 'ind2', 'label' => 'Individual', 'name' => 'Two Day Pass',
                    'blurb' => 'Come back tomorrow — same ticket, two visits.',
                    'bullets' => ['All rides, 2 calendar days', 'Use on any 2 days within 30 days'],
                    'price' => 999, 'per' => 'per person', 'category' => 'individual',
                ],
                [
                    'id' => 'ind3', 'label' => 'Individual', 'name' => 'Season Pass', 'badge' => 'FANS',
                    'blurb' => 'Unlimited visits for a full year.',
                    'bullets' => ['Unlimited entry, 12 months', '10% off food & merch'],
                    'price' => 2499, 'per' => 'per person', 'category' => 'individual',
                ],
                [
                    'id' => 'ind4', 'label' => 'Individual', 'name' => 'Child Pass (Under 12)',
                    'blurb' => 'Full-day access for young riders.',
                    'bullets' => ['All height-eligible rides', 'ID may be requested at gate'],
                    'price' => 399, 'per' => 'per child', 'category' => 'individual',
                ],
                [
                    'id' => 'ind5', 'label' => 'Individual', 'name' => 'Senior Pass (60+)',
                    'blurb' => 'Full-day access at a friendlier rate.',
                    'bullets' => ['All rides & attractions', 'Valid ID required at gate'],
                    'price' => 399, 'per' => 'per person', 'category' => 'individual',
                ],
                [
                    'id' => 'ind6', 'label' => 'Add-on', 'name' => 'Fast Pass (Skip-the-line)',
                    'blurb' => 'Priority lane on 6 signature rides. Requires a valid entry ticket.',
                    'bullets' => ['Stacks with any pass', 'Valid one day'],
                    'price' => 349, 'per' => 'per person', 'category' => 'individual',
                ],
            ],
        ],
        'combo' => [
            'title' => 'Bundle up & save',
            'eyebrow' => '🎁 Combo',
            'tickets' => [
                [
                    'id' => 'combo1', 'label' => 'Combo', 'name' => 'Ride + Splash Combo', 'badge' => 'BEST VALUE',
                    'blurb' => 'All rides plus full-day access to Splash Zone.',
                    'bullets' => ['All rides & water park', 'Locker & towel included'],
                    'price' => 949, 'was' => 1299, 'per' => 'per person', 'category' => 'combo',
                ],
                [
                    'id' => 'combo2', 'label' => 'Combo', 'name' => 'Ride + Nightfall Show Combo',
                    'blurb' => 'Day rides plus reserved seating for the fireworks show.',
                    'bullets' => ['All rides + evening show', 'Reserved viewing area'],
                    'price' => 1099, 'was' => 1399, 'per' => 'per person', 'category' => 'combo',
                ],
                [
                    'id' => 'combo3', 'label' => 'Combo', 'name' => 'Ultimate Combo', 'badge' => 'ALL-IN',
                    'blurb' => 'Rides, splash zone, evening show and a meal voucher.',
                    'bullets' => ['Everything the park offers', '₹300 meal voucher included'],
                    'price' => 1599, 'was' => 2199, 'per' => 'per person', 'category' => 'combo',
                ],
                [
                    'id' => 'combo4', 'label' => 'Combo', 'name' => 'Weekend Combo (2-Day)',
                    'blurb' => 'Two full days, every attraction, one weekend.',
                    'bullets' => ['Sat + Sun full access', 'Free re-entry both days'],
                    'price' => 1799, 'was' => 2399, 'per' => 'per person', 'category' => 'combo',
                ],
            ],
        ],
        'offer' => [
            'title' => 'Deals worth planning around',
            'eyebrow' => '🔥 Limited-Time Offers',
            'tickets' => [
                [
                    'id' => 'offer1', 'label' => 'Offer', 'name' => 'Family 4-Pack', 'badge' => 'POPULAR',
                    'blurb' => '2 adults + 2 kids, full-day access, one price.',
                    'bullets' => ['Save ₹800 vs individual', 'Free meal voucher'],
                    'price' => 1999, 'was' => 2799, 'per' => 'per family', 'category' => 'offer',
                ],
                [
                    'id' => 'offer2', 'label' => 'Offer', 'name' => 'Group of 10+ Pack',
                    'blurb' => 'For school trips, birthdays and office outings.',
                    'bullets' => ['Save 23% vs single pass', 'Dedicated group entry lane'],
                    'price' => 4499, 'was' => 5999, 'per' => 'per group of 10', 'category' => 'offer',
                ],
                [
                    'id' => 'offer3', 'label' => 'Offer', 'name' => 'Early Bird Entry', 'badge' => 'EARLY',
                    'blurb' => 'Gate opens 1 hour early, just for early bird holders.',
                    'bullets' => ['Entry from 9AM', 'Shortest lines of the day'],
                    'price' => 449, 'was' => 599, 'per' => 'per person', 'category' => 'offer',
                ],
                [
                    'id' => 'offer4', 'label' => 'Offer', 'name' => 'Student Special',
                    'blurb' => 'Valid student ID gets you full-day access, discounted.',
                    'bullets' => ['ID checked at gate', 'Mon–Fri only'],
                    'price' => 479, 'was' => 599, 'per' => 'per person', 'category' => 'offer',
                ],
            ],
        ],
    ];
}

/** Look up one ticket by id, wherever it lives in the catalog. */
function ticket_find(string $id): ?array
{
    foreach (ticket_catalog() as $group) {
        foreach ($group['tickets'] as $ticket) {
            if ($ticket['id'] === $id) {
                return $ticket;
            }
        }
    }
    return null;
}

/** Flat list of the three "popular picks" shown on the homepage. */
function ticket_popular_picks(): array
{
    $catalog = ticket_catalog();
    return [
        $catalog['individual']['tickets'][0], // Single Day Pass
        $catalog['combo']['tickets'][0],       // Ride + Splash Combo
        $catalog['offer']['tickets'][0],       // Family 4-Pack
    ];
}
