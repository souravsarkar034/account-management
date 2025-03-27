<!DOCTYPE html>
<html>
<head>
    <title>Account Statement</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; font-size: 20px; font-weight: bold; }
        .details { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">Account Statement</div>
    <div class="details">
        <p><strong>Account Holder:</strong> {{ $user->name }}</p>
        <p><strong>Account Number:</strong> {{ $account->account_number }}</p>
        <p><strong>Statement Period:</strong> {{ $from ?? 'Start' }} to {{ $to ?? 'Today' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr>
                <td>{{ $transaction->created_at->format('Y-m-d') }}</td>
                <td>{{ $transaction->type }}</td>
                <td>${{ number_format($transaction->amount, 2) }}</td>
                <td>{{ $transaction->description }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p><strong>Current Balance:</strong> ${{ number_format($account->balance, 2) }}</p>
</body>
</html>
