<?php

namespace App\Services\Leave;

use App\Models\Hr\Employee;
use App\Models\Hr\LeaveRequest;
use App\Models\Hr\LeaveSmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LeaveSmsService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $secretKey;
    protected string $senderId;

    public function __construct()
    {
        $this->apiUrl = config('services.sms.url') ?? 'https://apisms.beem.africa/v1/send';
        $this->apiKey = config('services.sms.key') ?? config('services.beem.api_key') ?? '';
        $this->secretKey = config('services.sms.token') ?? config('services.beem.secret_key') ?? '';
        $this->senderId = config('services.sms.senderid') ?? config('services.beem.sender_id') ?? 'SAFCO';
    }

    /**
     * Send SMS notification when leave is requested
     */
    public function sendRequestSubmitted(LeaveRequest $leaveRequest): void
    {
        $employee = $leaveRequest->employee;
        $manager = $employee->manager; // Assuming relationship exists

        // Notify manager
        if ($manager && $manager->mobile) {
            $message = $this->buildRequestSubmittedMessage($leaveRequest, $manager);
            $this->sendSms($leaveRequest, $manager, $message, 'request_submitted');
        }

        // Notify HR if no manager or direct to HR
        if (!$manager || $leaveRequest->status === 'pending_hr') {
            $this->notifyHR($leaveRequest, 'request_submitted');
        }
    }

    /**
     * Send SMS notification when leave is approved
     */
    public function sendRequestApproved(LeaveRequest $leaveRequest): void
    {
        $employee = $leaveRequest->employee;

        if ($employee->mobile) {
            $message = $this->buildRequestApprovedMessage($leaveRequest);
            $this->sendSms($leaveRequest, $employee, $message, 'request_approved');
        }

        // Notify reliever if assigned
        if ($leaveRequest->reliever && $leaveRequest->reliever->mobile) {
            $message = $this->buildRelieverAssignedMessage($leaveRequest);
            $this->sendSms($leaveRequest, $leaveRequest->reliever, $message, 'reliever_assigned');
        }
    }

    /**
     * Send SMS notification when leave is rejected
     */
    public function sendRequestRejected(LeaveRequest $leaveRequest): void
    {
        $employee = $leaveRequest->employee;

        if ($employee->mobile) {
            $message = $this->buildRequestRejectedMessage($leaveRequest);
            $this->sendSms($leaveRequest, $employee, $message, 'request_rejected');
        }
    }

    /**
     * Send SMS notification when leave is returned for edit
     */
    public function sendRequestReturned(LeaveRequest $leaveRequest): void
    {
        $employee = $leaveRequest->employee;

        if ($employee->mobile) {
            $message = $this->buildRequestReturnedMessage($leaveRequest);
            $this->sendSms($leaveRequest, $employee, $message, 'request_returned');
        }
    }

    /**
     * Send pending approval reminder to approver
     */
    public function sendPendingApprovalReminder(LeaveRequest $leaveRequest, Employee $approver): void
    {
        if ($approver->mobile) {
            $message = $this->buildPendingApprovalMessage($leaveRequest, $approver);
            $this->sendSms($leaveRequest, $approver, $message, 'pending_approval');
        }
    }

    /**
     * Build message for request submitted
     */
    protected function buildRequestSubmittedMessage(LeaveRequest $leaveRequest, Employee $recipient): string
    {
        $employee = $leaveRequest->employee;
        $leaveType = $leaveRequest->leaveType;
        $segment = $leaveRequest->segments->first();

        return sprintf(
            "Leave Request Alert!\n\n%s has requested %s leave for %.1f days from %s to %s.\n\nRequest #: %s\nReason: %s\n\nPlease review and approve/reject.",
            $employee->full_name,
            $leaveType->name,
            $leaveRequest->total_days,
            $segment->start_at->format('d M Y'),
            $segment->end_at->format('d M Y'),
            $leaveRequest->request_number,
            str_limit($leaveRequest->reason ?? 'N/A', 50)
        );
    }

    /**
     * Build message for request approved
     */
    protected function buildRequestApprovedMessage(LeaveRequest $leaveRequest): string
    {
        $leaveType = $leaveRequest->leaveType;
        $segment = $leaveRequest->segments->first();

        return sprintf(
            "Leave Approved!\n\nYour %s leave request (#%s) for %.1f days from %s to %s has been approved.\n\nEnjoy your time off!",
            $leaveType->name,
            $leaveRequest->request_number,
            $leaveRequest->total_days,
            $segment->start_at->format('d M Y'),
            $segment->end_at->format('d M Y')
        );
    }

    /**
     * Build message for request rejected
     */
    protected function buildRequestRejectedMessage(LeaveRequest $leaveRequest): string
    {
        $leaveType = $leaveRequest->leaveType;

        return sprintf(
            "Leave Request Rejected\n\nYour %s leave request (#%s) for %.1f days has been rejected.\n\nReason: %s\n\nPlease contact your manager or HR for more details.",
            $leaveType->name,
            $leaveRequest->request_number,
            $leaveRequest->total_days,
            str_limit($leaveRequest->rejection_reason ?? 'Not provided', 80)
        );
    }

    /**
     * Build message for request returned
     */
    protected function buildRequestReturnedMessage(LeaveRequest $leaveRequest): string
    {
        $leaveType = $leaveRequest->leaveType;
        $lastApproval = $leaveRequest->approvals()->where('decision', 'returned')->latest()->first();

        return sprintf(
            "Leave Request Returned\n\nYour %s leave request (#%s) has been returned for revision.\n\nComment: %s\n\nPlease edit and resubmit.",
            $leaveType->name,
            $leaveRequest->request_number,
            str_limit($lastApproval?->comment ?? 'Please revise', 80)
        );
    }

    /**
     * Build message for pending approval reminder
     */
    protected function buildPendingApprovalMessage(LeaveRequest $leaveRequest, Employee $recipient): string
    {
        $employee = $leaveRequest->employee;
        $leaveType = $leaveRequest->leaveType;

        return sprintf(
            "Reminder: Pending Leave Approval\n\n%s's %s leave request (#%s) for %.1f days is awaiting your approval.\n\nPlease review at your earliest convenience.",
            $employee->full_name,
            $leaveType->name,
            $leaveRequest->request_number,
            $leaveRequest->total_days
        );
    }

    /**
     * Build message for reliever assignment
     */
    protected function buildRelieverAssignedMessage(LeaveRequest $leaveRequest): string
    {
        $employee = $leaveRequest->employee;
        $leaveType = $leaveRequest->leaveType;
        $segment = $leaveRequest->segments->first();

        return sprintf(
            "Reliever Assignment\n\nYou have been assigned as reliever for %s during their %s leave from %s to %s.\n\nRequest #: %s",
            $employee->full_name,
            $leaveType->name,
            $segment->start_at->format('d M Y'),
            $segment->end_at->format('d M Y'),
            $leaveRequest->request_number
        );
    }

    /**
     * Notify HR team
     */
    protected function notifyHR(LeaveRequest $leaveRequest, string $type): void
    {
        // Get HR employees
        $hrEmployees = Employee::where('company_id', $leaveRequest->company_id)
            ->whereHas('user', function ($q) {
                $q->whereHas('roles', function ($rq) {
                    $rq->where('name', 'HR');
                });
            })
            ->whereNotNull('mobile')
            ->get();

        foreach ($hrEmployees as $hrEmployee) {
            $message = $this->buildRequestSubmittedMessage($leaveRequest, $hrEmployee);
            $this->sendSms($leaveRequest, $hrEmployee, $message, $type);
        }
    }

    /**
     * Send SMS using Beem Africa API (Tanzania)
     */
    protected function sendSms(LeaveRequest $leaveRequest, Employee $recipient, string $message, string $type): void
    {
        // Normalize phone number for Tanzania
        $phoneNumber = $this->normalizePhoneNumber($recipient->mobile);

        // Create SMS log
        $smsLog = LeaveSmsLog::create([
            'leave_request_id' => $leaveRequest->id,
            'recipient_id' => $recipient->id,
            'phone_number' => $phoneNumber,
            'message' => $message,
            'type' => $type,
            'status' => 'queued',
        ]);

        try {
            // Send via Beem Africa API
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'source_addr' => $this->senderId,
                'encoding' => 0,
                'schedule_time' => '',
                'message' => $message,
                'recipients' => [
                    [
                        'recipient_id' => (string) $recipient->id,
                        'dest_addr' => $phoneNumber,
                    ],
                ],
            ]);

            if ($response->successful()) {
                $smsLog->markSent($response->json());

                Log::info('Leave SMS sent successfully', [
                    'leave_request_id' => $leaveRequest->id,
                    'recipient_id' => $recipient->id,
                    'type' => $type,
                ]);
            } else {
                $errorMessage = $response->json()['message'] ?? 'Unknown error';
                $smsLog->markFailed($errorMessage);

                Log::error('Leave SMS failed', [
                    'leave_request_id' => $leaveRequest->id,
                    'recipient_id' => $recipient->id,
                    'error' => $errorMessage,
                    'response' => $response->json(),
                ]);
            }
        } catch (\Exception $e) {
            $smsLog->markFailed($e->getMessage());

            Log::error('Leave SMS exception', [
                'leave_request_id' => $leaveRequest->id,
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Normalize Tanzanian phone number to international format
     * Converts formats like:
     * - 0712345678 to 255712345678
     * - 712345678 to 255712345678
     * - +255712345678 to 255712345678
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Remove leading +
        $phone = ltrim($phone, '+');

        // If starts with 0, replace with 255
        if (str_starts_with($phone, '0')) {
            return '255' . substr($phone, 1);
        }

        // If doesn't start with 255, add it
        if (!str_starts_with($phone, '255')) {
            return '255' . $phone;
        }

        return $phone;
    }

    /**
     * Test SMS configuration
     */
    public function testConfiguration(string $phoneNumber): array
    {
        try {
            $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'source_addr' => $this->senderId,
                'encoding' => 0,
                'message' => 'Test message from Leave Management System. Configuration successful!',
                'recipients' => [
                    [
                        'recipient_id' => '1',
                        'dest_addr' => $normalizedPhone,
                    ],
                ],
            ]);

            return [
                'success' => $response->successful(),
                'response' => $response->json(),
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

