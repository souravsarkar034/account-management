<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'account_number' => 'required|exists:accounts,account_number',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $user = auth()->user();

        $account = Account::where('account_number', $request->account_number)
            ->where('user_id', $user->id)
            ->first();

        if (!$account) {
            return response()->json(['message' => 'Unauthorized access to this account'], 403);
        }

        $transactions = Transaction::where('account_id', $account->id)
            ->when($request->from, function ($query) use ($request) {
                return $query->whereDate('created_at', '>=', $request->from);
            })
            ->when($request->to, function ($query) use ($request) {
                return $query->whereDate('created_at', '<=', $request->to);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Transactions fetched successfully',
            'transactions' => $transactions
        ]);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_number' => 'required|exists:accounts,account_number',
            'type' => 'required|in:Credit,Debit',
            'amount' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ($value <= 0) {
                        $fail('The transaction amount must be a positive number greater than zero.');
                    }
                }
            ],
            'description' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        $account = Account::where('account_number', $request->account_number)
            ->where('user_id', $user->id)
            ->first();

        if (!$account) {
            return response()->json(['message' => 'Unauthorized access to this account'], 403);
        }

        DB::beginTransaction();

        try {

            if ($request->type === 'Debit' && $account->balance < $request->amount) {
                return response()->json(['message' => 'Insufficient balance'], 400);
            }

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'type' => $request->type,
                'amount' => $request->amount,
                'description' => $request->description,
            ]);

            if ($request->type === 'Credit') {
                $account->balance += $request->amount;
            } else {
                $account->balance -= $request->amount;
            }

            $account->save();

            DB::commit();

            return response()->json([
                'message' => 'Transaction logged successfully',
                'transaction' => $transaction
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Transaction failed', 'error' => $e->getMessage()], 500);
        }
    }
    public function transfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_account' => 'required|exists:accounts,account_number',
            'to_account'   => 'required|exists:accounts,account_number|different:from_account',
            'amount'       => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {
                    if ($value <= 0) {
                        $fail('The transfer amount must be a positive number greater than zero.');
                    }
                }
            ],
            'description'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        $fromAccount = Account::where('account_number', $request->from_account)
            ->where('user_id', $user->id)
            ->first();

        $toAccount = Account::where('account_number', $request->to_account)->first();

        if (!$fromAccount) {
            return response()->json(['message' => 'Unauthorized access to source account'], 403);
        }

        if (!$toAccount) {
            return response()->json(['message' => 'Recipient account not found'], 404);
        }

        if ($fromAccount->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient balance'], 400);
        }

        DB::beginTransaction();
        try {
            Transaction::create([
                'account_id'  => $fromAccount->id,
                'type'        => 'Debit',
                'amount'      => $request->amount,
                'description' => 'Transfer to ' . $toAccount->account_number,
            ]);
            $fromAccount->balance -= $request->amount;
            $fromAccount->save();

            Transaction::create([
                'account_id'  => $toAccount->id,
                'type'        => 'Credit',
                'amount'      => $request->amount,
                'description' => 'Transfer from ' . $fromAccount->account_number,
            ]);
            $toAccount->balance += $request->amount;
            $toAccount->save();

            DB::commit();

            return response()->json(['message' => 'Transfer successful'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Transfer failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function downloadStatement(Request $request)
    {
        $request->validate([
            'account_number' => 'required|exists:accounts,account_number',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $user = auth()->user();

        $account = Account::where('account_number', $request->account_number)
            ->where('user_id', $user->id)
            ->first();

        if (!$account) {
            return response()->json(['message' => 'Unauthorized access to this account'], 403);
        }

        $transactions = Transaction::where('account_id', $account->id)
            ->when($request->from, function ($query) use ($request) {
                return $query->whereDate('created_at', '>=', $request->from);
            })
            ->when($request->to, function ($query) use ($request) {
                return $query->whereDate('created_at', '<=', $request->to);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('statement.index', [
            'user' => $user,
            'account' => $account,
            'transactions' => $transactions,
            'from' => $request->from,
            'to' => $request->to
        ]);

        return $pdf->download('Account_Statement.pdf');
    }
}
