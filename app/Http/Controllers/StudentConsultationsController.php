<?php

namespace App\Http\Controllers;

use App\Models\ConsultationAppointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentConsultationsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isStudent(), 403);
        $appointments = ConsultationAppointment::where('student_id', $request->user()->id)
            ->with(['teacher', 'availability'])->orderByDesc('starts_at')->orderByDesc('id')->paginate(15);

        return view('student.consultations', compact('appointments'));
    }

    public function seen(Request $request): JsonResponse
    {
        abort_unless($request->user()->isStudent(), 403);
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:15'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);
        ConsultationAppointment::where('student_id', $request->user()->id)->whereIn('id', $data['ids'])
            ->where('status', 'rejected')->whereNull('student_rejection_seen_at')
            ->update(['student_rejection_seen_at' => now()]);

        return response()->json(['count' => ConsultationAppointment::where('student_id', $request->user()->id)->needsStudentAttention()->count()]);
    }
}
