<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionManager;
use Illuminate\View\View;

/** The public marketing page (Arabic, English, Kurdish). Prices and plan texts come from the dashboard. */
class LandingController extends Controller
{
    public function __invoke(SubscriptionManager $subscriptions): View
    {
        $plans = SubscriptionPlan::where('is_active', true)->get()->keyBy('slug');

        return view('landing', [
            'basic' => $plans->get(SubscriptionPlan::BASIC),
            'plus' => $plans->get(SubscriptionPlan::PLUS),
            'trialDays' => $subscriptions->trialDays(),
        ]);
    }
}
