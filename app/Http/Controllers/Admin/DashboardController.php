<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractStatusEnum;
use App\Enums\JobStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\SettingKeyEnum;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\User;
use App\Models\Client;
use App\Models\Order;
use App\Models\WorkerLeads;
use App\Models\Offer;
use App\Models\Schedule;
use App\Models\Contract;
use App\Models\Notification;
use App\Models\Admin;
use App\Models\ManageTime;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Traits\PriceOffered;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
  use PriceOffered;

  // public function dashboard()
  // {
  //   $today = Carbon::now()->toDateString();
  //   $todayDateTime = Carbon::now()->toDateTimeString();

  //   $total_jobs      = Job::whereDate('start_date', $today)->count();
  //   $total_new_clients   = Client::where('created_at', $todayDateTime)->count();
  //   $total_active_clients   = Client::where('status', 2)->count();
  //   $total_leads   = Client::where('status', '!=', 2)->count();
  //   $total_workers   = User::where(function ($q)  use ($today) {
  //     $q
  //       ->whereNull('last_work_date')
  //       ->orWhereDate('last_work_date', '>=', $today);
  //   })->count();
  //   $total_schedules  = Schedule::whereDate('start_date', $today)->count();
  //   $total_offers    = Offer::where('status', 'sent')->count();
  //   $total_worker_leads = WorkerLeads::all()->count();
  //   $total_contracts  = Contract::where('status', '!=', ContractStatusEnum::VERIFIED)->count();
  //   $latest_jobs     = Job::query()
  //     ->with(['client', 'service', 'worker', 'jobservice'])
  //     ->where('status', JobStatusEnum::COMPLETED)
  //     ->orderBy('created_at', 'desc')
  //     ->paginate(5);

  //   return response()->json([
  //     'total_jobs'         => $total_jobs,
  //     'total_new_clients'     => $total_new_clients,
  //     'total_active_clients'  => $total_active_clients,
  //     'total_leads'        => $total_leads,
  //     'total_workers'      => $total_workers,
  //     'total_worker_leads' => $total_worker_leads,
  //     'total_schedules'    => $total_schedules,
  //     'total_offers'       => $total_offers,
  //     'total_contracts'    => $total_contracts,
  //     'latest_jobs'        => $latest_jobs,
  //   ]);
  // }
  public function dashboard(Request $request)
  {
      $filterType = $request->input('filter', 'today');
      $today = Carbon::today();
      $startDate = $endDate = null;
  
      switch ($filterType) {
          case 'today':
              $startDate = $today->copy()->startOfDay();
              $endDate = $today->copy()->endOfDay();
              break;
          case 'this_week':
              $startDate = Carbon::now()->startOfWeek();
              $endDate = Carbon::now()->endOfWeek();
              break;
          case 'this_month':
              $startDate = Carbon::now()->startOfMonth();
              $endDate = Carbon::now()->endOfMonth();
              break;
          case 'custom':
              $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
              $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
              break;
          case 'all_time':
              $startDate = $endDate = null; // Show all
              break;
      }
  
      $total_jobs = Job::when($startDate && $endDate, fn($q) => $q->whereBetween('start_date', [$startDate, $endDate]))->count();
  
      $total_new_clients = Client::when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))->count();
  
      $total_new_workers = User::when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))->count();
  
      $total_active_clients = Client::where('status', 2)
          ->whereHas('lead_status', function ($query) {
              $query->where('lead_status', "active client");
          })
          ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
            return $query->whereBetween('created_at', [$startDate, $endDate]);
          })->count();
  
      $total_leads = Client::where('status', '!=', 2)
          ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
              return $query->whereBetween('created_at', [$startDate, $endDate]);
          })
          ->count();

        
      $total_order_price = Order::when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))->sum('amount_with_tax');
      $total_paid_order_price = Order::where('paid_status', 'paid')->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))->sum('amount_with_tax');
      $total_unpaid_order_price = Order::where('paid_status', 'unpaid')->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))->sum('amount_with_tax');
          \Log::info($total_order_price);
          \Log::info($total_paid_order_price);
          \Log::info($total_unpaid_order_price);

      $total_workers = User::where(function ($q) use ($today) {
          $q->whereNull('last_work_date')
            ->orWhereDate('last_work_date', '>=', $today);
      })->count();
  
      $total_schedules = Schedule::when($startDate && $endDate, fn($q) => $q->whereBetween('start_date', [$startDate, $endDate]))->count();
  
      $total_offers = Offer::where('status', 'sent')
          ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
            return $q->whereBetween('created_at', [$startDate, $endDate]);
          })->count();
  
      $total_worker_leads = WorkerLeads::when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))->count();
  
      $total_contracts = Contract::where('status', '!=', ContractStatusEnum::VERIFIED)
          ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
            return $q->whereBetween('created_at', [$startDate, $endDate]);
          })->count();
  
      $latest_jobs = Job::with(['client', 'service', 'worker', 'jobservice'])
          ->where('status', JobStatusEnum::COMPLETED)
          ->when($startDate && $endDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
          ->orderBy('created_at', 'desc')
          ->paginate(5);
  
      return response()->json([
          'total_jobs' => $total_jobs,
          'total_new_clients' => $total_new_clients,
          'total_new_workers' => $total_new_workers,
          'total_active_clients' => $total_active_clients,
          'total_leads' => $total_leads,
          'total_workers' => $total_workers,
          'total_worker_leads' => $total_worker_leads,
          'total_schedules' => $total_schedules,
          'total_offers' => $total_offers,
          'total_contracts' => $total_contracts,
          'latest_jobs' => $latest_jobs,
      ]);
  }
  

  public function updateTime(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'start_time' => 'required',
      'end_time'  => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->messages()]);
    }

    ManageTime::where('id', 1)->update([
      'start_time' => $request->start_time,
      'end_time'   => $request->end_time,
      'days'       => $request->days,
    ]);

    return response()->json(['message' => 'Time update successfully']);
  }

  public function getTime()
  {
    return response()->json([
      'data' => ManageTime::where('id', 1)->first()
    ]);
  }

  public function Notice(Request $request)
  {
    $groupType = $request->get('group_type');

    $count = Notification::count();
    $seenCount = Notification::where('seen', 0)->count();

    if ($count > 0) {
      if ($request->head) {
        $noticeAll = Notification::with('client')->orderBy('id', 'desc')->take(5)->get();
      }

      if ($request->all) {
        $noticeAll = Notification::with('client')
          ->when($groupType == 'payment-status', function ($q) {
            return $q->whereIn('type', [
              NotificationTypeEnum::PAYMENT_FAILED,
              NotificationTypeEnum::PAYMENT_PAID,
              NotificationTypeEnum::PAYMENT_PARTIAL_PAID,
              NotificationTypeEnum::ORDER_CANCELLED,
              NotificationTypeEnum::CLIENT_INVOICE_CREATED_AND_SENT_TO_PAY,
              NotificationTypeEnum::CLIENT_INVOICE_PAID_CREATED_RECEIPT,
              NotificationTypeEnum::ORDER_CREATED_WITH_EXTRA,
              NotificationTypeEnum::ORDER_CREATED_WITH_DISCOUNT,
            ]);
          })
          ->when($groupType == 'changes-and-cancellation', function ($q) {
            return $q->whereIn('type', [
              NotificationTypeEnum::JOB_SCHEDULE_CHANGE,
              NotificationTypeEnum::OPENING_JOB,
              NotificationTypeEnum::CLIENT_CANCEL_JOB,
              NotificationTypeEnum::WORKER_RESCHEDULE,
              NotificationTypeEnum::CLIENT_CHANGED_JOB_SCHEDULE,
              NotificationTypeEnum::WORKER_CHANGED_AVAILABILITY_AFFECT_JOB,
              NotificationTypeEnum::WORKER_LEAVES_JOB,
            ]);
          })
          ->when($groupType == 'lead-client', function ($q) {
            return $q->whereIn('type', [
              NotificationTypeEnum::CONVERTED_TO_CLIENT,
              NotificationTypeEnum::SENT_MEETING,
              NotificationTypeEnum::ACCEPT_MEETING,
              NotificationTypeEnum::REJECT_MEETING,
              NotificationTypeEnum::ACCEPT_OFFER,
              NotificationTypeEnum::REJECT_OFFER,
              NotificationTypeEnum::CONTRACT_ACCEPT,
              NotificationTypeEnum::CONTRACT_REJECT,
              NotificationTypeEnum::RESCHEDULE_MEETING,
              NotificationTypeEnum::FILES,
              NotificationTypeEnum::CLIENT_LEAD_STATUS_CHANGED,
              NotificationTypeEnum::NEW_LEAD_ARRIVED,
              NotificationTypeEnum::FOLLOW_UP_REQUIRED,
              NotificationTypeEnum::FOLLOW_UP_PRICE_OFFER,
              NotificationTypeEnum::FINAL_FOLLOW_UP_PRICE_OFFER,
              NotificationTypeEnum::LEAD_ACCEPTED_PRICE_OFFER,
              NotificationTypeEnum::BOOK_CLIENT_AFTER_SIGNED_CONTRACT,
              NotificationTypeEnum::LEAD_DECLINED_PRICE_OFFER,
              NotificationTypeEnum::FILE_SUBMISSION_REQUEST,
              NotificationTypeEnum::LEAD_DECLINED_CONTRACT,
              NotificationTypeEnum::CLIENT_IN_FREEZE_STATUS,
              NotificationTypeEnum::STATUS_NOT_UPDATED,
              NotificationTypeEnum::CLIENT_LEAD_STATUS_CHANGED,
            ]);
          })
          ->when($groupType == 'worker-forms', function ($q) {
            return $q->whereIn('type', [
              NotificationTypeEnum::FORM101_SIGNED,
              NotificationTypeEnum::INSURANCE_SIGNED,
              NotificationTypeEnum::WORKER_CONTRACT_SIGNED,
              NotificationTypeEnum::SAFETY_GEAR_SIGNED,
            ]);
          })
          ->when($groupType == 'reviews-of-clients', function ($q) {
            return $q->whereIn('type', [
              NotificationTypeEnum::CLIENT_COMMENTED,
              NotificationTypeEnum::ADMIN_COMMENTED,
              NotificationTypeEnum::WORKER_COMMENTED,
              NotificationTypeEnum::CLIENT_REVIEWED,
            ]);
          })
          ->when($groupType == 'problem-with-workers', function ($q) {
            return $q->whereIn('type', [
              NotificationTypeEnum::WORKER_NOT_APPROVED_JOB,
              NotificationTypeEnum::WORKER_NOT_LEFT_FOR_JOB,
              NotificationTypeEnum::WORKER_NOT_STARTED_JOB,
              NotificationTypeEnum::WORKER_NOT_FINISHED_JOB_ON_TIME,
              NotificationTypeEnum::WORKER_EXCEED_JOB_TIME,
            ]);
          })
          ->orderBy('id', 'desc')
          ->paginate(15);
      }

      if (isset($noticeAll)) {
        foreach ($noticeAll as $k => $notice) {
          if ($notice->type == NotificationTypeEnum::SENT_MEETING) {
            $sch = Schedule::with('client')->where('id', $notice->meet_id)->first();

            if (isset($sch)) {
              $noticeAll[$k]->data = "<a href='/admin/schedule/view/" . $sch->client->id . "?sid=" . $sch->id . "'> Meeting </a> scheduled with <a href='/admin/clients/view/" . $sch->client->id . "'>" . $sch->client->firstname . " " . $sch->client->lastname .
                "</a> on " . Carbon::parse($sch->start_date)->format('d-m-Y') . " at " . ($sch->start_time);
            }
          } else if ($notice->type == NotificationTypeEnum::RESCHEDULE_MEETING) {
            $sch = Schedule::with('client')->where('id', $notice->meet_id)->first();

            if (isset($sch)) {
              $noticeAll[$k]->data = "<a href='/admin/schedule/view/" . $sch->client->id . "?sid=" . $sch->id . "'> Meeting </a> Re-scheduled with <a href='/admin/clients/view/" . $sch->client->id . "'>" . $sch->client->firstname . " " . $sch->client->lastname .
                "</a> on " . Carbon::parse($sch->start_date)->format('d-m-Y') . " at " . ($sch->start_time);
            }
          } else if ($notice->type == NotificationTypeEnum::ACCEPT_MEETING) {
            $sch = Schedule::with('client')->where('id', $notice->meet_id)->first();

            if (isset($sch)) {
              $noticeAll[$k]->data = "<a href='/admin/schedule/view/" . $notice->user->id . "?sid=" . $sch->id . "'> Meeting </a> with <a href='/admin/clients/view/" . $sch->client->id . "'>" . $sch->client->firstname . " " . $sch->client->lastname .
                "</a> has been confirmed now on " . Carbon::parse($sch->start_date)->format('d-m-Y')  . " at " . ($sch->start_time);
            }
          } else if ($notice->type == NotificationTypeEnum::REJECT_MEETING) {
            $sch = Schedule::with('client')->where('id', $notice->meet_id)->first();

            if (isset($sch)) {
              $noticeAll[$k]->data = "<a href='/admin/schedule/view/" . $notice->meet_id . "?sid=" . $sch->id . "'> Meeting </a> with <a href='/admin/clients/view/" . $sch->client->id . "'>" . $sch->client->firstname . " " . $sch->client->lastname .
                "</a> which on " . Carbon::parse($sch->start_date)->format('d-m-Y')  . " at " . ($sch->start_time) . " has cancelled now.";
            }
          } else if ($notice->type == NotificationTypeEnum::FILES) {
            $sch = Schedule::with('client')->where('id', $notice->meet_id)->first();

            if (isset($sch)) {
              $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $sch->client->id . "'>" . $sch->client->firstname . " " . $sch->client->lastname .
                "</a> have added a file to <a href='/admin/schedule/view/" . $notice->meet_id . "?sid=" . $sch->id . "'> Meeting </a> scheduled on " . Carbon::parse($sch->start_date)->format('d-m-Y')  . " at " . ($sch->start_time);
            }
          } else if ($notice->type == NotificationTypeEnum::ACCEPT_OFFER) {
            $ofr = Offer::with('client')->where('id', $notice->offer_id)->first();

            if (isset($ofr)) {
              $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $ofr->client->id . "'>" . $ofr->client->firstname . " " . $ofr->client->lastname .
                "</a> has accepted the <a href='/admin/view-offer/" . $notice->offer_id . "'> price offer </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::REJECT_OFFER) {
            $ofr = Offer::with('client')->where('id', $notice->offer_id)->first();

            if (isset($ofr)) {
              $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $ofr->client->id . "'>" . $ofr->client->firstname . " " . $ofr->client->lastname .
                "</a> has rejected <a href='/admin/view-offer/" . $notice->offer_id . "'>the price offer </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::CONTRACT_ACCEPT) {
            $contract = Contract::with('offer', 'client')->where('id', $notice->contract_id)->first();

            if (isset($contract)) {
              $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $contract->client->id . "'>" . $contract->client->firstname . " " . $contract->client->lastname .
                "</a> has approved the <a href='/admin/view-contract/" . $contract->id . "'> contract </a>";
              if ($contract->offer) {
                $noticeAll[$k]->data .= "for <a href='/admin/view-offer/" . $contract->offer->id . "'> offer</a>";
              }
            }
          } else if ($notice->type == NotificationTypeEnum::CONTRACT_REJECT) {
            $contract = Contract::with('offer', 'client')->where('id', $notice->contract_id)->first();

            if (isset($contract)) {
              $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $contract->client->id . "'>" . $contract->client->firstname . " " . $contract->client->lastname .
                "</a> has rejected the <a href='/admin/view-contract/" . $contract->id . "'> contract </a>";
              if ($contract->offer) {
                $noticeAll[$k]->data .= "for <a href='/admin/view-offer/" . $contract->offer->id . "'> offer</a>";
              }
            }
          } else if ($notice->type == NotificationTypeEnum::CLIENT_CANCEL_JOB) {
            $job = Job::with('offer', 'client')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $job->client->id . "'>" . $job->client->firstname . " " . $job->client->lastname .
                "</a> has cancelled the  <a href='/admin/jobs/view/" . $job->id . "'> job </a>";
              if ($job->offer) {
                $noticeAll[$k]->data .= "for <a href='/admin/view-offer/" . $job->offer->id . "'> offer </a> ";
              }
            }
          } else if ($notice->type == NotificationTypeEnum::WORKER_RESCHEDULE) {
            $job = Job::with('offer', 'worker')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/workers/view/" . $job->worker->id . "'>" . $job->worker->firstname . " " . $job->worker->lastname .
                "</a> request for reschedule the  <a href='/admin/jobs/view/" . $job->id . "'> job </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::OPENING_JOB) {
            $job = Job::with('offer', 'worker')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/jobs/view/" . $job->id . "'> Job </a> has been started by  <a href='/admin/workers/view/" . $job->worker->id . "'>" . $job->worker->firstname . " " . $job->worker->lastname .
                "</a>";
            }
          } else if ($notice->type == NotificationTypeEnum::JOB_SCHEDULE_CHANGE) {
            $job = Job::with('offer', 'worker')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/jobs/view/" . $job->id . "'> Job </a> schedule has been changed";
            }
          } else if ($notice->type == NotificationTypeEnum::FORM101_SIGNED) {
            $noticeAll[$k]->data = "Form 101 has been signed by <a href='/admin/workers/view/" . ($notice->user->id ?? $notice->id) . "'>" . ($notice->user->firstname ?? $notice->firstname) . " " . ($notice->user->lastname ?? $notice->lastname) .
              "</a>";
          } else if ($notice->type == NotificationTypeEnum::WORKER_CONTRACT_SIGNED) {
            $noticeAll[$k]->data = "Contract form has been signed by <a href='/admin/workers/view/" . ($notice->user->id ?? $notice->id) . "'>" . ($notice->user->firstname ?? $notice->firstname) . " " . ($notice->user->lastname ?? $notice->lastname) .
              "</a>";
          } else if ($notice->type == NotificationTypeEnum::SAFETY_GEAR_SIGNED) {
            $noticeAll[$k]->data = "Safety and Gear form has been signed by <a href='/admin/workers/view/" . ($notice->user->id ?? $notice->id) . "'>" . ($notice->user->firstname ?? $notice->firstname) . " " . ($notice->user->lastname ?? $notice->lastname) .
              "</a>";
          } else if ($notice->type == NotificationTypeEnum::INSURANCE_SIGNED) {
            $noticeAll[$k]->data = "Insurance form has been signed by <a href='/admin/workers/view/" . ($notice->user->id ?? $notice->id) . "'>" . ($notice->user->firstname ?? $notice->firstname) . " " . ($notice->user->lastname ?? $notice->lastname) .
              "</a>";
          } else if ($notice->type == NotificationTypeEnum::CLIENT_REVIEWED) {
            $job = Job::with('offer', 'worker')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "Client has reviewed a <a href='/admin/jobs/view/" . $job->id . "'>Job </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::CONVERTED_TO_CLIENT) {
            $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $notice->user->id . "'>" . $notice->user->firstname . " " . $notice->user->lastname .
              "</a> have been converted to client";
          } else if ($notice->type == NotificationTypeEnum::PAYMENT_FAILED) {
            $noticeAll[$k]->data = "Payment with <a href='/admin/clients/view/" . $notice->client->id . "'>" . $notice->client->firstname . " " . $notice->client->lastname .
              "</a> has been failed";
          } else if ($notice->type == NotificationTypeEnum::PAYMENT_PAID) {
            $noticeAll[$k]->data = "Payment with <a href='/admin/clients/view/" . $notice->client->id . "'>" . $notice->client->firstname . " " . $notice->client->lastname .
              "</a> has been paid";
          } else if ($notice->type == NotificationTypeEnum::PAYMENT_PARTIAL_PAID) {
            $noticeAll[$k]->data = "Payment with <a href='/admin/clients/view/" . $notice->client->id . "'>" . $notice->client->firstname . " " . $notice->client->lastname .
              "</a> has been paid partially";
          } else if ($notice->type == NotificationTypeEnum::WORKER_NOT_APPROVED_JOB) {
            $job = Job::with('worker')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/workers/view/" . $job->worker->id . "'>" . $job->worker->firstname . " " . $job->worker->lastname .
                "</a> hasn't approved the <a href='/admin/jobs/view/" . $job->id . "'>Job </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::WORKER_NOT_LEFT_FOR_JOB) {
            $job = Job::with('worker')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/workers/view/" . $job->worker->id . "'>" . $job->worker->firstname . " " . $job->worker->lastname .
                "</a> hasn't leave for the <a href='/admin/jobs/view/" . $job->id . "'>Job </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::WORKER_NOT_STARTED_JOB) {
            $job = Job::with('worker')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/workers/view/" . $job->worker->id . "'>" . $job->worker->firstname . " " . $job->worker->lastname .
                "</a> hasn't started the <a href='/admin/jobs/view/" . $job->id . "'>Job </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::WORKER_NOT_FINISHED_JOB_ON_TIME) {
            $job = Job::with('worker')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/workers/view/" . $job->worker->id . "'>" . $job->worker->firstname . " " . $job->worker->lastname .
                "</a> hasn't finished the <a href='/admin/jobs/view/" . $job->id . "'>Job </a> on time";
            }
          } else if ($notice->type == NotificationTypeEnum::WORKER_EXCEED_JOB_TIME) {
            $job = Job::with('worker')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/workers/view/" . $job->worker->id . "'>" . $job->worker->firstname . " " . $job->worker->lastname .
                "</a> exceeded the <a href='/admin/jobs/view/" . $job->id . "'>Job </a> time";
            }
          } else if ($notice->type == NotificationTypeEnum::CLIENT_COMMENTED) {
            $job = Job::where('id', $notice->job_id)->first();
            $client = Client::find($notice->user_id);

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $client->id . "'>" . $client->firstname . " " . $client->lastname .
                "</a> has added commented to the <a href='/admin/jobs/view/" . $job->id . "'>Job </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::ADMIN_COMMENTED) {
            $job = Job::where('id', $notice->job_id)->first();
            $admin = Admin::find($notice->user_id);

            if (isset($job)) {
              $noticeAll[$k]->data = $admin->name . " has added commented to the <a href='/admin/jobs/view/" . $job->id . "'>Job </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::WORKER_COMMENTED) {
            $job = Job::where('id', $notice->job_id)->first();
            $worker = User::find($notice->user_id);

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/workers/view/" . $worker->id . "'>" . $worker->firstname . " " . $worker->lastname .
                "</a> has added commented to the <a href='/admin/jobs/view/" . $job->id . "'>Job </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::NEW_LEAD_ARRIVED) {
            $client = Client::find($notice->user_id);

            if (isset($client)) {
              $noticeAll[$k]->data = "New lead <a href='/admin/clients/view/" . $client->id . "'>" . $client->firstname . " " . $client->lastname .
                "</a> has been added.";
            }
          } else if ($notice->type == NotificationTypeEnum::CLIENT_LEAD_STATUS_CHANGED) {
            $client = Client::find($notice->user_id);

            if (isset($client)) {
              $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $client->id . "'>" . $client->firstname . " " . $client->lastname .
                "</a> lead status has been changed to " . $notice->data['new_status'] . ".";
            }
          } else if ($notice->type == NotificationTypeEnum::CLIENT_CHANGED_JOB_SCHEDULE) {
            $job = Job::where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "Client has changed schedule of <a href='/admin/jobs/view/" . $job->id . "'>Job </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::WORKER_CHANGED_AVAILABILITY_AFFECT_JOB) {
            $worker = User::find($notice->user_id);

            if (isset($worker)) {
              $noticeAll[$k]->data = "<a href='/admin/workers/view/" . $worker->id . "'>" . $worker->firstname . " " . $worker->lastname .
                "</a> availability has been changed that affect job on " . (isset($notice->data['date']) ? $notice->data['date'] : $notice->data['last_work_date']) . '.';
            }
          } else if ($notice->type == NotificationTypeEnum::WORKER_LEAVES_JOB) {
            $worker = User::find($notice->user_id);

            if (isset($worker)) {
              $noticeAll[$k]->data = "<a href='/admin/workers/view/" . $worker->id . "'>" . $worker->firstname . " " . $worker->lastname .
                "</a> leave date has been set to " . (isset($notice->data['date']) ? $notice->data['date'] : $notice->data['last_work_date']) . '.';
            }
          } else if ($notice->type == NotificationTypeEnum::ORDER_CANCELLED) {
            $noticeAll[$k]->data = "Payment with <a href='/admin/clients/view/" . $notice->user->id . "'>" . $notice->user->firstname . " " . $notice->user->lastname .
              "</a>'s order (" . $notice->data['order_id'] . ") has been cancelled.";
          } else if ($notice->type == NotificationTypeEnum::CLIENT_INVOICE_CREATED_AND_SENT_TO_PAY) {
            $noticeAll[$k]->data = "Invoice (" . $notice->data['invoice_id'] . ") has been created for <a href='/admin/clients/view/" . $notice->user->id . "'>" . $notice->user->firstname . " " . $notice->user->lastname .
              "</a>.";
          } else if ($notice->type == NotificationTypeEnum::CLIENT_INVOICE_PAID_CREATED_RECEIPT) {
            $noticeAll[$k]->data = "Invoice Receipt (" . $notice->data['invoice_id'] . ") has been created for <a href='/admin/clients/view/" . $notice->user->id . "'>" . $notice->user->firstname . " " . $notice->user->lastname .
              "</a>.";
          } else if ($notice->type == NotificationTypeEnum::ORDER_CREATED_WITH_EXTRA) {
            $noticeAll[$k]->data = "Order (" . $notice->data['order_id'] . ") has been created for <a href='/admin/clients/view/" . $notice->user->id . "'>" . $notice->user->firstname . " " . $notice->user->lastname .
              "</a> with extra.";
          } else if ($notice->type == NotificationTypeEnum::ORDER_CREATED_WITH_DISCOUNT) {
            $noticeAll[$k]->data = "Order (" . $notice->data['order_id'] . ") has been created for <a href='/admin/clients/view/" . $notice->user->id . "'>" . $notice->user->firstname . " " . $notice->user->lastname .
              "</a> with discount.";
          } else if ($notice->type == NotificationTypeEnum::UNANSWERED_LEAD) {
            $client = Client::find($notice->user_id);

            if (isset($client)) {
              $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $client->id . "'>" . $client->firstname . " " . $client->lastname .
                "</a> has unanswered leads for 24 hours or more than 24 hours.";
            }
          } else if ($notice->type == NotificationTypeEnum::FOLLOW_UP_PRICE_OFFER) {
            $client = Client::find($notice->user_id);

            if (isset($client)) {
              $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $client->id . "'>" . $client->firstname . " " . $client->lastname .
                "</a> has a follow-up price offer after 3 days.";
            }
          } else if ($notice->type == NotificationTypeEnum::FINAL_FOLLOW_UP_PRICE_OFFER) {
            $client = Client::find($notice->user_id);

            if (isset($client)) {
              $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $client->id . "'>" . $client->firstname . " " . $client->lastname .
                "</a> has a final follow-up price offer after 7 days.";
            }
          } else if ($notice->type == NotificationTypeEnum::LEAD_ACCEPTED_PRICE_OFFER) {
            $client = Client::find($notice->user_id);

            if (isset($client)) {
              $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $client->id . "'>" . $client->firstname . " " . $client->lastname .
                "</a> has accepted the price offer.";
            }
          } else if ($notice->type == NotificationTypeEnum::LEAD_DECLINED_PRICE_OFFER) {
            $client = Client::find($notice->user_id);

            if (isset($client)) {
              $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $client->id . "'>" . $client->firstname . " " . $client->lastname .
                "</a> has rejected the price offer.";
            }
          } else if ($notice->type == NotificationTypeEnum::LEAD_DECLINED_CONTRACT) {
            $client = Client::find($notice->user_id);

            if (isset($client)) {
              $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $client->id . "'>" . $client->firstname . " " . $client->lastname .
                "</a> has declined the contract.";
            }
          } else if ($notice->type == NotificationTypeEnum::BOOK_CLIENT_AFTER_SIGNED_CONTRACT) {
            $client = Client::find($notice->user_id);

            if (isset($client)) {
              $noticeAll[$k]->data = "<a href='/admin/clients/view/" . $client->id . "'>" . $client->firstname . " " . $client->lastname .
                "</a> has signed the contract and is ready to be booked.";
            }
          }
        }
      }

      return response()->json([
        'notice' => $noticeAll,
        'count' => $seenCount
      ]);
    } else {
      return response()->json([
        'notice' => []
      ]);
    }
  }

  public function viewPass(Request $request)
  {
    $user = Admin::find(Auth::id());

    $response = Hash::check($request->pass, $user->password);
    return response()->json([
      'response' => $response
    ]);
  }

  public function seen()
  {
    Notification::where('seen', 0)->update(['seen' => 1]);
  }

  public function clearNotices()
  {
    Notification::truncate();
  }

  public function income(Request $request)
  {
    $start_date = $request->get('start_date');
    $end_date = $request->get('end_date');

    $data = Job::query()
    ->leftJoin('job_services', 'job_services.job_id', '=', 'jobs.id')
    ->where('jobs.status', JobStatusEnum::COMPLETED)
    ->when($start_date && $end_date, function ($q) use ($start_date, $end_date) {
      return $q->whereBetween('jobs.created_at', [$start_date, $end_date]);;
    })
    ->selectRaw('SUM(jobs.subtotal_amount) as income')
    ->selectRaw('SUM(jobs.actual_time_taken_minutes) as actual_time_taken_minutes')
    ->selectRaw('SUM(job_services.duration_minutes) as duration_minutes')
    ->selectRaw('SUM(jobs.actual_time_taken_minutes - job_services.duration_minutes) as difference_minutes')
    ->selectRaw('COUNT(jobs.id) as total_jobs')
    ->first();

    $graph = [];
    if ($start_date && $end_date) {
      $iCountCompanyID = Setting::query()
        ->where('key', SettingKeyEnum::ICOUNT_COMPANY_ID)
        ->value('value');

      $iCountUsername = Setting::query()
        ->where('key', SettingKeyEnum::ICOUNT_USERNAME)
        ->value('value');

      $iCountPassword = Setting::query()
        ->where('key', SettingKeyEnum::ICOUNT_PASSWORD)
        ->value('value');

      $url = 'https://api.icount.co.il/api/v3.php/chart/monthly_profitability';

      $postData = [
        'cid' => $iCountCompanyID,
        'user' => $iCountUsername,
        'pass' => $iCountPassword,
      ];

      if(isset($start_date) && isset($end_date)) {
        $postData['start_date'] = $start_date;
        $postData['end_date'] = $end_date;
      }

      $response = Http::post($url, $postData);

      $json = $response->json();

      if (isset($json['status']) && $json['status'] == true) {
        $graph['labels'] = $json['monthly_profitability']['labels'];
        $graph['data'] = $json['monthly_profitability']['data'];
      }
    }

    return response()->json([
      'graph' => $graph,
      'data' => $data
    ]);
  }

  public function pendingData($for)
  {
    if ($for == 'meetings') {
      $meetings = Schedule::where('booking_status', 'pending')->with('client', 'team', 'propertyAddress')->paginate(5);
      return response()->json([
        'data' => $meetings,
      ]);
    }

    if ($for == 'offers') {
      $offers = Offer::where('status', 'sent')->with('client', 'service')
        ->paginate(5)
        ->through(function ($item) {
          $item->services = $this->formatServices($item);
          return $item;
        });
      return response()->json([
        'data' => $offers,
      ]);
    }

    if ($for == 'contracts') {
      $contracts = Contract::query()
        ->with(['client', 'offer'])
        ->where('status', ContractStatusEnum::UN_VERIFIED)
        ->orWhere('status', ContractStatusEnum::NOT_SIGNED)
        ->paginate(5)
        ->through(function ($item) {
          $item->offer->services = $this->formatServices($item->offer);
          return $item;
        });
      return response()->json([
        'data' => $contracts,
      ]);
    }
  }
}
