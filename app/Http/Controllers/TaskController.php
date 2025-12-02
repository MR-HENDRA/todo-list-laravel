<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->get('date', now()->toDateString());

        // Validasi & format tanggal di controller
        $formattedDate = Carbon::parse($selectedDate)
            ->locale('en')
            ->isoFormat('dddd, D MMMM YYYY');

        $tasks = Task::whereDate('date', $selectedDate)->orderBy('created_at', 'desc')->get();

        return view('tasks.index', compact('tasks', 'selectedDate', 'formattedDate'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'date'        => 'required|date',
        ]);

        try {
            $task = Task::create([
                'title'       => $request->title,
                'description' => $request->description,
                'date'        => $request->date,
                'completed'   => false,
            ]);

            return response()->json($task);
        } catch (\Exception $e) {
            \Log::error('Gagal menyimpan task: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal menyimpan task'], 500);
        }
    }

    public function update(Request $request, Task $task)
    {
        $task->update([
            'completed' => (bool) $request->completed,
        ]);

        return response()->json($task);
    }

    public function updateTask(Request $request, Task $task)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $task->update([
            'title'       => $request->title,
            'description' => $request->description,
        ]);

        return response()->json($task);
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return response()->json(['success' => true]);
    }

    public function getAllTasks()
    {
        $tasks = Task::orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($tasks);
    }

    public function getTasksByDate(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        if (! \Carbon\Carbon::hasFormat($date, 'Y-m-d')) {
            return response()->json(['tasks' => [], 'formatted_date' => 'Date not valid']);
        }

        $tasks = Task::whereDate('date', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'tasks' => $tasks,
            'formatted_date' => \Carbon\Carbon::parse($date)->locale('en')->isoFormat('dddd, D MMMM YYYY')
        ]);
    }

    public function showByDate(string $date)
    {
        // Validasi format tanggal
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! Carbon::canBeCreatedFromFormat($date, 'Y-m-d')) {
            abort(404);
        }

        $selectedDate = $date;
        $formattedDate = Carbon::parse($date)->format('l, j F Y');
        $tasks = Task::whereDate('date', $date)->orderBy('created_at', 'desc')->get();

        return view('tasks.index', compact('tasks', 'selectedDate', 'formattedDate'));
    }
}
