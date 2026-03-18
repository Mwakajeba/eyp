<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ManualController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Download the Store Requisition Management User Manual
     */
    public function downloadStoreRequisitionManual()
    {
        $filePath = storage_path('app/public/manuals/Store_Requisition_Management_User_Manual.pdf');
        
        if (!file_exists($filePath)) {
            abort(404, 'Manual not found');
        }

        return response()->download($filePath, 'Store_Requisition_Management_User_Manual.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * View the manual online
     */
    public function viewStoreRequisitionManual()
    {
        $filePath = storage_path('app/public/manuals/Store_Requisition_Management_User_Manual.pdf');
        
        if (!file_exists($filePath)) {
            abort(404, 'Manual not found');
        }

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Store_Requisition_Management_User_Manual.pdf"'
        ]);
    }

    /**
     * Generate a new manual (admin only)
     */
    public function generateManual()
    {
        // Check if user has admin permissions
        if (!auth()->user()->hasRole('Admin')) {
            abort(403, 'Unauthorized');
        }

        try {
            // Run the manual generation script
            $output = shell_exec('cd ' . base_path() . ' && php generate_manual.php 2>&1');
            
            return response()->json([
                'success' => true,
                'message' => 'Manual generated successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate manual: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manual management page
     */
    public function index()
    {
        $manuals = [
            [
                'title' => 'Store Requisition Management System',
                'description' => 'Complete user guide for the store requisition module',
                'file' => 'Store_Requisition_Management_User_Manual.pdf',
                'size' => $this->getFileSize('Store_Requisition_Management_User_Manual.pdf'),
                'last_updated' => $this->getLastModified('Store_Requisition_Management_User_Manual.pdf'),
                'download_route' => 'manuals.store-requisition.download',
                'view_route' => 'manuals.store-requisition.view'
            ]
        ];

        return view('manuals.index', compact('manuals'));
    }

    private function getFileSize($filename)
    {
        $filePath = storage_path('app/public/manuals/' . $filename);
        if (file_exists($filePath)) {
            $bytes = filesize($filePath);
            if ($bytes >= 1024 * 1024) {
                return number_format($bytes / (1024 * 1024), 2) . ' MB';
            } elseif ($bytes >= 1024) {
                return number_format($bytes / 1024, 2) . ' KB';
            } else {
                return $bytes . ' bytes';
            }
        }
        return 'Unknown';
    }

    private function getLastModified($filename)
    {
        $filePath = storage_path('app/public/manuals/' . $filename);
        if (file_exists($filePath)) {
            return date('M j, Y \a\t g:i A', filemtime($filePath));
        }
        return 'Unknown';
    }
}