<?php

namespace App\Http\Controllers;

use App\Models\AccountNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotificationInboxController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isStudent() || $user->isTeacher() || $user->hasAdminAccess(), 403);
        $data = $request->validate(['filter' => ['nullable', Rule::in(['all', 'unread'])]]);
        $filter = $data['filter'] ?? 'all';
        $inboxReady = Schema::hasTable('account_notifications');
        $openedThrough = $inboxReady ? (int) AccountNotification::where('user_id', $user->id)->max('id') : 0;
        $notifications = $inboxReady ? AccountNotification::where('user_id', $user->id)
            ->where('id', '<=', $openedThrough)
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest('id')->paginate(20)->withQueryString() : null;
        $notificationLayout = $user->isStudent() || $user->isTeacher() ? 'layouts.consultations' : 'admin.layout';
        return view('notifications', compact('notifications', 'filter', 'inboxReady', 'notificationLayout', 'openedThrough'));
    }

    public function summary(Request $request): JsonResponse
    {
        $count = Schema::hasTable('account_notifications')
            ? AccountNotification::where('user_id', $request->user()->id)->whereNull('read_at')->count() : 0;
        return response()->json(['unread' => $count])->header('Cache-Control', 'no-store');
    }

    public function read(Request $request, int $id): RedirectResponse
    {
        abort_unless(Schema::hasTable('account_notifications'), 503, 'Notification database setup is missing. Run migrations.');
        $notification = AccountNotification::where('user_id', $request->user()->id)->findOrFail($id);
        if (!$notification->read_at) $notification->update(['read_at' => now()]);
        return redirect($notification->destination());
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['through' => ['nullable', 'integer', 'min:0']]);
        $ready = Schema::hasTable('account_notifications');
        if ($ready) {
            AccountNotification::where('user_id', $request->user()->id)->whereNull('read_at')
                ->when(isset($data['through']), fn ($query) => $query->where('id', '<=', $data['through']))->update(['read_at' => now()]);
        }
        if ($request->expectsJson()) {
            return response()->json(['unread' => $ready ? AccountNotification::where('user_id', $request->user()->id)->whereNull('read_at')->count() : 0])
                ->header('Cache-Control', 'no-store');
        }
        return back()->with('status', 'Your notifications were marked as read.');
    }
}
