<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription_log;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GenerateInvoices extends Command
{
    protected $signature = 'invoices:generate';
    protected $description = 'Generate invoices for subscriptions';

    public function handle()
    {
        $log = Subscription_log::all();

        // \Log::info($log);
        foreach ($log as $i) {
            $totaltime = '';
            $invoice = Invoice::where('user_id', '=', $i->company_id)->latest()->first();

            if ($invoice) {
                $package = Subscription::find($i->subscription_id);
                if ($package->duration != 'Unlimited') {
                    $originalDateTime = $invoice->created_at;


                    $apiResponseDuration = $package->duration;

                    $matches = [];
                    if (preg_match('/(\d+) Month/', $apiResponseDuration, $matches)) {
                        $packageDuration = (int)$matches[1];
                    } else {
                        // \Log::info("Error: Unable to extract package duration from API response.");
                        exit;
                    }

                    // Convert the original date string to a DateTime object
                    $dateTime = new \DateTime($originalDateTime);

                    // Add the package duration to the date
                    $dateTime->add(new \DateInterval("P{$packageDuration}M"));

                    // Get the new date and time as a string
                    $newDateTime = $dateTime->format("Y-m-d H:i:s");
                    // \Log::info($newDateTime);


                    if ($newDateTime < Carbon::now()) {
                        // \Log::info($newDateTime . ' ' . Carbon::now());
                        $discount = Setting::where('type', '=', 'Discount')->first();
                        $GST = Setting::where('type', '=', 'GST')->first();

                        
                        $total_discount = (float)$discount->message / 100;
                        $discount_price = (float)$package->price *  $total_discount;
                        $after_discount = (float)$package->price -  $discount_price;

                        $total_GST = (float)$GST->message / 100;
                        $GST_price = (float)$after_discount *  $total_GST;
                        $after_total = (float)$after_discount +  $GST_price;
                        // \Log::info($after_total);


                        $invoice = Invoice::create([
                            'subscription_id' => $package->id,
                            'package_amount' => $package->amount,
                            'gst' => $GST->message,
                            'discount' => $discount->message,
                            'status' => 'Pending',
                            'user_id' => $i->company_id,
                            'total' => $after_total
                        ]);

                        $user = User::where('company_id', $i->company_id)->get();
                        if ($user->count() > 0) {
                            foreach ($user as $u) {
                                $u->status = 'Inactive';
                                $u->save();
                            }
                        }
                    } else {
                        // Log::info('have time');
                    }
                }
            }
        }
    }
}
