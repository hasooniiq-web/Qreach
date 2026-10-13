<?php

/** Public site pages (landing page and reseller look-up) — English. */
return [
    'brand' => 'Qreach SmartTag',
    'dir' => 'ltr',
    'skip' => 'Skip to content',
    'language' => 'Language',

    'nav' => [
        'features' => 'Features',
        'how' => 'How it works',
        'plans' => 'Plans',
        'faq' => 'FAQ',
        'contact' => 'Contact',
        'download' => 'Get started',
    ],

    'meta' => [
        'title' => 'Qreach SmartTag — a smart tag on your vehicle that delivers messages without revealing your number',
        'description' => 'Put a Qreach tag (QR or NFC) on your vehicle so anyone can reach you instantly without seeing your phone number. You control what visitors see and get notified on your phone.',
    ],

    'hero' => [
        'eyebrow' => 'For cars, bikes and every vehicle',
        'title' => 'One tag on your car. Messages reach you — your number stays private.',
        'sub' => 'Stick a Qreach SmartTag (QR or NFC) on your vehicle. Anyone who scans it can message you instantly, and you alone decide what they get to see.',
        'cta' => 'Get started',
        'cta_secondary' => 'How does it work?',
        'trial' => 'Try Plus free for :days days',
    ],

    'mock' => [
        'title' => 'Report to the vehicle owner',
        'sub' => 'Pick a reason and send — the owner gets a notification',
        'reasons' => ['Your car is blocking the way', 'Damage to your vehicle', 'Something looks wrong', 'I need help'],
        'send' => 'Send report',
        'note' => 'The conversation is deleted automatically after 7 days.',
    ],

    'features' => [
        'title' => 'Easy to reach. Fully private.',
        'sub' => 'Everything someone needs to get hold of you — without needing your number, an app or an account.',
        'items' => [
            ['title' => 'Your number stays private', 'text' => 'Visitors write their message on the page and never see your number unless you choose to show it.'],
            ['title' => 'Instant phone notification', 'text' => 'The message arrives as a notification, and you follow the conversation and reply from the app.'],
            ['title' => 'You decide what shows', 'text' => 'Your name, photo, number and accounts: each one is shown or hidden with a single switch.'],
            ['title' => 'Ready for emergencies', 'text' => 'Add an emergency contact visitors can reach when it matters — and choose when it appears.'],
            ['title' => 'Visitors need nothing', 'text' => 'Scan with the phone camera or tap an NFC tag. No app, no sign-up.'],
            ['title' => 'Chats erase themselves', 'text' => 'Conversations are deleted automatically after 7 days, so unneeded data never piles up.'],
        ],
    ],

    'how' => [
        'title' => 'Just three steps',
        'steps' => [
            ['title' => 'Stick the tag', 'text' => 'Place the Qreach tag on the windshield or any visible spot on your vehicle.'],
            ['title' => 'Link it in the app', 'text' => 'Open the app, add your vehicle, and scan the code or type its number to link them.'],
            ['title' => 'Receive messages', 'text' => 'When someone scans the tag you are notified at once, and you reply without revealing your number.'],
        ],
    ],

    'plans' => [
        'title' => 'Pick what suits you',
        'sub' => 'Start free, and move to Plus when you want to show your accounts and get full control.',
        'free' => 'Free',
        'per' => 'per :days days',
        'currency' => 'IQD',
        'trial' => 'Try it free for :days days — once',
        'recommended' => 'Most popular',
        'basic_points' => [
            'Receive messages and notifications',
            'Show your name and number if you choose',
            'An emergency contact',
            'Control what visitors see',
        ],
        'plus_points' => [
            'Everything in Basic',
            'Add Instagram, Facebook, Snapchat, TikTok, WhatsApp and more',
            'Show any account you pick to visitors',
            'More vehicles and tags',
        ],
    ],

    'faq' => [
        'title' => 'Frequently asked questions',
        'items' => [
            ['q' => 'Can people see my phone number?', 'a' => 'No — only if you choose to show it in the "What visitors see" settings. By default a visitor can only send a message.'],
            ['q' => 'Does the person scanning need an app?', 'a' => 'No. The phone camera or NFC is enough; the message page opens right in the browser.'],
            ['q' => 'What if I sell the vehicle or the tag is damaged?', 'a' => 'Unlink the tag from the vehicle in the app and link a new one any time.'],
            ['q' => 'Is the Basic plan really free?', 'a' => 'Yes. You receive messages and can show your name, number and emergency contact at no cost.'],
            ['q' => 'What happens when Plus ends?', 'a' => 'Features keep working for an extra week so you can renew without losing anything, then you return to Basic and your data stays saved.'],
        ],
    ],

    'contact' => [
        'title' => 'Contact us',
        'sub' => 'A question, an order for tags, or a partnership? Write to us and we will reply as soon as we can.',
        'email' => 'Email',
        'phone' => 'Phone',
        'name' => 'Name',
        'your_email' => 'Your email',
        'your_phone' => 'Your phone number',
        'reach_hint' => 'Give your email or phone number (at least one) so we can reply.',
        'message' => 'Your message',
        'send' => 'Send message',
        'sent' => 'We received your message and will get back to you soon.',
        'err_name' => 'Please enter your name.',
        'err_email' => 'That email address is not valid.',
        'err_phone' => 'That phone number is not valid.',
        'err_reach' => 'Please enter your email or phone number so we can reply.',
        'err_message' => 'Please write your message (at least 5 characters).',
        'err_message_long' => 'The message is too long (1500 characters at most).',
    ],

    'download' => [
        'title' => 'Ready to try Qreach?',
        'text' => 'Get a Qreach tag, then download the app and add your vehicle in minutes.',
        'ios' => 'Download on the App Store',
        'android' => 'Get it on Google Play',
        'whatsapp' => 'Chat on WhatsApp',
        'email' => 'Email us',
    ],

    'footer' => [
        'rights' => '© :year Qreach. All rights reserved.',
        'note' => 'Public page — no sign-in required.',
    ],

    'portal' => [
        'title' => 'Voucher batch tracker',
        'meta' => 'See how many vouchers of your batch were activated and how many are left.',
        'intro' => 'Enter the batch number and access code you received from Qreach to see how many vouchers were activated and how many remain.',
        'batch' => 'Batch number',
        'batch_hint' => 'Example: VB-20261012-001',
        'code' => 'Access code',
        'code_hint' => 'Eight letters and digits',
        'submit' => 'Show batch',
        'wrong' => 'The batch number or access code is not correct. Check them and try again.',
        'locked' => 'Too many incorrect attempts. Try again in an hour.',
        'throttled' => 'Too many attempts in a short time. Wait a minute and try again.',
        'result_title' => 'Batch :batch',
        'plan' => 'Plan',
        'duration' => 'Activation length',
        'days' => ':days days',
        'created' => 'Batch date',
        'valid_until' => 'Valid until',
        'no_expiry' => 'No expiry date',
        'total' => 'Total vouchers',
        'activated' => 'Activated',
        'remaining' => 'Remaining',
        'revoked' => 'Revoked',
        'expired' => 'Expired',
        'progress' => ':percent% of the batch is activated',
        'last' => 'Last activation',
        'none_yet' => 'No voucher activated yet',
        'daily' => 'Activations in the last 14 days',
        'date' => 'Date',
        'count' => 'Count',
        'privacy' => 'For privacy, voucher numbers and the people who activated them are not shown here.',
        'again' => 'Look up another batch',
    ],
];
