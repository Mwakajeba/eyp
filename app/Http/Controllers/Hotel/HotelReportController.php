<?php

namespace App\Http\Controllers\Hotel;

use App\Http\Controllers\Controller;
use App\Models\Hotel\Booking;
use App\Models\Hotel\Room;
use App\Models\Hotel\Guest;
use App\Models\Hotel\Property;
use App\Models\Receipt;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class HotelReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'company.scope', 'require.branch']);
    }

    public function index()
    {
        return view('hotel.reports.index');
    }

    /**
     * 1. Daily Occupancy Report
     */
    public function dailyOccupancy(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);

        $totalRooms = Room::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->count();

        // Occupied rooms (checked in or confirmed for today)
        $occupiedRooms = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereDate('check_in', '<=', $selectedDate)
            ->whereDate('check_out', '>=', $selectedDate)
            ->distinct('room_id')
            ->count('room_id');

        // Reserved rooms (pending bookings for future dates)
        $reservedRooms = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->where('status', 'pending')
            ->whereDate('check_in', '>', $selectedDate)
            ->distinct('room_id')
            ->count('room_id');

        $availableRooms = $totalRooms - $occupiedRooms;
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

        return view('hotel.reports.daily-occupancy', compact(
            'date',
            'totalRooms',
            'occupiedRooms',
            'availableRooms',
            'reservedRooms',
            'occupancyRate'
        ));
    }

    /**
     * 2. Daily Sales / Revenue Report
     */
    public function dailySales(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);

        $receipts = Receipt::where('reference_type', 'hotel_booking')
            ->where('branch_id', current_branch_id())
            ->whereDate('date', $selectedDate)
            ->with(['user', 'bankAccount', 'receiptItems'])
            ->get();

        // Get booking details for each receipt
        $salesData = $receipts->map(function ($receipt) {
            $booking = Booking::where('booking_number', $receipt->reference_number)
                ->with(['guest', 'room'])
                ->first();

            return [
                'receipt' => $receipt,
                'booking' => $booking,
                'guest_name' => $booking ? $booking->guest->first_name . ' ' . $booking->guest->last_name : 'N/A',
                'room_no' => $booking ? $booking->room->room_number : 'N/A',
                'payment_method' => $this->getPaymentMethod($receipt),
                'amount' => $receipt->amount,
                'received_by' => $receipt->user->name ?? 'N/A',
            ];
        });

        $totalAmount = $receipts->sum('amount');

        return view('hotel.reports.daily-sales', compact('date', 'salesData', 'totalAmount'));
    }

    protected function getPaymentMethod($receipt)
    {
        if ($receipt->payment_method) {
            return ucfirst(str_replace('_', ' ', $receipt->payment_method));
        }
        
        if ($receipt->bankAccount) {
            $accountName = strtolower($receipt->bankAccount->name ?? '');
            if (str_contains($accountName, 'mpesa') || str_contains($accountName, 'mobile')) {
                return 'M-Pesa';
            }
            if (str_contains($accountName, 'card')) {
                return 'Card';
            }
            return 'Bank Transfer';
        }
        
        return 'Cash';
    }

    /**
     * 3. Monthly Revenue Report
     */
    public function monthlyRevenue(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        // Room Revenue from bookings
        $bookings = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereBetween('check_in', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->get();

        $roomRevenue = $bookings->sum('total_amount');
        $totalBookings = $bookings->count();

        // Extra Services Revenue (from hotel expenses or additional charges)
        // This would need to be implemented based on your system structure
        $extraServicesRevenue = 0; // Placeholder

        $totalRevenue = $roomRevenue + $extraServicesRevenue;

        // Get monthly data for the year
        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthStart = Carbon::create($year, $m, 1)->startOfMonth();
            $monthEnd = Carbon::create($year, $m, 1)->endOfMonth();
            
            $monthBookings = Booking::forBranch(current_branch_id())
                ->forCompany(current_company_id())
                ->whereBetween('check_in', [$monthStart, $monthEnd])
                ->where('status', '!=', 'cancelled')
                ->get();

            $monthlyData[] = [
                'month' => Carbon::create($year, $m, 1)->format('F Y'),
                'total_bookings' => $monthBookings->count(),
                'room_revenue' => $monthBookings->sum('total_amount'),
                'extra_services_revenue' => 0, // Placeholder
                'total_revenue' => $monthBookings->sum('total_amount'),
            ];
        }

        return view('hotel.reports.monthly-revenue', compact(
            'year',
            'month',
            'totalBookings',
            'roomRevenue',
            'extraServicesRevenue',
            'totalRevenue',
            'monthlyData'
        ));
    }

    /**
     * 4. Booking Report
     */
    public function bookingReport(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $bookings = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereBetween('check_in', [$dateFrom, $dateTo])
            ->with(['guest', 'room', 'room.property'])
            ->orderBy('check_in', 'desc')
            ->get();

        return view('hotel.reports.booking', compact('bookings', 'dateFrom', 'dateTo'));
    }

    /**
     * 5. Check-In & Check-Out Report
     */
    public function checkInOut(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::today()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::today()->format('Y-m-d'));

        // Check-ins
        $checkIns = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereBetween('check_in', [$dateFrom, $dateTo])
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->with(['guest', 'room', 'createdBy'])
            ->get();

        // Check-outs
        $checkOuts = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereBetween('check_out', [$dateFrom, $dateTo])
            ->whereIn('status', ['checked_out'])
            ->with(['guest', 'room', 'createdBy'])
            ->get();

        $movements = collect()
            ->merge($checkIns->map(function ($booking) {
                return [
                    'type' => 'Check-In',
                    'guest_name' => $booking->guest->first_name . ' ' . $booking->guest->last_name,
                    'room_no' => $booking->room->room_number,
                    'time' => $booking->check_in_time ?? $booking->check_in,
                    'processed_by' => $booking->createdBy->name ?? 'N/A',
                    'stay_duration' => $booking->nights . ' night(s)',
                ];
            }))
            ->merge($checkOuts->map(function ($booking) {
                return [
                    'type' => 'Check-Out',
                    'guest_name' => $booking->guest->first_name . ' ' . $booking->guest->last_name,
                    'room_no' => $booking->room->room_number,
                    'time' => $booking->check_out_time ?? $booking->check_out,
                    'processed_by' => $booking->createdBy->name ?? 'N/A',
                    'stay_duration' => $booking->nights . ' night(s)',
                ];
            }))
            ->sortByDesc('time');

        return view('hotel.reports.check-in-out', compact('movements', 'dateFrom', 'dateTo'));
    }

    /**
     * 6. Room Status Report
     */
    public function roomStatus(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::today()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::today()->format('Y-m-d'));

        $rooms = Room::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->with(['property', 'bookings' => function ($query) use ($dateFrom, $dateTo) {
                $query->whereIn('status', ['confirmed', 'checked_in'])
                    ->where(function ($q) use ($dateFrom, $dateTo) {
                        // Check if booking overlaps with the date range
                        $q->where(function ($subQ) use ($dateFrom, $dateTo) {
                            // Booking starts before or on dateTo and ends after or on dateFrom
                            $subQ->where('check_in', '<=', $dateTo)
                                 ->where('check_out', '>=', $dateFrom);
                        });
                    });
            }])
            ->orderBy('room_number')
            ->get();

        $rooms = $rooms->map(function ($room) {
            $currentBooking = $room->bookings->first();
            $status = $room->status;
            
            if ($currentBooking) {
                $status = 'occupied';
            }

            return [
                'room' => $room,
                'status' => $status,
                'last_updated' => $room->updated_at,
            ];
        });

        return view('hotel.reports.room-status', compact('rooms', 'dateFrom', 'dateTo'));
    }

    /**
     * 7. Housekeeping Report
     */
    public function housekeeping(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::today()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::today()->format('Y-m-d'));

        // This would need to be implemented based on your housekeeping system
        // For now, using room status updates as a proxy
        $rooms = Room::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereBetween('updated_at', [$dateFrom, $dateTo])
            ->with(['property'])
            ->orderBy('room_number')
            ->get();

        $housekeepingData = $rooms->map(function ($room) {
            return [
                'room_no' => $room->room_number,
                'cleaning_status' => $room->status === 'available' ? 'Cleaned' : 'Pending',
                'cleaned_by' => 'N/A', // Would need housekeeping staff tracking
                'cleaning_date' => $room->updated_at,
                'remarks' => '',
            ];
        });

        return view('hotel.reports.housekeeping', compact('housekeepingData', 'dateFrom', 'dateTo'));
    }

    /**
     * 8. Guest History Report
     */
    public function guestHistory(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Guest::forCompany(current_company_id())
            ->with(['bookings' => function ($query) use ($dateFrom, $dateTo) {
                $query->forBranch(current_branch_id())
                    ->where('status', '!=', 'cancelled');
                if ($dateFrom) {
                    $query->whereDate('check_in', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate('check_in', '<=', $dateTo);
                }
            }]);

        $guests = $query->get()
            ->map(function ($guest) {
                $bookings = $guest->bookings;
                return [
                    'guest' => $guest,
                    'visits_count' => $bookings->count(),
                    'total_spent' => $bookings->sum('total_amount'),
                    'last_visit' => $bookings->max('check_in'),
                ];
            })
            ->filter(function ($data) {
                return $data['visits_count'] > 0;
            })
            ->sortByDesc('total_spent');

        return view('hotel.reports.guest-history', compact('guests', 'dateFrom', 'dateTo'));
    }

    /**
     * 9. Payment Method Report
     */
    public function paymentMethod(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Use whereDate for proper date comparison
        $receipts = Receipt::where('reference_type', 'hotel_booking')
            ->where('branch_id', current_branch_id())
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->with(['bankAccount', 'user'])
            ->get();

        $paymentMethods = [];
        $cashTotal = 0;
        $mobileMoneyTotal = 0;
        $cardTotal = 0;
        $bankTransferTotal = 0;

        foreach ($receipts as $receipt) {
            $method = $this->getPaymentMethod($receipt);
            $amount = $receipt->amount;

            switch (strtolower($method)) {
                case 'cash':
                    $cashTotal += $amount;
                    break;
                case 'm-pesa':
                case 'mobile money':
                    $mobileMoneyTotal += $amount;
                    break;
                case 'card':
                    $cardTotal += $amount;
                    break;
                case 'bank transfer':
                    $bankTransferTotal += $amount;
                    break;
            }
        }

        $grandTotal = $cashTotal + $mobileMoneyTotal + $cardTotal + $bankTransferTotal;

        $dailyData = $receipts->groupBy(function ($receipt) {
            return $receipt->date->format('Y-m-d');
        })->map(function ($dayReceipts, $date) {
            $cash = 0;
            $mobile = 0;
            $card = 0;
            $bank = 0;

            foreach ($dayReceipts as $receipt) {
                $method = $this->getPaymentMethod($receipt);
                $amount = $receipt->amount;

                switch (strtolower($method)) {
                    case 'cash':
                        $cash += $amount;
                        break;
                    case 'm-pesa':
                    case 'mobile money':
                        $mobile += $amount;
                        break;
                    case 'card':
                        $card += $amount;
                        break;
                    case 'bank transfer':
                        $bank += $amount;
                        break;
                }
            }

            return [
                'date' => $date,
                'cash' => $cash,
                'mobile_money' => $mobile,
                'card' => $card,
                'bank_transfer' => $bank,
                'total' => $cash + $mobile + $card + $bank,
            ];
        })->sortBy('date');

        return view('hotel.reports.payment-method', compact(
            'dateFrom',
            'dateTo',
            'cashTotal',
            'mobileMoneyTotal',
            'cardTotal',
            'bankTransferTotal',
            'grandTotal',
            'dailyData'
        ));
    }

    /**
     * 10. Staff Activity Report
     */
    public function staffActivity(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Get bookings created by staff
        $bookings = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->with('createdBy')
            ->get();

        // Get receipts processed by staff
        $receipts = Receipt::where('reference_type', 'hotel_booking')
            ->where('branch_id', current_branch_id())
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->with('user')
            ->get();

        $staffActivities = collect()
            ->merge($bookings->groupBy('created_by')->map(function ($userBookings, $userId) {
                $user = $userBookings->first()->createdBy;
                return [
                    'staff_name' => $user->name ?? 'N/A',
                    'role' => 'Booking Staff', // Would need role from user
                    'actions' => 'Created ' . $userBookings->count() . ' booking(s)',
                    'total_transactions' => $userBookings->count(),
                    'date' => $userBookings->first()->created_at->format('Y-m-d'),
                ];
            }))
            ->merge($receipts->groupBy('user_id')->map(function ($userReceipts, $userId) {
                $user = $userReceipts->first()->user;
                return [
                    'staff_name' => $user->name ?? 'N/A',
                    'role' => 'Payment Staff',
                    'actions' => 'Processed ' . $userReceipts->count() . ' payment(s)',
                    'total_transactions' => $userReceipts->count(),
                    'date' => $userReceipts->first()->date->format('Y-m-d'),
                ];
            }))
            ->groupBy('staff_name')
            ->map(function ($activities, $staffName) {
                return [
                    'staff_name' => $staffName,
                    'role' => $activities->first()['role'],
                    'actions' => $activities->pluck('actions')->implode(', '),
                    'total_transactions' => $activities->sum('total_transactions'),
                    'date' => $activities->first()['date'],
                ];
            })
            ->values();

        return view('hotel.reports.staff-activity', compact('staffActivities', 'dateFrom', 'dateTo'));
    }

    /**
     * 11. Profit & Loss Summary
     */
    public function profitLoss(Request $request)
    {
        $period = $request->get('period', 'month');
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        if ($period === 'month') {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
            $periodLabel = Carbon::create($year, $month, 1)->format('F Y');
        } else {
            $startDate = Carbon::create($year, 1, 1)->startOfYear();
            $endDate = Carbon::create($year, 12, 31)->endOfYear();
            $periodLabel = $year;
        }

        // Total Revenue from bookings
        $totalRevenue = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereBetween('check_in', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        // Operating Expenses (from hotel expenses)
        $operatingExpenses = Payment::where('payee_type', 'hotel')
            ->where('reference_type', 'hotel_expense')
            ->where('branch_id', current_branch_id())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('paymentItems')
            ->get()
            ->sum(function ($payment) {
                return $payment->paymentItems->sum('amount');
            });

        $netProfit = $totalRevenue - $operatingExpenses;

        return view('hotel.reports.profit-loss', compact(
            'period',
            'year',
            'month',
            'periodLabel',
            'totalRevenue',
            'operatingExpenses',
            'netProfit'
        ));
    }

    // ==================== PDF EXPORT METHODS ====================

    /**
     * Export Daily Occupancy Report to PDF
     */
    public function dailyOccupancyExportPdf(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);

        $totalRooms = Room::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->count();

        $occupiedRooms = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->whereDate('check_in', '<=', $selectedDate)
            ->whereDate('check_out', '>=', $selectedDate)
            ->distinct('room_id')
            ->count('room_id');

        $reservedRooms = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->where('status', 'pending')
            ->whereDate('check_in', '>', $selectedDate)
            ->distinct('room_id')
            ->count('room_id');

        $availableRooms = $totalRooms - $occupiedRooms;
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

        $company = Company::find(current_company_id());
        $branch = Branch::find(current_branch_id());
        $generatedAt = now();

        $pdf = Pdf::loadView('hotel.reports.pdf.daily-occupancy', compact(
            'date', 'totalRooms', 'occupiedRooms', 'availableRooms', 'reservedRooms', 
            'occupancyRate', 'company', 'branch', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('daily_occupancy_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Daily Sales Report to PDF
     */
    public function dailySalesExportPdf(Request $request)
    {
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);

        $receipts = Receipt::where('reference_type', 'hotel_booking')
            ->where('branch_id', current_branch_id())
            ->whereDate('date', $selectedDate)
            ->with(['user', 'bankAccount', 'receiptItems'])
            ->get();

        $salesData = $receipts->map(function ($receipt) {
            $booking = Booking::where('booking_number', $receipt->reference_number)
                ->with(['guest', 'room'])
                ->first();

            return [
                'receipt' => $receipt,
                'booking' => $booking,
                'guest_name' => $booking ? $booking->guest->first_name . ' ' . $booking->guest->last_name : 'N/A',
                'room_no' => $booking ? $booking->room->room_number : 'N/A',
                'payment_method' => $this->getPaymentMethod($receipt),
                'amount' => $receipt->amount,
                'received_by' => $receipt->user->name ?? 'N/A',
            ];
        });

        $totalAmount = $receipts->sum('amount');
        $company = Company::find(current_company_id());
        $branch = Branch::find(current_branch_id());
        $generatedAt = now();

        $pdf = Pdf::loadView('hotel.reports.pdf.daily-sales', compact(
            'date', 'salesData', 'totalAmount', 'company', 'branch', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('daily_sales_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Monthly Revenue Report to PDF
     */
    public function monthlyRevenueExportPdf(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $bookings = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereBetween('check_in', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->get();

        $roomRevenue = $bookings->sum('total_amount');
        $totalBookings = $bookings->count();
        $extraServicesRevenue = 0;
        $totalRevenue = $roomRevenue + $extraServicesRevenue;

        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthStart = Carbon::create($year, $m, 1)->startOfMonth();
            $monthEnd = Carbon::create($year, $m, 1)->endOfMonth();
            
            $monthBookings = Booking::forBranch(current_branch_id())
                ->forCompany(current_company_id())
                ->whereBetween('check_in', [$monthStart, $monthEnd])
                ->where('status', '!=', 'cancelled')
                ->get();

            $monthlyData[] = [
                'month' => Carbon::create($year, $m, 1)->format('F Y'),
                'total_bookings' => $monthBookings->count(),
                'room_revenue' => $monthBookings->sum('total_amount'),
                'extra_services_revenue' => 0,
                'total_revenue' => $monthBookings->sum('total_amount'),
            ];
        }

        $company = Company::find(current_company_id());
        $branch = Branch::find(current_branch_id());
        $generatedAt = now();

        $pdf = Pdf::loadView('hotel.reports.pdf.monthly-revenue', compact(
            'year', 'month', 'totalBookings', 'roomRevenue', 'extraServicesRevenue',
            'totalRevenue', 'monthlyData', 'company', 'branch', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('monthly_revenue_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Booking Report to PDF
     */
    public function bookingReportExportPdf(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $bookings = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereBetween('check_in', [$dateFrom, $dateTo])
            ->with(['guest', 'room', 'room.property'])
            ->orderBy('check_in', 'desc')
            ->get();

        $company = Company::find(current_company_id());
        $branch = Branch::find(current_branch_id());
        $generatedAt = now();

        $pdf = Pdf::loadView('hotel.reports.pdf.booking', compact(
            'bookings', 'dateFrom', 'dateTo', 'company', 'branch', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('booking_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Check-In & Check-Out Report to PDF
     */
    public function checkInOutExportPdf(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::today()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::today()->format('Y-m-d'));

        $checkIns = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereBetween('check_in', [$dateFrom, $dateTo])
            ->whereIn('status', ['checked_in', 'checked_out'])
            ->with(['guest', 'room', 'createdBy'])
            ->get();

        $checkOuts = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereBetween('check_out', [$dateFrom, $dateTo])
            ->whereIn('status', ['checked_out'])
            ->with(['guest', 'room', 'createdBy'])
            ->get();

        $movements = collect()
            ->merge($checkIns->map(function ($booking) {
                return [
                    'type' => 'Check-In',
                    'guest_name' => $booking->guest->first_name . ' ' . $booking->guest->last_name,
                    'room_no' => $booking->room->room_number,
                    'time' => $booking->check_in_time ?? $booking->check_in,
                    'processed_by' => $booking->createdBy->name ?? 'N/A',
                    'stay_duration' => $booking->nights . ' night(s)',
                ];
            }))
            ->merge($checkOuts->map(function ($booking) {
                return [
                    'type' => 'Check-Out',
                    'guest_name' => $booking->guest->first_name . ' ' . $booking->guest->last_name,
                    'room_no' => $booking->room->room_number,
                    'time' => $booking->check_out_time ?? $booking->check_out,
                    'processed_by' => $booking->createdBy->name ?? 'N/A',
                    'stay_duration' => $booking->nights . ' night(s)',
                ];
            }))
            ->sortByDesc('time');

        $company = Company::find(current_company_id());
        $branch = Branch::find(current_branch_id());
        $generatedAt = now();

        $pdf = Pdf::loadView('hotel.reports.pdf.check-in-out', compact(
            'movements', 'dateFrom', 'dateTo', 'company', 'branch', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('check_in_out_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Room Status Report to PDF
     */
    public function roomStatusExportPdf(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::today()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::today()->format('Y-m-d'));

        $rooms = Room::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->with(['property', 'bookings' => function ($query) use ($dateFrom, $dateTo) {
                $query->whereIn('status', ['confirmed', 'checked_in'])
                    ->where(function ($q) use ($dateFrom, $dateTo) {
                        // Check if booking overlaps with the date range
                        $q->where(function ($subQ) use ($dateFrom, $dateTo) {
                            // Booking starts before or on dateTo and ends after or on dateFrom
                            $subQ->where('check_in', '<=', $dateTo)
                                 ->where('check_out', '>=', $dateFrom);
                        });
                    });
            }])
            ->orderBy('room_number')
            ->get();

        $rooms = $rooms->map(function ($room) {
            $currentBooking = $room->bookings->first();
            $status = $room->status;
            
            if ($currentBooking) {
                $status = 'occupied';
            }

            return [
                'room' => $room,
                'status' => $status,
                'last_updated' => $room->updated_at,
            ];
        });

        $company = Company::find(current_company_id());
        $branch = Branch::find(current_branch_id());
        $generatedAt = now();

        $pdf = Pdf::loadView('hotel.reports.pdf.room-status', compact(
            'rooms', 'company', 'branch', 'generatedAt', 'dateFrom', 'dateTo'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('room_status_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Housekeeping Report to PDF
     */
    public function housekeepingExportPdf(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::today()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::today()->format('Y-m-d'));

        $rooms = Room::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereBetween('updated_at', [$dateFrom, $dateTo])
            ->with(['property'])
            ->orderBy('room_number')
            ->get();

        $housekeepingData = $rooms->map(function ($room) {
            return [
                'room_no' => $room->room_number,
                'cleaning_status' => $room->status === 'available' ? 'Cleaned' : 'Pending',
                'cleaned_by' => 'N/A',
                'cleaning_date' => $room->updated_at,
                'remarks' => '',
            ];
        });

        $company = Company::find(current_company_id());
        $branch = Branch::find(current_branch_id());
        $generatedAt = now();

        $pdf = Pdf::loadView('hotel.reports.pdf.housekeeping', compact(
            'housekeepingData', 'dateFrom', 'dateTo', 'company', 'branch', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('housekeeping_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Guest History Report to PDF
     */
    public function guestHistoryExportPdf(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Guest::forCompany(current_company_id())
            ->with(['bookings' => function ($query) use ($dateFrom, $dateTo) {
                $query->forBranch(current_branch_id())
                    ->where('status', '!=', 'cancelled');
                if ($dateFrom) {
                    $query->whereDate('check_in', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate('check_in', '<=', $dateTo);
                }
            }]);

        $guests = $query->get()
            ->map(function ($guest) {
                $bookings = $guest->bookings;
                return [
                    'guest' => $guest,
                    'visits_count' => $bookings->count(),
                    'total_spent' => $bookings->sum('total_amount'),
                    'last_visit' => $bookings->max('check_in'),
                ];
            })
            ->filter(function ($data) {
                return $data['visits_count'] > 0;
            })
            ->sortByDesc('total_spent');

        $company = Company::find(current_company_id());
        $branch = Branch::find(current_branch_id());
        $generatedAt = now();

        $pdf = Pdf::loadView('hotel.reports.pdf.guest-history', compact(
            'guests', 'dateFrom', 'dateTo', 'company', 'branch', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('guest_history_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Payment Method Report to PDF
     */
    public function paymentMethodExportPdf(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $receipts = Receipt::where('reference_type', 'hotel_booking')
            ->where('branch_id', current_branch_id())
            ->whereDate('date', '>=', $dateFrom)
            ->whereDate('date', '<=', $dateTo)
            ->with(['bankAccount', 'user'])
            ->get();

        $cashTotal = 0;
        $mobileMoneyTotal = 0;
        $cardTotal = 0;
        $bankTransferTotal = 0;

        foreach ($receipts as $receipt) {
            $method = $this->getPaymentMethod($receipt);
            $amount = $receipt->amount;

            switch (strtolower($method)) {
                case 'cash':
                    $cashTotal += $amount;
                    break;
                case 'm-pesa':
                case 'mobile money':
                    $mobileMoneyTotal += $amount;
                    break;
                case 'card':
                    $cardTotal += $amount;
                    break;
                case 'bank transfer':
                    $bankTransferTotal += $amount;
                    break;
            }
        }

        $grandTotal = $cashTotal + $mobileMoneyTotal + $cardTotal + $bankTransferTotal;

        $dailyData = $receipts->groupBy(function ($receipt) {
            return $receipt->date->format('Y-m-d');
        })->map(function ($dayReceipts, $date) {
            $cash = 0;
            $mobile = 0;
            $card = 0;
            $bank = 0;

            foreach ($dayReceipts as $receipt) {
                $method = $this->getPaymentMethod($receipt);
                $amount = $receipt->amount;

                switch (strtolower($method)) {
                    case 'cash':
                        $cash += $amount;
                        break;
                    case 'm-pesa':
                    case 'mobile money':
                        $mobile += $amount;
                        break;
                    case 'card':
                        $card += $amount;
                        break;
                    case 'bank transfer':
                        $bank += $amount;
                        break;
                }
            }

            return [
                'date' => $date,
                'cash' => $cash,
                'mobile_money' => $mobile,
                'card' => $card,
                'bank_transfer' => $bank,
                'total' => $cash + $mobile + $card + $bank,
            ];
        })->sortBy('date');

        $company = Company::find(current_company_id());
        $branch = Branch::find(current_branch_id());
        $generatedAt = now();

        $pdf = Pdf::loadView('hotel.reports.pdf.payment-method', compact(
            'dateFrom', 'dateTo', 'cashTotal', 'mobileMoneyTotal', 'cardTotal',
            'bankTransferTotal', 'grandTotal', 'dailyData', 'company', 'branch', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('payment_method_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Staff Activity Report to PDF
     */
    public function staffActivityExportPdf(Request $request)
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $bookings = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->with('createdBy')
            ->get();

        $receipts = Receipt::where('reference_type', 'hotel_booking')
            ->where('branch_id', current_branch_id())
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->with('user')
            ->get();

        $staffActivities = collect()
            ->merge($bookings->groupBy('created_by')->map(function ($userBookings, $userId) {
                $user = $userBookings->first()->createdBy;
                return [
                    'staff_name' => $user->name ?? 'N/A',
                    'role' => 'Booking Staff',
                    'actions' => 'Created ' . $userBookings->count() . ' booking(s)',
                    'total_transactions' => $userBookings->count(),
                    'date' => $userBookings->first()->created_at->format('Y-m-d'),
                ];
            }))
            ->merge($receipts->groupBy('user_id')->map(function ($userReceipts, $userId) {
                $user = $userReceipts->first()->user;
                return [
                    'staff_name' => $user->name ?? 'N/A',
                    'role' => 'Payment Staff',
                    'actions' => 'Processed ' . $userReceipts->count() . ' payment(s)',
                    'total_transactions' => $userReceipts->count(),
                    'date' => $userReceipts->first()->date->format('Y-m-d'),
                ];
            }))
            ->groupBy('staff_name')
            ->map(function ($activities, $staffName) {
                return [
                    'staff_name' => $staffName,
                    'role' => $activities->first()['role'],
                    'actions' => $activities->pluck('actions')->implode(', '),
                    'total_transactions' => $activities->sum('total_transactions'),
                    'date' => $activities->first()['date'],
                ];
            })
            ->values();

        $company = Company::find(current_company_id());
        $branch = Branch::find(current_branch_id());
        $generatedAt = now();

        $pdf = Pdf::loadView('hotel.reports.pdf.staff-activity', compact(
            'staffActivities', 'dateFrom', 'dateTo', 'company', 'branch', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'landscape');
        return $pdf->download('staff_activity_report_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Profit & Loss Report to PDF
     */
    public function profitLossExportPdf(Request $request)
    {
        $period = $request->get('period', 'month');
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month', Carbon::now()->month);

        if ($period === 'month') {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
            $periodLabel = Carbon::create($year, $month, 1)->format('F Y');
        } else {
            $startDate = Carbon::create($year, 1, 1)->startOfYear();
            $endDate = Carbon::create($year, 12, 31)->endOfYear();
            $periodLabel = $year;
        }

        $totalRevenue = Booking::forBranch(current_branch_id())
            ->forCompany(current_company_id())
            ->whereBetween('check_in', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $operatingExpenses = Payment::where('payee_type', 'hotel')
            ->where('reference_type', 'hotel_expense')
            ->where('branch_id', current_branch_id())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('paymentItems')
            ->get()
            ->sum(function ($payment) {
                return $payment->paymentItems->sum('amount');
            });

        $netProfit = $totalRevenue - $operatingExpenses;

        $company = Company::find(current_company_id());
        $branch = Branch::find(current_branch_id());
        $generatedAt = now();

        $pdf = Pdf::loadView('hotel.reports.pdf.profit-loss', compact(
            'period', 'year', 'month', 'periodLabel', 'totalRevenue',
            'operatingExpenses', 'netProfit', 'company', 'branch', 'generatedAt'
        ));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('profit_loss_report_' . date('Y-m-d') . '.pdf');
    }
}
