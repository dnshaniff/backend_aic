<p>Dear {{ $manager->full_name }},</p>

<p>A new reimbursement has been submitted by {{ $reimbursement->employee->full_name }}.</p>

<p><strong>Title:</strong> {{ $reimbursement->title }}</p>
<p><strong>Amount:</strong> Rp {{ number_format($reimbursement->amount, 0, ',', '.') }}</p>
<p><strong>Category:</strong> {{ $reimbursement->category->category_name }}</p>

<p>Please review it in the system.</p>
