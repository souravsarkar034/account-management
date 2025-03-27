<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    public function createAccount(Request $request)
    {

        $request->validate([
            'account_name' => 'required|string|unique:accounts,account_name',
            'account_type' => 'required|in:Personal,Business',
            'currency' => 'required|in:USD,EUR,GBP',
            'balance' => 'nullable|numeric|min:0',
        ]);

        $account = Account::create([
            'user_id' => Auth::id(),
            'account_name' => $request->account_name,
            'account_number' => $this->generateLuhnNumber(),
            'account_type' => $request->account_type,
            'currency' => $request->currency,
            'balance' => $request->balance ?? 0
        ]);

        return response()->json(['account' => $account], 201);
    }

    private function generateLuhnNumber()
    {
        do {
            $number = mt_rand(100000000000, 999999999999);
        } while (!$this->isValidLuhn($number));

        return (string) $number;
    }

    private function isValidLuhn($number)
    {
        $digits = str_split($number);
        $sum = 0;
        $alt = false;

        for ($i = count($digits) - 1; $i >= 0; $i--) {
            $n = (int) $digits[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }

        return ($sum % 10) === 0;
    }
    public function showAccountDetails($account_number)
    {
        $account = Account::where('account_number', $account_number)
            ->where('user_id', auth()->id())
            ->first();

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        return response()->json($account);
    }
    public function updateAccountDetails(Request $request, $account_number)
    {

        $request->validate([
            'account_name' => 'sometimes|string|max:255',
            'account_type' => 'sometimes|string|in:Personal,Business',
            'balance' => 'sometimes|numeric|min:0',
        ]);


        $account = Account::where('account_number', $account_number)
            ->where('user_id', auth()->id())
            ->first();
        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }


        $account->update($request->only(['account_name', 'account_type', 'balance']));

        return response()->json(['message' => 'Account updated successfully', 'account' => $account]);
    }
    public function deactivateAccount($account_number)
    {

        $account = Account::where('account_number', $account_number)
            ->where('user_id', auth()->id())
            ->first();

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }


        $account->delete();

        return response()->json(['message' => 'Account deactivated successfully']);
    }
}
