<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demo checkout — DeQode</title>
    <style>
        :root { color-scheme: light; font-family: ui-sans-serif, system-ui, sans-serif; }
        body { margin: 0; background: #f8fafc; color: #0f172a; }
        main { max-width: 28rem; margin: 0 auto; min-height: 100vh; display: flex; flex-direction: column; justify-content: center; padding: 2rem 1rem; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1.5rem; box-shadow: 0 1px 2px rgb(15 23 42 / 6%); }
        .banner { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; border-radius: 1rem; padding: 1rem; font-size: .875rem; margin-bottom: 1.25rem; }
        h1 { margin: .25rem 0 0; font-size: 1.25rem; }
        .muted { color: #64748b; font-size: .875rem; }
        dl { margin: 1.5rem 0 0; }
        .row { display: flex; justify-content: space-between; gap: 1rem; padding: .5rem 0; font-size: .875rem; }
        .row.total { border-top: 1px solid #e2e8f0; margin-top: .5rem; padding-top: .75rem; font-size: 1rem; font-weight: 600; }
        .actions { display: grid; gap: .75rem; margin-top: 1.75rem; }
        button { width: 100%; border: 0; border-radius: .75rem; padding: .85rem 1rem; font-weight: 600; cursor: pointer; }
        .success { background: #059669; color: #fff; }
        .fail { background: #dc2626; color: #fff; }
        .cancel { background: #e2e8f0; color: #0f172a; }
        .back { text-align: center; margin-top: 1rem; font-size: .75rem; }
        a { color: #475569; }
        code { font-family: ui-monospace, monospace; font-size: .75rem; }
    </style>
</head>
<body>
<main>
    <div class="banner">
        <strong>Demo gateway</strong> — local/testing only. Choose a payment outcome.
    </div>

    <div class="card">
        <p class="muted">Workspace</p>
        <h1>{{ $session->tenant->name }}</h1>

        <dl>
            <div class="row">
                <dt class="muted">Plan</dt>
                <dd>{{ $session->package->name }}</dd>
            </div>
            <div class="row">
                <dt class="muted">Cycle</dt>
                <dd>Monthly</dd>
            </div>
            <div class="row total">
                <dt>Amount</dt>
                <dd>{{ number_format($session->amount_cents / 100, 2) }} {{ $session->currency }}</dd>
            </div>
        </dl>

        <div class="actions">
            <form method="POST" action="{{ route('billing.demo.complete') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $session->token }}">
                <input type="hidden" name="result" value="success">
                <button class="success" type="submit">Success</button>
            </form>

            <form method="POST" action="{{ route('billing.demo.complete') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $session->token }}">
                <input type="hidden" name="result" value="fail">
                <button class="fail" type="submit">Fail</button>
            </form>

            <form method="POST" action="{{ route('billing.demo.complete') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $session->token }}">
                <input type="hidden" name="result" value="cancel">
                <button class="cancel" type="submit">Cancel</button>
            </form>
        </div>

        <p class="muted" style="margin-top:1.5rem;text-align:center">
            Reference: <code>{{ $session->token }}</code>
        </p>
    </div>

    <p class="back">
        <a href="{{ $billingUrl }}">Back to Billing</a>
    </p>
</main>
</body>
</html>
