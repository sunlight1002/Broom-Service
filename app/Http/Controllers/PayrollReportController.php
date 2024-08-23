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
use Illuminate\Support\Facades\Storage;

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
        $users = User::select('users.id', 'users.firstname','users.lastname','users.role',
                            'users.payment_per_hour','users.worker_id','users.created_at','users.address','users.driving_fees')
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
            

                if ($totalMinutesWorked == 0) {
                    $reportData[] = [
                        'Number' =>$user->id,
                        'Passport Id' => $user->worker_id,
                        'Last Name' =>$user->lastname,
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
                        'Normal Payment' => ' ',
                        '125% Bonus Payment' => ' ',
                        '150% Bonus Payment' => ' ',
                        'Holiday Payment at 175%' => ' ',
                        'Holiday Payment at 200%' => ' ',
                        'Recovery Fee' => ' ',
                        'Public Holiday Bonus' => ' ',
                        'Insurance' =>' ',
                        'Sick Leave Payment' =>' ',
                        'Total Payment' => ' ',
                        'loan' =>' ',
                        'Net Payment'=>' ',
                        'Doctor Report' =>' ',   
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

                // Get jobs worked on holidays or weekends
                $holidayJobs = Job::where('worker_id', $user->id)
                    ->whereIn(DB::raw('DATE(start_date)'), $holidayDates)
                    ->orWhere(function($query) use ($startDate, $endDate) {
                        $query->whereBetween(DB::raw('DATE(start_date)'), [$startDate, $endDate])
                            ->whereIn(DB::raw('DAYOFWEEK(start_date)'), [Carbon::FRIDAY + 1, Carbon::SATURDAY + 1]);
                    })
                    ->get();

                $sickLeaves = SickLeave::where('worker_id', $user->id)
                    -> where('status', 'approved')
                    ->where(function($query) use ($startDate, $endDate) {
                        $query->where(function($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $endDate)
                            ->where('end_date', '>=', $startDate);
                        });
                    })
                    ->get();
                    

                // Calculate holiday hours
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
                
                //payment increment on year of experience
                $paymentPerHour = $user->payment_per_hour;
                if ($yearsOfService >= 6) {
                    $paymentPerHour += $bonusAfterSixYears;
                } elseif ($yearsOfService >= 1) {
                    $paymentPerHour += $bonusAfterOneYear;
                }

                $dailyRecoveryFee = $recoveryFee;
                $annualRecoveryFee = $daysOfRecoveryFee * $dailyRecoveryFee;
                $proratedRecoveryFee = ($annualRecoveryFee / 12) ;

                // Calculate total hours worked excluding holiday hours
                $totalHoursWithoutHoliday = $totalHoursWorked - ($holidayHours175 + $holidayHours200);
                $standardHours = min($totalHoursWithoutHoliday, $standardHours);
                $extraHours = max($totalHoursWithoutHoliday - $standardHours, 0);

                // Calculate overtime hours
                $hoursAt125 = min($extraHours, $workingDays * 2);
                $hoursAt150 = max($extraHours - $hoursAt125, 0);
                // Calculate payments
                $normalPayment = $standardHours * $paymentPerHour;
                $bonus125Payment = $hoursAt125 * $paymentPerHour * $overtimeRate125;
                $bonus150Payment = $hoursAt150 * $paymentPerHour * $overtimeRate150;
                $holidayPayment175 = $holidayHours175 * $paymentPerHour * $holidayPay175;
                $holidayPayment200 = $holidayHours200 * $paymentPerHour * $holidayPay200;

                //holiday bonus
                $publicHolidayBonusAmount = 0;
                $normalizedHolidayNames = [
                    'roshhashanah' => 'Rosh Hashanah',
                    'passover' => 'Passover'
                ];
                
                foreach ($holidays as $holiday) {
                    $normalizedHolidayName = strtolower(preg_replace('/\s+/', '', $holiday->holiday_name));
        
                    if (array_key_exists($normalizedHolidayName, $normalizedHolidayNames)) {
                        $holidayStartDate = Carbon::parse($holiday->start_date);
                        $holidayEndDate = Carbon::parse($holiday->end_date);
                
                        $holidayDurationDays = $holidayStartDate->diffInDays($holidayEndDate) + 1;
                
                        $workedHoursInHolidayMonth = Job::where('worker_id', $user->id)
                            ->whereMonth('start_date', Carbon::parse($month)->month)
                            ->sum('actual_time_taken_minutes') / 60;
                
                        // Calculate the bonus
                        if ($workedHoursInHolidayMonth >= 91) {
                            $publicHolidayBonusAmount += $publicHolidayBonus * $holidayDurationDays;
                        } else {
                            $publicHolidayBonusAmount += ($workedHoursInHolidayMonth / 182) * $publicHolidayBonus * $holidayDurationDays;
                        }
                    }
                }

                $sickLeavePayment = 0;
                $doctorReports = ' ';
                foreach ($sickLeaves as $leave) {
                    $leavestartDate = Carbon::parse($leave->start_date);
                    $leaveendDate = Carbon::parse($leave->end_date);
                    $dailySalary = $paymentPerHour * 8; 
                    $isCancerPatient = $leave->is_cancer_patient;
                    $doctorReports = $leave->doctor_report_path; 
                    
                    $totalDays = $leavestartDate->diffInDays($leaveendDate) + 1;
                    if ($isCancerPatient) {
                        // Cancer patients get 100% payment from day one
                        $sickLeavePayment += $dailySalary * $totalDays;
                    } else {
                        for ($dayCount = 1; $leavestartDate->lte($leaveendDate); $leavestartDate->addDay(), $dayCount++) {
                            if ($totalDays <= 3) {
                                if ($dayCount == 2 || $dayCount == 3) {
                                    $sickLeavePayment += $dailySalary * 0.5;
                                }
                            } else {
                                if ($dayCount == 1) {
                                    continue;
                                } elseif ($dayCount == 2 || $dayCount == 3) {
                                    $sickLeavePayment += $dailySalary * 0.5;
                                } else {
                                    $sickLeavePayment += $dailySalary;
                                }
                            }
                        }
                    }
                }
               
               
               // Deduction for foreign workers
                $foreignWorkerDeduction = 0;
                if ($user->address) {
                    $addressLowercase = strtolower($user->address);
                    if (!str_contains($addressLowercase, 'israel')) {
                        $foreignWorkerDeduction = $workerDeduction;
                    }
                }

                //driving fees
                $totalDrivingFee = 0;
                if ($user->driving_fees == 1) {
                    $totalDrivingFee = $workerDay * $drivingFeeDay;

                    // Compare with the monthly cap
                    if ($totalDrivingFee > $drivingFeeMonth) {
                        $totalDrivingFee = $drivingFeeMonth;
                    }
                }
                 
                $totalPayment = $normalPayment + $bonus125Payment + $bonus150Payment + $holidayPayment175 + $holidayPayment200
                               + $proratedRecoveryFee +$publicHolidayBonusAmount +$totalDrivingFee + $sickLeavePayment- $foreignWorkerDeduction;

                $newPayment = $totalPayment +$foreignWorkerDeduction;

                $deduction = 0;
                $adjustedPaymentData = $this->handleAdvanceLoanDeductions($user, $totalPayment, $startDate, $endDate);
                
                $adjustedPayment = $adjustedPaymentData['adjustedPayment'] ;
                $deduction = $adjustedPaymentData['deduction']; 
                
                $grossPay = round($adjustedPayment,2);
                

                $reportData[] = [
                    'Number' =>$user->id,
                    'Passport Id' => $user->worker_id,
                    'Last Name' =>$user->lastname,
                    'First Name' => $user->firstname,
                    'Role' => $user->role,
                    'Total Hours Worked' => round($totalHoursWorked, 2),
                    'Normal Rate Hours (100%)' => round($standardHours, 2),
                    'Hours at 125% Salary' => round($hoursAt125, 2),
                    'Hours at 150% Salary' => round($hoursAt150, 2),
                    'Holiday/Weekend Hours at 175% Salary' => round($holidayHours175, 2),
                    'Holiday/Weekend Hours at 200% Salary' => round($holidayHours200, 2),
                    'Total Days'=> $workerDay,
                    'Hourly Rate' => $paymentPerHour,
                    'Normal Payment' => round($normalPayment, 2),
                    '125% Bonus Payment' => round($bonus125Payment, 2),
                    '150% Bonus Payment' => round($bonus150Payment, 2),
                    'Holiday Payment at 175%' => round($holidayPayment175, 2),
                    'Holiday Payment at 200%' => round($holidayPayment200, 2),
                    'Recovery Fee' => round($proratedRecoveryFee, 2),
                    'Public Holiday Bonus' => round($publicHolidayBonusAmount, 2),
                    'Driving Fees'=> round($totalDrivingFee, 2),
                    'Sick Leave Payment' => round($sickLeavePayment, 2),
                    'Total Payment' => round($newPayment , 2), 
                    'Insurance' => $foreignWorkerDeduction, 
                    'loan' => round($deduction,2),
                    'Net Payment' => round($adjustedPayment, 2),
                    'Doctor Report' => $doctorReports,        
                ];

            
        }
        
         
        $fileName = 'דוח שכר לעובד לחודש של' . $month . '.xlsx';
        Excel::store(new PayrollReportExport($reportData ), $fileName, 'public');
    
        // Return the path to the saved file
        return response()->json([ 
            'message' => 'Excel file has been saved.',
            'file_path' => Storage::url($fileName)
        ]);
        
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
    
                if ($totalPayment <= 0) {
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
        ];
    }
    

}
