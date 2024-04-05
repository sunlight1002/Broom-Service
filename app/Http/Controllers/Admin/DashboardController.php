<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractStatusEnum;
use App\Enums\JobStatusEnum;
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
    $total_workers   = User::all()->count();
    $total_clients   = Client::all()->count();
    $total_jobs      = Job::count();
    $total_offers    = Offer::count();
    $total_schedules  = Schedule::count();
    $total_contracts  = Contract::count();
    $latest_jobs     = Job::query()
      ->with(['client', 'service', 'worker', 'jobservice'])
      ->where('status', JobStatusEnum::COMPLETED)
      ->orderBy('created_at', 'desc')
      ->paginate(5);

    return response()->json([
      'total_workers'      => $total_workers,
      'total_clients'      => $total_clients,
      'total_jobs'         => $total_jobs,
      'total_offers'       => $total_offers,
      'total_schedules'    => $total_schedules,
      'total_contracts'    => $total_contracts,
      'latest_jobs'        => $latest_jobs
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
    $count = Notification::count();
    $seenCount = Notification::where('seen', 0)->count();

    if ($count > 0) {
      if ($request->head) {
        $noticeAll = Notification::with('client')->orderBy('id', 'desc')->take(5)->get();
      }

      if ($request->all) {
        $noticeAll = Notification::with('client')->orderBy('id', 'desc')->paginate(15);
      }

      if (isset($noticeAll)) {
        foreach ($noticeAll as $k => $notice) {
          if ($notice->type == 'sent-meeting') {
            $sch = Schedule::with('client')->where('id', $notice->meet_id)->first();

            if (isset($sch)) {
              $noticeAll[$k]->data = "<a href='/admin/view-schedule/" . $sch->client->id . "?sid=" . $sch->id . "'> Meeting </a> scheduled with <a href='/admin/view-client/" . $sch->client->id . "'>" . $sch->client->firstname . " " . $sch->client->lastname .
                "</a> on " . Carbon::parse($sch->start_date)->format('d-m-Y') . " at " . ($sch->start_time);
            }
          } else if ($notice->type == 'accept-meeting') {
            $sch = Schedule::with('client')->where('id', $notice->meet_id)->first();

            if (isset($sch)) {
              $noticeAll[$k]->data = "<a href='/admin/view-schedule/" . $notice->client->id . "?sid=" . $sch->id . "'> Meeting </a> with <a href='/admin/view-client/" . $sch->client->id . "'>" . $sch->client->firstname . " " . $sch->client->lastname .
                "</a> has been confirmed now on " . Carbon::parse($sch->start_date)->format('d-m-Y')  . " at " . ($sch->start_time);
            }
          } else if ($notice->type == 'reject-meeting') {
            $sch = Schedule::with('client')->where('id', $notice->meet_id)->first();

            if (isset($sch)) {
              $noticeAll[$k]->data = "<a href='/admin/view-schedule/" . $notice->meet_id . "?sid=" . $sch->id . "'> Meeting </a> with <a href='/admin/view-client/" . $sch->client->id . "'>" . $sch->client->firstname . " " . $sch->client->lastname .
                "</a> which on " . Carbon::parse($sch->start_date)->format('d-m-Y')  . " at " . ($sch->start_time) . " has cancelled now.";
            }
          } else if ($notice->type == 'accept-offer') {
            $ofr = Offer::with('client')->where('id', $notice->offer_id)->first();

            if (isset($ofr)) {
              $noticeAll[$k]->data = "<a href='/admin/view-client/" . $ofr->client->id . "'>" . $ofr->client->firstname . " " . $ofr->client->lastname .
                "</a> has accepted the <a href='/admin/view-offer/" . $notice->offer_id . "'> price offer </a>";
            }
          } else if ($notice->type == 'reject-offer') {
            $ofr = Offer::with('client')->where('id', $notice->offer_id)->first();

            if (isset($ofr)) {
              $noticeAll[$k]->data = "<a href='/admin/view-client/" . $ofr->client->id . "'>" . $ofr->client->firstname . " " . $ofr->client->lastname .
                "</a> has rejected <a href='/admin/view-offer/" . $notice->offer_id . "'>the price offer </a>";
            }
          } else if ($notice->type == 'contract-accept') {
            $contract = Contract::with('offer', 'client')->where('id', $notice->contract_id)->first();

            if (isset($contract)) {
              $noticeAll[$k]->data = "<a href='/admin/view-client/" . $contract->client->id . "'>" . $contract->client->firstname . " " . $contract->client->lastname .
                "</a> has approved the <a href='/admin/view-contract/" . $contract->id . "'> contract </a>";
              if ($contract->offer) {
                $noticeAll[$k]->data .= "for <a href='/admin/view-offer/" . $contract->offer->id . "'> offer</a>";
              }
            }
          } else if ($notice->type == 'contract-reject') {
            $contract = Contract::with('offer', 'client')->where('id', $notice->contract_id)->first();

            if (isset($contract)) {
              $noticeAll[$k]->data = "<a href='/admin/view-client/" . $contract->client->id . "'>" . $contract->client->firstname . " " . $contract->client->lastname .
                "</a> has rejected the <a href='/admin/view-contract/" . $contract->id . "'> contract </a>";
              if ($contract->offer) {
                $noticeAll[$k]->data .= "for <a href='/admin/view-offer/" . $contract->offer->id . "'> offer</a>";
              }
            }
          } else if ($notice->type == 'client-cancel-job') {
            $job = Job::with('offer', 'client')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/view-client/" . $job->client->id . "'>" . $job->client->firstname . " " . $job->client->lastname .
                "</a> has cancelled the  <a href='/admin/view-job/" . $job->id . "'> job </a>";
              if ($job->offer) {
                $noticeAll[$k]->data .= "for <a href='/admin/view-offer/" . $job->offer->id . "'> offer </a> ";
              }
            }
          } else if ($notice->type == 'worker-reschedule') {
            $job = Job::with('offer', 'worker')->where('id', $notice->job_id)->first();

            if (isset($job)) {
              $noticeAll[$k]->data = "<a href='/admin/view-worker/" . $job->worker->id . "'>" . $job->worker->firstname . " " . $job->worker->lastname .
                "</a> request for reschedule the  <a href='/admin/view-job/" . $job->id . "'> job </a>";
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
    $tasks = Job::query()
      ->with(['client', 'worker', 'offer', 'hours'])
      ->where('status', JobStatusEnum::COMPLETED);

    if (empty($request->duration) || $request->duration == 'all') {
      $tasks = $tasks->get();
    }

    if ($request->duration == 'day') {
      $tasks = $tasks->whereDate('created_at', Carbon::today())->get();
    }

    if ($request->duration == 'month') {
      $tasks = $tasks->whereMonth('created_at', Carbon::now()->month)->get();
    }

    if ($request->duration == 'week') {
      $tasks = $tasks->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
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
