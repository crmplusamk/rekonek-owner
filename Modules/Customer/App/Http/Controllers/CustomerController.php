<?php

namespace Modules\Customer\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\Customer\App\Http\Requests\CustomerCreateRequest;
use Modules\Customer\App\Http\Requests\CustomerUpdateRequest;
use Modules\Customer\App\Repositories\CustomerRepository;

class CustomerController extends Controller
{
    public $customerRepo;

    public function __construct(CustomerRepository $customerRepo)
    {
        $this->customerRepo = $customerRepo;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('customer::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {

            return view('customer::create');

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }

    public function list(Request $request)
    {
        return $this->customerRepo->list($request->all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomerCreateRequest $request)
    {
        DB::beginTransaction();
        try {

            $this->customerRepo->create($request->all());

            DB::commit();
            notify()->success("Berhasil membuat data customer");
            return to_route('customer.index');

        } catch (\Exception $e) {

            DB::rollBack();
            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        try {
            // Get customer data from backoffice database
            $customer = $this->customerRepo->detail($id);
            
            if (!$customer->company_id) {
                notify()->error("Customer tidak memiliki company_id yang valid.");
                return back();
            }
            
            $companyId = $customer->company_id;
            $oneMonthAgo = now()->subMonth()->format('Y-m-d');
            $today = now()->format('Y-m-d');
            
            // Get company info from client database
            $company = DB::connection('client')
                ->table('companies')
                ->where('id', $companyId)
                ->first();
            
            // Get summary counts
            $stats = [
                'contacts_count' => DB::connection('client')->table('contacts')->where('company_id', $companyId)->count(),
                'contacts_last_date' => DB::connection('client')->table('contacts')->where('company_id', $companyId)->max('created_at'),
                'total_channel_count' => DB::connection('client')->table('chat_sessions')->where('company_id', $companyId)->count(),
                'sessions_last_date' => DB::connection('client')->table('chat_sessions')->where('company_id', $companyId)->max('created_at'),
                'tasks_count' => DB::connection('client')->table('tasks')->where('company_id', $companyId)->count(),
                'tasks_last_date' => DB::connection('client')->table('tasks')->where('company_id', $companyId)->max('created_at'),
                'users_count' => DB::connection('client')->table('users')->where('company_id', $companyId)->count(),
                'conversations_count' => DB::connection('client')->table('conversations')->where('company_id', $companyId)->count(),
                'conversations_last_date' => DB::connection('client')->table('conversations')->where('company_id', $companyId)->max('created_at'),
                'total_revenue' => DB::connection('client')->table('invoices')->where('company_id', $companyId)->sum('total') ?? 0
            ];
            
            // Get recent data (limit 10)
            $recentContacts = DB::connection('client')
                ->table('contacts')
                ->where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
                
            $recentTasks = DB::connection('client')
                ->table('tasks')
                ->where('company_id', $companyId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
                
            $recentConversations = DB::connection('client')
                ->table('conversations')
                ->leftJoin('contacts', 'conversations.contact_id', '=', 'contacts.id')
                ->where('conversations.company_id', $companyId)
                ->select('conversations.*', 'contacts.name as contact_name')
                ->orderBy('conversations.last_chat', 'desc')
                ->limit(10)
                ->get();
            
            // Get 30-day statistics for charts (optimized with single query)
            $startDate = now()->subDays(29)->startOfDay();
            $endDate = now()->endOfDay();
            
            // Get contacts by date with segmentation (total, leads, customers)
            $contactsByDate = DB::connection('client')
                ->table('contacts')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total, SUM(CASE WHEN is_customer = 0 THEN 1 ELSE 0 END) as leads, SUM(CASE WHEN is_customer = 1 THEN 1 ELSE 0 END) as customers')
                ->where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at)')
                ->get()
                ->keyBy('date');
            
            // Get conversations by date with segmentation (total, open, closed)
            $conversationsByDate = DB::connection('client')
                ->table('conversations')
                ->selectRaw('DATE(last_chat) as date, COUNT(*) as total, SUM(CASE WHEN status::text = \'1\' THEN 1 ELSE 0 END) as open, SUM(CASE WHEN status::text = \'0\' THEN 1 ELSE 0 END) as closed')
                ->where('company_id', $companyId)
                ->whereBetween('last_chat', [$startDate, $endDate])
                ->groupByRaw('DATE(last_chat)')
                ->orderByRaw('DATE(last_chat)')
                ->get()
                ->keyBy('date');
            
            // Get tasks by date with segmentation (total, finished, not_started)
            $tasksByDate = DB::connection('client')
                ->table('tasks')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total, SUM(CASE WHEN status = \'finished\' THEN 1 ELSE 0 END) as finished, SUM(CASE WHEN status = \'not started\' THEN 1 ELSE 0 END) as not_started')
                ->where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at)')
                ->get()
                ->keyBy('date');
            
            // Build daily stats array
            $dailyStats = [];
            for ($i = 29; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $contactData = $contactsByDate->get($date);
                $conversationData = $conversationsByDate->get($date);
                $taskData = $tasksByDate->get($date);
                
                $dailyStats[] = [
                    'date' => $date,
                    'contacts' => [
                        'total' => $contactData ? $contactData->total : 0,
                        'leads' => $contactData ? $contactData->leads : 0,
                        'customers' => $contactData ? $contactData->customers : 0
                    ],
                    'conversations' => [
                        'total' => $conversationData ? $conversationData->total : 0,
                        'open' => $conversationData ? $conversationData->open : 0,
                        'closed' => $conversationData ? $conversationData->closed : 0
                    ],
                    'tasks' => [
                        'total' => $taskData ? $taskData->total : 0,
                        'finished' => $taskData ? $taskData->finished : 0,
                        'not_started' => $taskData ? $taskData->not_started : 0
                    ]
                ];
            }
            
            // Get recent user logins (5 most recent)
            $recentLogins = DB::connection('client')
                ->table('users')
                ->where('company_id', $companyId)
                ->whereNotNull('last_login_at')
                ->orderBy('last_login_at', 'desc')
                ->limit(5)
                ->select('name', 'last_login_at')
                ->get();
            
            // Get invoices data from backoffice database
            $invoices = DB::table('invoices')
                ->where('customer_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Get invoice items for each invoice with related package/addon names
            foreach ($invoices as $invoice) {
                $items = DB::table('invoice_items')
                    ->where('invoice_id', $invoice->id)
                    ->get();
                
                // Load the related package or addon name for each item
                foreach ($items as $item) {
                    if ($item->itemable_type === 'Modules\\Package\\App\\Models\\Package') {
                        $package = DB::table('packages')->where('id', $item->itemable_id)->first();
                        $item->name = $package ? $package->name : 'Unknown Package';
                    } elseif ($item->itemable_type === 'Modules\\Addon\\App\\Models\\Addon') {
                        $addon = DB::table('addons')->where('id', $item->itemable_id)->first();
                        $item->name = $addon ? $addon->name : 'Unknown Addon';
                    } else {
                        $item->name = 'Unknown Item';
                    }
                }
                
                $invoice->items = $items;
            }
            
            // Access Log Progress - Static stages in order
            $accessLogStages = [
                'request_token' => 'Request Token',
                'token_verified' => 'Token Verified',
                'registration_success' => 'Registration Success',
                'email_verified_success' => 'Email Verified',
                'first_login_success' => 'First Login',
                'onboarding_completed' => 'Onboarding Completed',
                'trial_activated' => 'Trial Activated'
            ];
            
            // Query access logs for this customer
            // Need to get logs by both company_id AND email because early logs (request_token, token_verified) 
            // might not have company_id yet but have email
            $accessLogs = DB::table('access_logs')
                ->where(function($query) use ($customer) {
                    // Get logs that match either company_id OR email for this customer
                    if (!empty($customer->company_id)) {
                        $query->where('company_id', $customer->company_id);
                    }
                    if (!empty($customer->email)) {
                        $query->orWhere('email', $customer->email);
                    }
                })
                ->whereIn('progress', array_keys($accessLogStages))
                ->orderBy('created_at')
                ->get();
            
            // Get completed stages
            $completedStages = $accessLogs->pluck('progress')->unique()->values()->toArray();
            
            $data = [
                'customer' => $customer,
                'company' => $company,
                'stats' => $stats,
                'recent_contacts' => $recentContacts,
                'recent_tasks' => $recentTasks,
                'recent_conversations' => $recentConversations,
                'recent_logins' => $recentLogins,
                'daily_stats' => $dailyStats,
                'access_log_stages' => $accessLogStages,
                'completed_stages' => $completedStages,
                'access_logs' => $accessLogs,
                'invoices' => $invoices
            ];
            
            return view('customer::show', compact('data'));

        } catch (\Exception $e) {
            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {

            $data =  $this->customerRepo->detail($id);
            return view('customer::edit', compact('data'));

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomerUpdateRequest $request, $id)
    {
        try {

            $this->customerRepo->update($request->all(), $id);

            notify()->success("Berhasil mengubah data customer");
            return to_route('customer.index');

        } catch (\Exception $e) {

            DB::rollBack();
            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $user = $this->customerRepo->delete($id);

            if ($user != 403) {
                notify()->success("Berhasil menghapus data customer");
                return to_route('customer.index');
            }

            notify()->warning("Customer tidak dapat dihapus");
            return back();

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back();
        }
    }

    public function status($id)
    {
        try {

            $this->customerRepo->status($id);

            notify()->success("Berhasil mengubah data customer");
            return to_route('customer.index');

        } catch (\Exception $e) {

            notify()->error("Terjadi kesalahan. ".$e->getMessage());
            return back()->withInput();
        }
    }

    public function datatable(Request $request)
    {
        return $this->customerRepo->datatable();
    }

    /**
     * Get invoice detail with items and logs
     */
    public function getInvoiceDetail($customerId, $invoiceId)
    {
        try {
            $invoice = DB::table('invoices')
                ->where('id', $invoiceId)
                ->where('customer_id', $customerId)
                ->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice tidak ditemukan'
                ], 404);
            }

            // Get invoice items
            $items = DB::table('invoice_items')
                ->where('invoice_id', $invoiceId)
                ->get();
            
            // Load the related package or addon name for each item
            foreach ($items as $item) {
                if ($item->itemable_type === 'Modules\\Package\\App\\Models\\Package') {
                    $package = DB::table('packages')->where('id', $item->itemable_id)->first();
                    $item->name = $package ? $package->name : 'Unknown Package';
                } elseif ($item->itemable_type === 'Modules\\Addon\\App\\Models\\Addon') {
                    $addon = DB::table('addons')->where('id', $item->itemable_id)->first();
                    $item->name = $addon ? $addon->name : 'Unknown Addon';
                } else {
                    $item->name = 'Unknown Item';
                }
            }
            
            $invoice->items = $items;

            // Get logs related to this invoice
            $invoice->logs = DB::table('logs')
                ->where('fid', $invoiceId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}

