<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractStatusEnum;
use App\Enums\JobStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\User;
use App\Models\Client;
use App\Models\Offer;
use App\Models\Schedule;
use App\Models\Contract;
use App\Models\Notification;
use App\Models\Admin;
use App\Models\ManageTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Traits\PriceOffered;

class DashboardController extends Controller
{
  use PriceOffered;

  public function dashboard()
  {
    $today = Carbon::now()->toDateString();
    $todayDateTime = Carbon::now()->toDateTimeString();

    $total_jobs      = Job::whereDate('start_date', $today)->count();
    $total_new_clients   = Client::where('created_at', $todayDateTime)->count();
    $total_active_clients   = Client::where('status', 2)->count();
    $total_leads   = Client::where('status', '!=', 2)->count();
    $total_workers   = User::where(function ($q)  use($today){
      $q
          ->whereNull('last_work_date')
          ->orWhereDate('last_work_date', '>=', $today);
    })->count();
    $total_schedules  = Schedule::whereDate('start_date', $today)->count();
    $total_offers    = Offer::where('status', 'sent')->count();
    $total_contracts  = Contract::where('status', '!=', ContractStatusEnum::VERIFIED)->count();
    $latest_jobs     = Job::query()
      ->with(['client', 'service', 'worker', 'jobservice'])
      ->where('status', JobStatusEnum::COMPLETED)
      ->orderBy('created_at', 'desc')
      ->paginate(5);

    return response()->json([
      'total_jobs'         => $total_jobs,
      'total_new_clients'     => $total_new_clients,
      'total_active_clients'  => $total_active_clients,
      'total_leads'        => $total_leads,
      'total_workers'      => $total_workers,
      'total_schedules'    => $total_schedules,
      'total_offers'       => $total_offers,
      'total_contracts'    => $total_contracts,
      'latest_jobs'        => $latest_jobs,
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
          ->when($groupType == 'schedule-and-worker', function ($q) {
            return $q->whereIn('type', [
              NotificationTypeEnum::FORM101_SIGNED,
              NotificationTypeEnum::INSURANCE_SIGNED,
              NotificationTypeEnum::WORKER_CONTRACT_SIGNED,
              NotificationTypeEnum::SAFETY_GEAR_SIGNED,
              NotificationTypeEnum::JOB_SCHEDULE_CHANGE,
              NotificationTypeEnum::OPENING_JOB,
              NotificationTypeEnum::CLIENT_CANCEL_JOB,
            ]);
          })
          ->when($groupType == 'converted-to-client', function ($q) {
            return $q->where('type', NotificationTypeEnum::CONVERTED_TO_CLIENT);
          })
          ->when($groupType == 'client-comments-reviews', function ($q) {
            return $q->whereIn('type', [
              NotificationTypeEnum::CLIENT_COMMENTED,
              NotificationTypeEnum::JOB_REVIEWED,
            ]);
          })
          ->when($groupType == 'payment-status', function ($q) {
            return $q->whereIn('type', [
              NotificationTypeEnum::PAYMENT_FAILED,
              NotificationTypeEnum::PAYMENT_PAID,
              NotificationTypeEnum::PAYMENT_PARTIAL_PAID,
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
              $noticeAll[$k]->data = "<a href='/admin/view-schedule/" . $sch->client->id . "?sid=" . $sch->id . "'> Meeting </a> scheduled with <a href='/admin/view-client/" . $sch->client->id . "'>" . $sch->client->firstname . " " . $sch->client->lastname .
                "</a> on " . Carbon::parse($sch->start_date)->format('d-m-Y') . " at " . ($sch->start_time);
            }
          } else if ($notice->type == NotificationTypeEnum::RESCHEDULE_MEETING) {
            $sch = Schedule::with('client')->where('id', $notice->meet_id)->first();

            if (isset($sch)) {
              $noticeAll[$k]->data = "<a href='/admin/view-schedule/" . $sch->client->id . "?sid=" . $sch->id . "'> Meeting </a> Re-scheduled with <a href='/admin/view-client/" . $sch->client->id . "'>" . $sch->client->firstname . " " . $sch->client->lastname .
                "</a> on " . Carbon::parse($sch->start_date)->format('d-m-Y') . " at " . ($sch->start_time);
            }
          } else if ($notice->type == NotificationTypeEnum::ACCEPT_MEETING) {
            $sch = Schedule::with('client')->where('id', $notice->meet_id)->first();

            if (isset($sch)) {
              $noticeAll[$k]->data = "<a href='/admin/view-schedule/" . $notice->user->id . "?sid=" . $sch->id . "'> Meeting </a> with <a href='/admin/view-client/" . $sch->client->id . "'>" . $sch->client->firstname . " " . $sch->client->lastname .
                "</a> has been confirmed now on " . Carbon::parse($sch->start_date)->format('d-m-Y')  . " at " . ($sch->start_time);
            }
          } else if ($notice->type == NotificationTypeEnum::REJECT_MEETING) {
            $sch = Schedule::with('client')->where('id', $notice->meet_id)->first();

            if (isset($sch)) {
              $noticeAll[$k]->data = "<a href='/admin/view-schedule/" . $notice->meet_id . "?sid=" . $sch->id . "'> Meeting </a> with <a href='/admin/view-client/" . $sch->client->id . "'>" . $sch->client->firstname . " " . $sch->client->lastname .
                "</a> which on " . Carbon::parse($sch->start_date)->format('d-m-Y')  . " at " . ($sch->start_time) . " has cancelled now.";
            }
          } else if ($notice->type == NotificationTypeEnum::FILES) {
            $sch = Schedule::with('client')->where('id', $notice->meet_id)->first();

            if (isset($sch)) {
              $noticeAll[$k]->data = "<a href='/admin/view-client/" . $sch->client->id . "'>" . $sch->client->firstname . " " . $sch->client->lastname .
                "</a> have added a file to <a href='/admin/view-schedule/" . $notice->meet_id . "?sid=" . $sch->id . "'> Meeting </a> scheduled on " . Carbon::parse($sch->start_date)->format('d-m-Y')  . " at " . ($sch->start_time);
            }
          } else if ($notice->type == NotificationTypeEnum::ACCEPT_OFFER) {
            $ofr = Offer::with('client')->where('id', $notice->offer_id)->first();

            if (isset($ofr)) {
              $noticeAll[$k]->data = "<a href='/admin/view-client/" . $ofr->client->id . "'>" . $ofr->client->firstname . " " . $ofr->client->lastname .
                "</a> has accepted the <a href='/admin/view-offer/" . $notice->offer_id . "'> price offer </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::REJECT_OFFER) {
            $ofr = Offer::with('client')->where('id', $notice->offer_id)->first();

            if (isset($ofr)) {
              $noticeAll[$k]->data = "<a href='/admin/view-client/" . $ofr->client->id . "'>" . $ofr->client->firstname . " " . $ofr->client->lastname .
                "</a> has rejected <a href='/admin/view-offer/" . $notice->offer_id . "'>the price offer </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::CONTRACT_ACCEPT) {
            $contract = Contract::with('offer', 'client')->where('id', $notice->contract_id)->first();

            if (isset($contract)) {
              $noticeAll[$k]->data = "<a href='/admin/view-client/" . $contract->client->id . "'>" . $contract->client->firstname . " " . $contract->client->lastname .
                "</a> has approved the <a href='/admin/view-contract/" . $contract->id . "'> contract </a>";
              if ($contract->offer) {
                $noticeAll[$k]->data .= "for <a href='/admin/view-offer/" . $contract->offer->id . "'> offer</a>";
              }
            }
          } else if ($notice->type == NotificationTypeEnum::CONTRACT_REJECT) {
            $contract = Contract::with('offer', 'client')->where('id', $notice->contract_id)->first();

            if (isset($contract)) {
              $noticeAll[$k]->data = "<a href='/admin/view-client/" . $contract->client->id . "'>" . $contract->client->firstname . " " . $contract->client->lastname .
                "</a> has rejected the <a href='/admin/view-contract/" . $contract->id . "'> contract </a>";
              if ($contract->offer) {
                $noticeAll[$k]->data .= "for <a href='/admin/view-offer/" . $contract->offer->id . "'> offer</a>";
              }
            }
          } else if ($notice->type == NotificationTypeEnum::CLIENT_CANCEL_JOB) {
            $job = Job::with('offer', 'client')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/view-client/" . $job->client->id . "'>" . $job->client->firstname . " " . $job->client->lastname .
                "</a> has cancelled the  <a href='/admin/view-job/" . $job->id . "'> job </a>";
              if ($job->offer) {
                $noticeAll[$k]->data .= "for <a href='/admin/view-offer/" . $job->offer->id . "'> offer </a> ";
              }
            }
          } else if ($notice->type == NotificationTypeEnum::WORKER_RESCHEDULE) {
            $job = Job::with('offer', 'worker')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/view-worker/" . $job->worker->id . "'>" . $job->worker->firstname . " " . $job->worker->lastname .
                "</a> request for reschedule the  <a href='/admin/view-job/" . $job->id . "'> job </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::OPENING_JOB) {
            $job = Job::with('offer', 'worker')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/view-job/" . $job->id . "'> Job </a> has been started by  <a href='/admin/view-worker/" . $job->worker->id . "'>" . $job->worker->firstname . " " . $job->worker->lastname .
                "</a>";
            }
          } else if ($notice->type == NotificationTypeEnum::JOB_SCHEDULE_CHANGE) {
            $job = Job::with('offer', 'worker')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/view-job/" . $job->id . "'> Job </a> schedule has been changed";
            }
          } else if ($notice->type == NotificationTypeEnum::FORM101_SIGNED) {
            $noticeAll[$k]->data = "Form 101 has been signed by <a href='/admin/view-worker/" . $notice->user->id . "'>" . $notice->user->firstname . " " . $notice->user->lastname .
              "</a>";
          } else if ($notice->type == NotificationTypeEnum::WORKER_CONTRACT_SIGNED) {
            $noticeAll[$k]->data = "Contract form has been signed by <a href='/admin/view-worker/" . $notice->user->id . "'>" . $notice->user->firstname . " " . $notice->user->lastname .
              "</a>";
          } else if ($notice->type == NotificationTypeEnum::SAFETY_GEAR_SIGNED) {
            $noticeAll[$k]->data = "Safety and Gear form has been signed by <a href='/admin/view-worker/" . $notice->user->id . "'>" . $notice->user->firstname . " " . $notice->user->lastname .
              "</a>";
          } else if ($notice->type == NotificationTypeEnum::INSURANCE_SIGNED) {
            $noticeAll[$k]->data = "Insurance form has been signed by <a href='/admin/view-worker/" . $notice->user->id . "'>" . $notice->user->firstname . " " . $notice->user->lastname .
              "</a>";
          } else if ($notice->type == NotificationTypeEnum::CLIENT_COMMENTED) {
            $job = Job::with('offer', 'worker')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "Client has comment for a <a href='/admin/view-job/" . $job->id . "'>Job </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::JOB_REVIEWED) {
            $job = Job::with('offer', 'worker')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "Client has reviewed a <a href='/admin/view-job/" . $job->id . "'>Job </a>";
            }
          } else if ($notice->type == NotificationTypeEnum::CONVERTED_TO_CLIENT) {
            $noticeAll[$k]->data = "<a href='/admin/view-client/" . $notice->user->id . "'>" . $notice->user->firstname . " " . $notice->user->lastname .
              "</a> have been converted to client";
          } else if ($notice->type == NotificationTypeEnum::PAYMENT_FAILED) {
            $noticeAll[$k]->data = "Payment with <a href='/admin/view-client/" . $notice->user->id . "'>" . $notice->user->firstname . " " . $notice->user->lastname .
              "</a> has been failed";
          } else if ($notice->type == NotificationTypeEnum::PAYMENT_PAID) {
            $noticeAll[$k]->data = "Payment with <a href='/admin/view-client/" . $notice->user->id . "'>" . $notice->user->firstname . " " . $notice->user->lastname .
              "</a> has been paid";
          } else if ($notice->type == NotificationTypeEnum::PAYMENT_PARTIAL_PAID) {
            $noticeAll[$k]->data = "Payment with <a href='/admin/view-client/" . $notice->user->id . "'>" . $notice->user->firstname . " " . $notice->user->lastname .
              "</a> has been paid partially";
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
    $user = Admin::where('id', $request->id)->first();
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
    $requestData = $request->all();
    $tasks = Job::query()
      ->with(['client', 'worker', 'offer', 'hours'])
      ->where('status', JobStatusEnum::COMPLETED);

    if (isset($requestData['dateRange'])) {
      $startDate = $requestData['dateRange']['start_date'];
      $endDate = $requestData['dateRange']['end_date'];
      $tasks = $tasks->whereBetween('created_at', [$startDate, $endDate])->get();
    } else {
      $tasks = $tasks->get();
    }
    $inc = 0;
    foreach ($tasks as $t1 => $task) {
      if (isset($task->hours)) {
        $tsec = 0;
        foreach ($task->hours as $t => $hour) {
          $tsec += $hour->time_diff;
        }
        $tasks[$t1]->total_sec = $tsec;
      }

      if (isset($task->offer)) {
        $inc += $task->offer->subtotal;
      }
    }

    return response()->json([
      'tasks' => $tasks,
      'total_tasks' => $tasks->count(),
      'income' => $inc,
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
