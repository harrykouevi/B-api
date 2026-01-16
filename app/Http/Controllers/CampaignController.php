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
use Illuminate\Support\Facades\Storage;
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
            'message' => 'nullable|string|max:1000',
            // 'message_format' => 'nullable|string|in:plain,markdown,html',
            'image_url' => 'nullable|url|max:2048',
            'image_file' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:4096',
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

        // Temporary: keep campaigns plain text until rich rendering is supported on mobile.
        $messageFormat = 'plain';
        $messageRaw = (string) ($validated['message'] ?? '');
        $messagePlain = trim(html_entity_decode(strip_tags($messageRaw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $messageRaw = $messagePlain;
        $imageUrl = $this->resolveImageUrl($request, $validated);

        if ($validated['audience'] === 'all') {
            try {
                Notification::route('fcm', 'topic')->notify(
                    new CampaignNotification(
                        $validated['title'],
                        $messagePlain,
                        'all',
                        true,
                        'all',
                        $messageRaw,
                        $messageFormat,
                        $imageUrl
                    )
                );
            } catch (Throwable $e) {
                Campaign::create([
                    'title' => $validated['title'],
                    'message' => $messageRaw,
                    'message_format' => $messageFormat,
                    'image_url' => $imageUrl,
                    'audience' => 'all',
                    'sent_via' => 'topic',
                    'topic' => 'all',
                    'sent_count' => null,
                    'failed_count' => 0,
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
                'message' => $messageRaw,
                'message_format' => $messageFormat,
                'image_url' => $imageUrl,
                'audience' => 'all',
                'sent_via' => 'topic',
                'topic' => 'all',
                'sent_count' => null,
                'failed_count' => 0,
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
        $failed = 0;
        $lastError = null;
        try {
            $query->chunkById(200, function ($users) use (&$sent, &$failed, &$lastError, $validated, $messagePlain, $messageRaw, $messageFormat, $imageUrl) {
                if ($users->isEmpty()) {
                    return;
                }
                foreach ($users as $user) {
                    try {
                        Notification::send(
                            $user,
                            new CampaignNotification(
                                $validated['title'],
                                $messagePlain,
                                $validated['audience'],
                                false,
                                'all',
                                $messageRaw,
                                $messageFormat,
                                $imageUrl
                            )
                        );
                        $sent++;
                    } catch (Throwable $e) {
                        $failed++;
                        $lastError = $e->getMessage();
                    }
                }
            });
        } catch (Throwable $e) {
            Campaign::create([
                'title' => $validated['title'],
                'message' => $messageRaw,
                'message_format' => $messageFormat,
                'image_url' => $imageUrl,
                'audience' => $validated['audience'],
                'sent_via' => 'tokens',
                'topic' => null,
                'sent_count' => $sent,
                'failed_count' => $failed,
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
                'message' => $messageRaw,
                'message_format' => $messageFormat,
                'image_url' => $imageUrl,
                'audience' => $validated['audience'],
                'sent_via' => 'tokens',
                'topic' => null,
                'sent_count' => 0,
                'failed_count' => $failed,
                'status' => 'failed',
                'error_message' => $lastError ?? 'no_recipients',
                'sent_at' => now(),
                'created_by' => auth()->id(),
            ]);
            Flash::warning(trans('lang.campaign_no_recipients'));
            return redirect()->back()->withInput();
        }

        $status = $failed > 0 ? 'partial' : 'success';
        Campaign::create([
            'title' => $validated['title'],
            'message' => $messageRaw,
            'message_format' => $messageFormat,
            'image_url' => $imageUrl,
            'audience' => $validated['audience'],
            'sent_via' => 'tokens',
            'topic' => null,
            'sent_count' => $sent,
            'failed_count' => $failed,
            'status' => $status,
            'error_message' => $failed > 0 ? ($lastError ?? 'partial_failures') : null,
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

    private function normalizeMessage(string $message, string $format): string
    {
        if ($format === 'html') {
            $message = strip_tags($message);
        }
        if ($format === 'markdown') {
            $message = preg_replace('/\*\*(.*?)\*\*/', '$1', $message) ?? $message;
            $message = preg_replace('/__(.*?)__/', '$1', $message) ?? $message;
        }

        return trim($message);
    }

    private function resolveImageUrl(Request $request, array $validated): ?string
    {
        if ($request->hasFile('image_file')) {
            $path = $request->file('image_file')->store('campaigns', 'public');
            return Storage::disk('public')->url($path);
        }

        return $validated['image_url'] ?? null;
    }
}
