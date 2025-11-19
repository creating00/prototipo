<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: 'Helvetica', 'Arial', sans-serif;
        color: #333;
        line-height: 1.6;
        font-size: 14px;
    }
    
    .invoice-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 40px;
    }
    
    /* Header */
    .invoice-header {
        display: table;
        width: 100%;
        margin-bottom: 40px;
        border-bottom: 3px solid #2563eb;
        padding-bottom: 20px;
    }
    
    .header-left, .header-right {
        display: table-cell;
        vertical-align: top;
        width: 50%;
    }
    
    .company-name {
        font-size: 28px;
        font-weight: bold;
        color: #2563eb;
        margin-bottom: 8px;
    }
    
    .company-details {
        font-size: 12px;
        color: #666;
        line-height: 1.8;
    }
    
    .invoice-title {
        font-size: 32px;
        font-weight: bold;
        color: #1e293b;
        text-align: right;
        margin-bottom: 8px;
    }
    
    .invoice-meta {
        text-align: right;
        font-size: 12px;
        color: #666;
    }
    
    .invoice-meta div {
        margin-bottom: 4px;
    }
    
    .invoice-meta strong {
        color: #333;
        font-weight: 600;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        margin-top: 8px;
    }
    
    .status-paid {
        background-color: #dcfce7;
        color: #166534;
    }
    
    .status-pending {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    /* Client Info */
    .client-section {
        margin-bottom: 30px;
        background-color: #f8fafc;
        padding: 20px;
        border-radius: 8px;
        border-left: 4px solid #2563eb;
    }
    
    .section-title {
        font-size: 13px;
        font-weight: 600;
        color: #2563eb;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .client-name {
        font-size: 16px;
        font-weight: bold;
        color: #1e293b;
        margin-bottom: 6px;
    }
    
    .client-details {
        font-size: 12px;
        color: #64748b;
        line-height: 1.8;
    }
    
    /* Items Table */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 30px;
    }
    
    .items-table thead {
        background-color: #2563eb;
        color: white;
    }
    
    .items-table th {
        padding: 12px 10px;
        text-align: left;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .items-table th:last-child,
    .items-table td:last-child {
        text-align: right;
    }
    
    .items-table tbody tr {
        border-bottom: 1px solid #e2e8f0;
    }
    
    .items-table tbody tr:last-child {
        border-bottom: 2px solid #cbd5e1;
    }
    
    .items-table td {
        padding: 14px 10px;
        font-size: 13px;
    }
    
    .item-description {
        color: #1e293b;
        font-weight: 500;
    }
    
    .item-quantity {
        color: #64748b;
    }
    
    /* Totals */
    .totals-section {
        display: table;
        width: 100%;
        margin-bottom: 40px;
    }
    
    .totals-left {
        display: table-cell;
        width: 60%;
        vertical-align: top;
    }
    
    .totals-right {
        display: table-cell;
        width: 40%;
        vertical-align: top;
    }
    
    .totals-table {
        width: 100%;
    }
    
    .totals-table tr {
        border-bottom: 1px solid #e2e8f0;
    }
    
    .totals-table td {
        padding: 10px 0;
        font-size: 14px;
    }
    
    .totals-table td:first-child {
        color: #64748b;
        font-weight: 500;
    }
    
    .totals-table td:last-child {
        text-align: right;
        color: #1e293b;
        font-weight: 600;
    }
    
    .total-row {
        background-color: #2563eb;
        color: white !important;
    }
    
    .total-row td {
        padding: 14px 10px;
        font-size: 18px;
        font-weight: bold;
        border: none !important;
    }
    
    .total-row td:first-child,
    .total-row td:last-child {
        color: white !important;
    }
    
    .notes-box {
        background-color: #fef9e7;
        border-left: 4px solid #f59e0b;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
    
    .notes-title {
        font-size: 12px;
        font-weight: 600;
        color: #92400e;
        margin-bottom: 6px;
        text-transform: uppercase;
    }
    
    .notes-text {
        font-size: 12px;
        color: #78350f;
        line-height: 1.6;
    }
    
    /* Footer */
    .invoice-footer {
        border-top: 2px solid #e2e8f0;
        padding-top: 20px;
        text-align: center;
        font-size: 11px;
        color: #94a3b8;
    }
    
    .footer-text {
        margin-bottom: 8px;
    }
</style>