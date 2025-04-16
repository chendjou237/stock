<?php

namespace App\Http\Controllers\hrm;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class EmployeeSessionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Employee::class);

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;

        $sessions = Employee::where('deleted_at', '=', null)
            ->with(['company'])
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('username', 'LIKE', "%{$request->search}%")
                        ->orWhere('email', 'LIKE', "%{$request->search}%");
                });
            });

        $totalRows = $sessions->count();
        if($perPage == "-1"){
            $perPage = $totalRows;
        }
        $sessions = $sessions->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'sessions' => $sessions,
            'totalRows' => $totalRows,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Employee::class);

        $this->validate($request, [
            'employee_id' => 'required|exists:employees,id',
            'session_data' => 'required|array'
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        $employee->session_data = $request->session_data;
        $employee->save();

        return response()->json(['success' => true]);
    }

    public function show($id)
    {
        $employee = Employee::findOrFail($id);
        return response()->json([
            'session_data' => $employee->session_data
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Employee::class);

        $this->validate($request, [
            'session_data' => 'required|array'
        ]);

        $employee = Employee::findOrFail($id);
        $employee->session_data = $request->session_data;
        $employee->save();

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Employee::class);

        $employee = Employee::findOrFail($id);
        $employee->session_data = null;
        $employee->save();

        return response()->json(['success' => true]);
    }
}
