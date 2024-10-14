<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Job;
use App\Models\Setting;
use App\Models\Holiday;
use App\Models\AdvanceLoan;
use App\Models\SickLeave;
use App\Models\AdvanceLoanTransaction;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PayrollReportExport;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use App\Exports\MonthlyReportExport;

class PayrollReportController extends Controller
{
    public function generateMonthlyReport(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m', 
        ]);

        $month = $request->month;

        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();
        $daysInMonth = Carbon::parse($month)->daysInMonth;

        // Fetch settings
        $settings = Setting::all()->pluck('value', 'key');
        $overtimeRate125 = $settings['overtime_rate_9to10_hours'];
        $overtimeRate150 = $settings['overtime_rate_11to12_hours'];
        $holidayPay175 = $settings['holiday_rate_forTwo_hours'];
        $holidayPay200 = $settings['holiday_rate_three_hours'];
        $bonusAfterOneYear = $settings['bonus_after_one_year_perHour'];
        $bonusAfterSixYears = $settings['bonus_after_six_years_perHour'];
        $publicHolidayBonus = $settings['rosh_hashanah_pay'];
        $workerDeduction = (float)$settings['deduction_foreignworker'];
        $recoveryFee = $settings['recovery_fee_year_ofservice'];
        $drivingFeeDay = $settings['driving_fee_perDay'];
        $drivingFeeMonth =(float)$settings['driving_fee_perMonth'];

        // Fetch holidays 
        $holidays = Holiday::where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($query) use ($startDate, $endDate) {
                    $query->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                });
        })->get(['holiday_name', 'start_date', 'end_date']);

        // Generate a list of all holiday dates
        $holidayDates = collect();
        foreach ($holidays as $holiday) {
            $period = CarbonPeriod::create($holiday->start_date, $holiday->end_date);
            $holidayDates = $holidayDates->merge($period->toArray());
        }
        $holidayDates = $holidayDates->unique()->map->format('Y-m-d')->toArray();

        // Calculate the total working days excluding Fridays, Saturdays, and holidays
        $currentDate = $startDate->copy();
        $workingDays = 0;      
        while ($currentDate->lte($endDate)) {
            if (!in_array($currentDate->format('Y-m-d'), $holidayDates) && !in_array($currentDate->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY])) {
                $workingDays++;
            }
            $currentDate->addDay();
        }

        // Fetch all users regardless of whether they have jobs in the specified month
        $users = User::select('users.id', 'users.firstname', 'users.lastname', 'users.role',
                            'users.payment_per_hour', 'users.worker_id', 'users.created_at', 'users.address', 'users.driving_fees','users.country','users.employment_type', 'users.salary')
                    ->leftJoin('jobs', function($join) use ($startDate, $endDate) {
                        $join->on('users.id', '=', 'jobs.worker_id')
                        ->whereBetween('jobs.start_date', [$startDate, $endDate]);
                    })
                    ->groupBy('users.id')
                    ->get();

        $reportData = [];

        foreach ($users as $user) {
            $workerDay = Job::where('worker_id', $user->id)
                ->whereBetween('start_date', [$startDate, $endDate])
                ->pluck('start_date')
                ->map(function ($date) {
                    return Carbon::parse($date)->startOfDay();
                })
                ->unique()
                ->count();

            $totalMinutesWorked = Job::where('worker_id', $user->id)
                ->whereBetween('start_date', [$startDate, $endDate])
                ->sum('actual_time_taken_minutes');

            $salary = ($user->employment_type == 'fixed') ? $user->salary : null;
            $normalPayment = 0;
            $holidayPayment175=0;
            $holidayPayment200=0;
            $annualRecoveryFee=0;
            
            if ($totalMinutesWorked == 0) {
                $reportData[] = [
                    'Number' => $user->id,
                    'Passport Id' => $user->worker_id,
                    'Last Name' => $user->lastname,
                    'First Name' => $user->firstname,
                    'Role' => $user->role,
                    'Total Hours Worked' => ' ',
                    'Normal Rate Hours (100%)' => ' ',
                    'Hours at 125% Salary' => ' ',
                    'Hours at 150% Salary' => ' ',
                    'Holiday/Weekend Hours at 175% Salary' => ' ',
                    'Holiday/Weekend Hours at 200% Salary' => ' ',
                    'Total Days' => ' ',
                    'Hourly Rate' => $user->payment_per_hour,
                    'Salary' => $salary,
                    'Normal Payment' => ' ',
                    '125% Bonus Payment' => ' ',
                    '150% Bonus Payment' => ' ',
                    'Holiday Payment at 175%' => ' ',
                    'Holiday Payment at 200%' => ' ',
                    'Recovery Fee' => ' ',
                    'Public Holiday Bonus' => ' ',
                    'Insurance' => ' ',
                    'Sick Leave Payment' => ' ',
                    'Total Payment' => ' ',
                    'Loan' => ' ',
                    'Net Payment' => ' ',
                    'Doctor Report' => ' ',
                ];
                continue;
            }

            $totalHoursWorked = $totalMinutesWorked / 60;
            $paymentPerHour = $user->payment_per_hour;

            // Initialize variables
            $standardHours = $workingDays * 8;
            $holidayHours175 = 0;
            $holidayHours200 = 0;
            $regularHours = 0;
            $extraHours = 0;


            $sickLeavePayment = 0;
            $sickLeaveDays = 0;
            $doctorReportPath = " ";
            $doctorReportLink = " ";
            $doctorReportDisplay = " ";

            if ($user->employment_type == 'fixed') {
                // Fetch sick leaves and calculate sick leave payment for fixed workers
                $sickLeaves = SickLeave::where('worker_id', $user->id)
                    ->where('status', 'approved')
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->where(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $endDate)
                                ->where('end_date', '>=', $startDate);
                        });
                    })->get();
        
                // Calculate sick leave payment for fixed workers
                foreach ($sickLeaves as $leave) {
                    $sickStart = Carbon::parse($leave->start_date);
                    $sickEnd = Carbon::parse($leave->end_date);
                    $leaveDays = $sickStart->diffInDays($sickEnd) + 1;
                    $sickLeaveDays += $leaveDays;
        
                    for ($i = 1; $i <= $leaveDays; $i++) {
                        if ($sickLeaveDays > 1.5) {
                            if ($i == 1) {
                                continue;
                            } elseif ($i == 2 || $i == 3) {
                                $sickLeavePayment += ($user->salary / $workingDays) * 0.5;
                            } elseif ($i >= 4) {
                                $sickLeavePayment += $user->salary / $workingDays;
                            }
                        } else {
                            // No deduction for up to 1.5 sick leave days
                            continue;
                        }
                    }            
                }

                 $sickLeaves = SickLeave::where('worker_id', $user->id)
                 ->where('status', 'approved')
                 ->whereBetween('start_date', [$startDate, $endDate])
                 ->get();
 
                $doctorReportPath = $sickLeaves->pluck('doctor_report_path')->first();
    
                    if ($doctorReportPath) {
                        $doctorReportLink = url('storage/' . $doctorReportPath);
                        $doctorReportDisplay = $doctorReportLink;
                    } else {
                        $doctorReportDisplay = 'Not available';
                    }
        
                $insuranceDeduction = ($user->country !== 'Israel') ? $workerDeduction : 0;

                $totalPayment = $user->salary - $sickLeavePayment;
                $loanDeduction = $this->handleAdvanceLoanDeductions($user, $totalPayment, $startDate, $endDate);
                $netPayment = $loanDeduction['adjustedPayment'] - $insuranceDeduction;
                $loanApplied = $loanDeduction['loanApplied'];
            } else {
                $paymentPerHour = $user->payment_per_hour;
        
                $normalPayment = ($totalHoursWorked <= $standardHours)
                    ? $totalHoursWorked * $paymentPerHour
                    : $standardHours * $paymentPerHour;

                    $holidayJobs = Job::where('worker_id', $user->id)
                    ->whereIn(DB::raw('DATE(start_date)'), $holidayDates)
                    ->orWhere(function($query) use ($startDate, $endDate) {
                        $query->whereBetween(DB::raw('DATE(start_date)'), [$startDate, $endDate])
                            ->whereIn(DB::raw('DAYOFWEEK(start_date)'), [Carbon::FRIDAY + 1, Carbon::SATURDAY + 1]);
                    })
                    ->get();

                    foreach ($holidayJobs as $job) {
                        $hoursWorked = $job->actual_time_taken_minutes / 60;
        
                        if ($hoursWorked <= 2) {
                            $holidayHours175 += $hoursWorked;
                        } else {
                            $holidayHours175 += 2;
                            $holidayHours200 += ($hoursWorked - 2);
                        }
                    }
        
                    // Calculate years of service
                    $hireDate = $user->created_at; 
                    if ($hireDate) {
                        $yearsOfService = $hireDate->diffInYears(Carbon::now());
                        $monthsWorked = $hireDate->diffInMonths(Carbon::now());
                    } else {
                        $yearsOfService = 0;
                        $monthsWorked = 0;
                    }
        
                    // Determine recovery fee days
                    $daysOfRecoveryFee = 0;
        
                    if ($yearsOfService >= 1 && $yearsOfService <= 3) {
                        $daysOfRecoveryFee = 7;
                    } elseif ($yearsOfService >= 4 && $yearsOfService <= 10) {
                        $daysOfRecoveryFee = 9;
                    } elseif ($yearsOfService >= 11 && $yearsOfService <= 15) {
                        $daysOfRecoveryFee = 10;
                    } elseif ($yearsOfService >= 16 && $yearsOfService <= 19) {
                        $daysOfRecoveryFee = 11;
                    } elseif ($yearsOfService >= 20 && $yearsOfService <= 24) {
                        $daysOfRecoveryFee = 12;
                    } elseif ($yearsOfService >= 25) {
                        $daysOfRecoveryFee = 13;
                    }
                $dailyRecoveryFee = $recoveryFee;
                $annualRecoveryFee = $daysOfRecoveryFee * $dailyRecoveryFee;

                // Holiday payments
                $holidayPayment175 = $holidayHours175 * $holidayPay175;
                $holidayPayment200 = $holidayHours200 * $holidayPay200;
        
                $insuranceDeduction = ($user->country !== 'Israel') ? $workerDeduction : 0;
                $totalPayment = $normalPayment + $holidayPayment175 + $holidayPayment200 - $annualRecoveryFee;
            }
        
            // Handling loan deductions and final net payment
            $loanDeduction = $this->handleAdvanceLoanDeductions($user, $totalPayment, $startDate, $endDate);
            $netPayment = $totalPayment - $insuranceDeduction - $loanDeduction['loanApplied'];
            $loanApplied = $loanDeduction['loanApplied']; 

            // Store report data
            $reportData[] = [
                'Number' => $user->id,
                'Passport Id' => $user->worker_id,
                'Last Name' => $user->lastname,
                'First Name' => $user->firstname,
                'Role' => $user->role,
                'Total Hours Worked' => round($totalHoursWorked, 2),
                'Normal Rate Hours (100%)' => round($standardHours, 2),
                'Hours at 125% Salary' => round($holidayHours175, 2),
                'Hours at 150% Salary' => round($holidayHours200, 2),
                'Holiday/Weekend Hours at 175% Salary' => round($holidayHours175, 2),
                'Holiday/Weekend Hours at 200% Salary' => round($holidayHours200, 2),
                'Total Days' => round($workingDays, 2),
                'Hourly Rate' => round($paymentPerHour, 2),
                'Salary' => round($salary, 2),
                'Normal Payment' => round($normalPayment, 2),
                '125% Bonus Payment' => round($holidayPayment175, 2),
                '150% Bonus Payment' => round($holidayPayment200, 2),
                'Holiday Payment at 175%' => round($holidayPayment175, 2),
                'Holiday Payment at 200%' => round($holidayPayment200, 2),
                'Recovery Fee' => round($annualRecoveryFee, 2),
                'Public Holiday Bonus' => round($publicHolidayBonus, 2),
                'Insurance' => round($insuranceDeduction, 2),
                'Sick Leave Payment' => round($sickLeavePayment, 2),
                'Total Payment' => round($totalPayment, 2),
                'Loan' => round($loanApplied, 2),
                'Net Payment' => round($netPayment, 2),
                'Doctor Report' => $doctorReportDisplay,
            ];
        }

        return Excel::download(new MonthlyReportExport($reportData), 'monthly_report_' . $month . '.xlsx');
    }

    public function handleAdvanceLoanDeductions(User $user, $totalPayment, $startDate, $endDate)
    {
        $deduction = 0.00;
        $remainingAmount = 0.00;

        $startMonth = \Carbon\Carbon::parse($startDate)->format('Y-m');
        $endMonth = \Carbon\Carbon::parse($endDate)->format('Y-m');

        $advanceLoans = AdvanceLoan::where('worker_id', $user->id)
            ->whereIn('type', ['advance', 'loan'])
            ->orderByRaw("FIELD(type, 'advance', 'loan'), created_at ASC")
            ->get();

        foreach ($advanceLoans as $advanceLoan) {
            $existingTransaction = AdvanceLoanTransaction::where('advance_loan_id', $advanceLoan->id)
                ->whereYear('transaction_date', \Carbon\Carbon::parse($endDate)->year)
                ->whereMonth('transaction_date', \Carbon\Carbon::parse($endDate)->month)
                ->where('type', 'credit')
                ->first();

            if ($existingTransaction) {
                $deduction += $existingTransaction->amount;
                $totalPayment -= $existingTransaction->amount;
                continue;
            }

            // Continue deducting until pending amount is 0, starting with the oldest transactions
            while (true) {
                $lastTransaction = AdvanceLoanTransaction::where('advance_loan_id', $advanceLoan->id)
                    ->orderBy('transaction_date', 'desc')
                    ->whereIn('type', ['credit', 'debit'])
                    ->first();

                $pendingAmount = $lastTransaction ? floatval($lastTransaction->pending_amount) : floatval($advanceLoan->amount);

                if ($pendingAmount <= 0) {
                    $advanceLoan->update(['status' => 'paid']);
                    break;
                }

                $transactionMonth = $lastTransaction ? \Carbon\Carbon::parse($lastTransaction->transaction_date)->format('Y-m') : $startMonth;

                if ($transactionMonth > $startMonth) {
                    break; 
                }

                if ($advanceLoan->type == 'advance') {

                    $deductibleAmount = min($totalPayment, $pendingAmount);
                    $deduction += $deductibleAmount;
                    $remainingAmount = $pendingAmount - $deductibleAmount;

                    AdvanceLoanTransaction::create([
                        'advance_loan_id' => $advanceLoan->id,
                        'worker_id' => $user->id,
                        'type' => 'credit',
                        'amount' => $deductibleAmount,
                        'pending_amount' => $remainingAmount,
                        'transaction_date' => $endDate,
                    ]);
                    if ($remainingAmount > 0) {
                        $advanceLoan->update(['status' => 'active']);
                    } else {
                        $advanceLoan->update(['status' => 'paid']);
                    }

                } elseif ($advanceLoan->type == 'loan') {
                    $monthlyPayment = floatval($advanceLoan->monthly_payment);

                    // Check if the loan start date falls within or before the requested month
                    $loanStartMonth = \Carbon\Carbon::parse($advanceLoan->loan_start_date)->format('Y-m');

                    if ($loanStartMonth <= $startMonth) {
                        // Ensure deduction is applied only once per month
                        $existingTransaction = AdvanceLoanTransaction::where('advance_loan_id', $advanceLoan->id)
                            ->whereYear('transaction_date', \Carbon\Carbon::parse($endDate)->year)
                            ->whereMonth('transaction_date', \Carbon\Carbon::parse($endDate)->month)
                            ->where('type', 'credit')
                            ->first();

                        if (!$existingTransaction) {
                            // Deduct the monthly payment or the pending amount, whichever is smaller
                            $deductibleAmount = min($monthlyPayment, $totalPayment, $pendingAmount);
                            $deduction += $deductibleAmount;
                            $remainingAmount = $pendingAmount - $deductibleAmount;

                            // Create a new credit transaction
                            AdvanceLoanTransaction::create([
                                'advance_loan_id' => $advanceLoan->id,
                                'worker_id' => $user->id,
                                'type' => 'credit',
                                'amount' => $deductibleAmount,
                                'pending_amount' => $remainingAmount,
                                'transaction_date' => $endDate,
                            ]);

                            if ($remainingAmount > 0) {
                                $advanceLoan->update(['status' => 'active']);
                            } else {
                                $advanceLoan->update(['status' => 'paid']);
                            }

                            // Adjust the total payment by the deductible amount
                            $totalPayment -= $deductibleAmount;

                            // If the monthly payment is more than the salary, save the remaining amount in pending
                            if ($totalPayment < 0) {
                                $remainingAmount += abs($totalPayment);
                                $totalPayment = 0; // Ensure no further deductions are made
                            }
                        }
                    }
                }                
                $totalPayment -= $deductibleAmount;

                // if ($totalPayment <= 0) {
                //     break; 
                // }

                if ($totalPayment < 0) {
                    // Add remaining unpaid amount to the next month's pending amount       
                    $advanceLoan->update([
                        'pending_amount' => $remainingAmount + abs($totalPayment), // Adding the unpaid amount
                    ]);
                    $totalPayment = 0; // Reset total payment to prevent further deductions
                    break; 
                }

                if ($pendingAmount > 0) {
                    $advanceLoan->update(['status' => 'active']);
                } else {
                    $advanceLoan->update(['status' => 'paid']);
                }

            }
        } 
        $adjustedPayment = max($totalPayment , 0.00);

        return [
            'adjustedPayment' => round($adjustedPayment, 2),
            'deduction' => round($deduction, 2),
            'loanApplied' => round($deduction, 2),
        ];
    }


}