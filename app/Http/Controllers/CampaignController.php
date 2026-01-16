<?php
/*
 * File name: CampaignController.php
 * Last modified: 2025.03.09
 * Author: CHARM Platform
 */

namespace App\Http\Controllers;

use App\DataTables\CampaignDataTable;
use App\Models\Campaign;
use App\Models\User;
use App\Notifications\CampaignNotification;
use Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Throwable;

class CampaignController extends Controller
{
    /**
     * Show the campaign creation form.
     *
     * @return View
     */
    public function create(): View
    {
        $defaultTitle = setting('app_name', config('app.name', ''));

        return view('campaigns.create')->with('defaultTitle', $defaultTitle);
    }

    /**
     * Display a listing of campaigns.
     *
     * @param CampaignDataTable $campaignDataTable
     * @return mixed
     */
    public function index(CampaignDataTable $campaignDataTable): mixed
    {
        return $campaignDataTable->render('campaigns.index');
    }

    /**
     * Send a campaign via Firebase push notifications.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'message' => 'required|string|max:1000',
            'audience' => 'required|string|in:all,salon,client',
        ]);

        if (!setting('enable_notifications', false)) {
            Flash::error(trans('lang.campaign_notifications_disabled'));
            return redirect()->back()->withInput();
        }

        $projectId = $this->ensureFirebaseProjectId();
        if ($projectId === '') {
            Flash::error(trans('lang.campaign_missing_project_id'));
            return redirect()->back()->withInput();
        }

        $credentialsPath = config('services.fcm.service_account');
        if (!$credentialsPath || !file_exists($credentialsPath)) {
            Flash::error(trans('lang.campaign_missing_credentials'));
            return redirect()->back()->withInput();
        }

        if ($validated['audience'] === 'all') {
            try {
                Notification::route('fcm', 'topic')->notify(
                    new CampaignNotification($validated['title'], $validated['message'], 'all', true, 'all')
                );
            } catch (Throwable $e) {
                Campaign::create([
                    'title' => $validated['title'],
                    'message' => $validated['message'],
                    'audience' => 'all',
                    'sent_via' => 'topic',
                    'topic' => 'all',
                    'sent_count' => null,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'sent_at' => now(),
                    'created_by' => auth()->id(),
                ]);
                Flash::error($e->getMessage());
                return redirect()->back()->withInput();
            }

            Campaign::create([
                'title' => $validated['title'],
                'message' => $validated['message'],
                'audience' => 'all',
                'sent_via' => 'topic',
                'topic' => 'all',
                'sent_count' => null,
                'status' => 'success',
                'sent_at' => now(),
                'created_by' => auth()->id(),
            ]);

            Flash::success(trans('lang.campaign_sent_topic_success', ['topic' => 'all']));
            return redirect()->route('campaigns.index');
        }

        $query = User::query()
            ->whereNotNull('device_token')
            ->where('device_token', '!=', '');

        if ($validated['audience'] === 'salon') {
            $query->role('salon owner');
        }

        if ($validated['audience'] === 'client') {
            $query->role('customer');
        }

        $sent = 0;
        try {
            $query->chunkById(200, function ($users) use (&$sent, $validated) {
                if ($users->isEmpty()) {
                    return;
                }
                Notification::send($users, new CampaignNotification($validated['title'], $validated['message'], $validated['audience']));
                $sent += $users->count();
            });
        } catch (Throwable $e) {
            Campaign::create([
                'title' => $validated['title'],
                'message' => $validated['message'],
                'audience' => $validated['audience'],
                'sent_via' => 'tokens',
                'topic' => null,
                'sent_count' => $sent,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'sent_at' => now(),
                'created_by' => auth()->id(),
            ]);
            Flash::error($e->getMessage());
            return redirect()->back()->withInput();
        }

        if ($sent === 0) {
            Campaign::create([
                'title' => $validated['title'],
                'message' => $validated['message'],
                'audience' => $validated['audience'],
                'sent_via' => 'tokens',
                'topic' => null,
                'sent_count' => 0,
                'status' => 'failed',
                'error_message' => 'no_recipients',
                'sent_at' => now(),
                'created_by' => auth()->id(),
            ]);
            Flash::warning(trans('lang.campaign_no_recipients'));
            return redirect()->back()->withInput();
        }

        Campaign::create([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'audience' => $validated['audience'],
            'sent_via' => 'tokens',
            'topic' => null,
            'sent_count' => $sent,
            'status' => 'success',
            'sent_at' => now(),
            'created_by' => auth()->id(),
        ]);

        Flash::success(trans('lang.campaign_sent_success', ['count' => $sent]));
        return redirect()->route('campaigns.index');
    }

    private function ensureFirebaseProjectId(): string
    {
        $projectId = trim((string) setting('firebase_project_id', ''));
        if ($projectId === '') {
            return '';
        }

        return $projectId;
    }
}
