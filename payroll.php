<!-- Payroll Summary Table -->
<div class="summary-section">
    <h3 class="summary-title">Payroll Summary</h3>
    <div class="table-responsive">
        <table class="summary-table">
            <thead>
                <tr>
                    <th class="text-left">ID</th>
                    <th class="text-left">Name</th>
                    <th class="text-left">TIN</th>
                    <th class="text-left">SSS</th>
                    <th class="text-left">PhilHealth</th>
                    <th class="text-left">Pag-IBIG</th>
                    <th class="text-right">Basic Salary</th>
                    <th class="text-right">Allowances</th>
                    <th class="text-right">Deductions</th>
                    <th class="text-right">Net Pay</th>
                    <th class="text-center">Pay Period</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Sample data - replace with actual database query
                $payroll_data = [
                    [
                        'id' => 'EMP001',
                        'name' => 'John Doe',
                        'tin' => '123-456-789-000',
                        'sss' => '01-2345678-9',
                        'philhealth' => '01-234567890-1',
                        'pagibig' => '1234-5678-9012',
                        'basic_salary' => 25000,
                        'allowances' => 5000,
                        'deductions' => 2500,
                        'net_pay' => 27500,
                        'pay_period' => 'Nov 1-15, 2023',
                        'status' => 'Paid'
                    ],
                    [
                        'id' => 'EMP002',
                        'name' => 'Jane Smith',
                        'tin' => '987-654-321-000',
                        'sss' => '02-3456789-0',
                        'philhealth' => '02-345678901-2',
                        'pagibig' => '2345-6789-0123',
                        'basic_salary' => 20000,
                        'allowances' => 3000,
                        'deductions' => 1800,
                        'net_pay' => 21200,
                        'pay_period' => 'Nov 1-15, 2023',
                        'status' => 'Paid'
                    ],
                    [
                        'id' => 'EMP003',
                        'name' => 'Robert Johnson',
                        'tin' => '456-789-123-000',
                        'sss' => '03-4567890-1',
                        'philhealth' => '03-456789012-3',
                        'pagibig' => '3456-7890-1234',
                        'basic_salary' => 18000,
                        'allowances' => 2500,
                        'deductions' => 1500,
                        'net_pay' => 19000,
                        'pay_period' => 'Nov 1-15, 2023',
                        'status' => 'Pending'
                    ]
                ];
                
                // Format currency function
                function format_currency($amount) {
                    return '₱' . number_format($amount, 2, '.', ',');
                }
                
                foreach ($payroll_data as $employee): ?>
                <tr>
                    <td class="text-left"><?php echo htmlspecialchars($employee['id']); ?></td>
                    <td class="text-left"><?php echo htmlspecialchars($employee['name']); ?></td>
                    <td class="text-left"><?php echo htmlspecialchars($employee['tin']); ?></td>
                    <td class="text-left"><?php echo htmlspecialchars($employee['sss']); ?></td>
                    <td class="text-left"><?php echo htmlspecialchars($employee['philhealth']); ?></td>
                    <td class="text-left"><?php echo htmlspecialchars($employee['pagibig']); ?></td>
                    <td class="text-right"><?php echo format_currency($employee['basic_salary']); ?></td>
                    <td class="text-right"><?php echo format_currency($employee['allowances']); ?></td>
                    <td class="text-right"><?php echo format_currency($employee['deductions']); ?></td>
                    <td class="text-right highlight"><?php echo format_currency($employee['net_pay']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($employee['pay_period']); ?></td>
                    <td class="text-center">
                        <span class="status-badge status-<?php echo strtolower($employee['status']); ?>">
                            <?php echo htmlspecialchars($employee['status']); ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <!-- Total Row -->
                <tr class="total-row">
                    <td colspan="6" class="text-right"><strong>Total:</strong></td>
                    <td class="text-right"><strong><?php echo format_currency(array_sum(array_column($payroll_data, 'basic_salary'))); ?></strong></td>
                    <td class="text-right"><strong><?php echo format_currency(array_sum(array_column($payroll_data, 'allowances'))); ?></strong></td>
                    <td class="text-right"><strong><?php echo format_currency(array_sum(array_column($payroll_data, 'deductions'))); ?></strong></td>
                    <td class="text-right highlight"><strong><?php echo format_currency(array_sum(array_column($payroll_data, 'net_pay'))); ?></strong></td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
    /* Payroll Summary Table Styles */
    .summary-section {
        margin-top: 30px;
        background: #ffffff;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .summary-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eaeaea;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .summary-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
        min-width: 1100px;
    }

    .summary-table th {
        background: #3498db;
        color: white;
        padding: 12px 15px;
        font-weight: 500;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }

    .summary-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .summary-table tr:last-child td {
        border-bottom: none;
    }

    .summary-table tr:hover {
        background-color: #f8f9fa;
    }

    .summary-table .total-row {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .summary-table .total-row:hover {
        background-color: #f1f1f1;
    }

    /* Text alignment classes */
    .text-left { text-align: left; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }

    /* Numeric formatting */
    .numeric {
        font-family: 'Roboto Mono', monospace, sans-serif;
    }

    /* Highlight important numbers */
    .highlight {
        color: #2c3e50;
        font-weight: 500;
    }

    /* Status badges */
    .status-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 500;
        text-transform: uppercase;
    }

    .status-paid {
        background: rgba(46, 204, 113, 0.15);
        color: #2ecc71;
    }

    .status-pending {
        background: rgba(241, 196, 15, 0.15);
        color: #f1c40f;
    }

    .status-unpaid {
        background: rgba(231, 76, 60, 0.15);
        color: #e74c3c;
    }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .summary-table {
            min-width: 1000px;
        }
        
        .summary-table th,
        .summary-table td {
            padding: 10px 12px;
            font-size: 0.85rem;
        }
    }

    @media (max-width: 768px) {
        .summary-section {
            padding: 15px;
            border-radius: 0;
        }
        
        .summary-table th,
        .summary-table td {
            padding: 10px 8px;
            font-size: 0.8rem;
        }
    }
</style>
