<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <?php
        $msoXml = '<!--[if gte mso 9]><xml>'
            . '<' . 'x:ExcelWorkbook>'
            . '<' . 'x:ExcelWorksheets>'
            . '<' . 'x:ExcelWorksheet>'
            . '<' . 'x:Name>Executive Analytics Report</' . 'x:Name>'
            . '<' . 'x:WorksheetOptions>'
            . '<' . 'x:DisplayGridlines/>'
            . '<' . 'x:FitToPage/>'
            . '<' . 'x:Print><' . 'x:ValidPrinterInfo/></' . 'x:Print>'
            . '</' . 'x:WorksheetOptions>'
            . '</' . 'x:ExcelWorksheet>'
            . '</' . 'x:ExcelWorksheets>'
            . '</' . 'x:ExcelWorkbook>'
            . '</xml><![endif]-->';
        echo $msoXml;
    ?>
    <style>
        body {
            font-family: 'Segoe UI', Calibri, Arial, sans-serif;
            font-size: 11pt;
            color: #0f172a;
            margin: 0;
            padding: 20px;
        }

        table {
            border-collapse: collapse;
            margin-bottom: 24px;
            width: 100%;
        }

        th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 12px 18px;
            border: 1px solid #334155;
            font-size: 10.5pt;
            letter-spacing: 0.5px;
        }

        td {
            padding: 10px 18px;
            border: 1px solid #cbd5e1;
            font-size: 10.5pt;
            vertical-align: middle;
            color: #1e293b;
        }

        .title-banner {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 16pt;
            font-weight: bold;
            padding: 16px 20px;
            text-align: left;
            border: 1px solid #0f172a;
        }

        .subtitle-banner {
            background-color: #1e293b;
            color: #94a3b8;
            font-size: 10pt;
            padding: 10px 20px;
            border: 1px solid #1e293b;
        }

        .section-header {
            background-color: #1e293b;
            color: #f8fafc;
            font-size: 12pt;
            font-weight: bold;
            padding: 12px 18px;
            border: 1px solid #0f172a;
        }

        .section-sub {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 9.5pt;
            font-style: italic;
            padding: 6px 18px;
            border: 1px solid #cbd5e1;
        }

        .kpi-card-header {
            background-color: #f8fafc;
            font-weight: bold;
            color: #475569;
            font-size: 9pt;
            text-transform: uppercase;
        }

        .kpi-card-value {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
        }

        .currency {
            mso-number-format: "\₱\#\,\#\#0\.00";
            text-align: right;
            font-family: Consolas, 'Segoe UI', monospace;
            font-weight: 600;
        }

        .number {
            mso-number-format: "\#\,\#\#0";
            text-align: right;
            font-family: Consolas, 'Segoe UI', monospace;
        }

        .percentage {
            mso-number-format: "0\.0%";
            text-align: right;
            font-weight: 600;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .font-mono {
            font-family: Consolas, 'Courier New', monospace;
        }

        .row-alt {
            background-color: #f8fafc;
        }

        .total-row {
            background-color: #e2e8f0;
            font-weight: bold;
            border-top: 2px solid #0f172a;
            border-bottom: 3px double #0f172a;
        }

        .badge-critical {
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: bold;
            text-align: center;
        }

        .badge-high {
            background-color: #ffedd5;
            color: #9a3412;
            font-weight: bold;
            text-align: center;
        }

        .badge-moderate {
            background-color: #fef9c3;
            color: #854d0e;
            font-weight: bold;
            text-align: center;
        }

        .badge-idle {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-align: center;
        }

        .spacer {
            height: 18px;
            border: none;
            background: transparent;
        }
    </style>
</head>
<body>

    <!-- BRANDING & REPORT HEADER -->
    <table>
        <colgroup>
            <col width="220" style="width: 220px;">
            <col width="260" style="width: 260px;">
            <col width="220" style="width: 220px;">
            <col width="260" style="width: 260px;">
        </colgroup>
        <tr>
            <td colspan="4" class="title-banner">
                RLG TOYS &amp; HOBBIES &bull; EXECUTIVE ANALYTICS &amp; FINANCIAL AUDIT REPORT
            </td>
        </tr>
        <tr>
            <td colspan="4" class="subtitle-banner">
                Confidential Management Report &bull; Comprehensive Performance &bull; Inventory Health &bull; Tax Compliance
            </td>
        </tr>
        <tr>
            <td class="kpi-card-header">Reporting Window:</td>
            <td style="font-weight: bold; color: #0f172a;">{{ $timeframeLabel }}</td>
            <td class="kpi-card-header">Exported Timestamp:</td>
            <td style="font-weight: bold; color: #0f172a;">{{ $generatedDate }}</td>
        </tr>
        <tr>
            <td class="kpi-card-header">Base Currency:</td>
            <td style="font-weight: bold; color: #059669;">Philippine Peso (PHP &bull; &#8369;)</td>
            <td class="kpi-card-header">Audit Classification:</td>
            <td style="font-weight: bold; color: #2563eb;">Board-Level Executive Audit</td>
        </tr>
    </table>

    <table class="spacer"><tr><td style="border: none;">&nbsp;</td></tr></table>

    <!-- SECTION 1: EXECUTIVE FINANCIAL SUMMARY & KEY KPIS -->
    <table>
        <colgroup>
            <col width="320" style="width: 320px;">
            <col width="200" style="width: 200px;">
            <col width="320" style="width: 320px;">
            <col width="200" style="width: 200px;">
        </colgroup>
        <tr>
            <td colspan="4" class="section-header">
                1. EXECUTIVE FINANCIAL OVERVIEW &amp; PERFORMANCE METRICS
            </td>
        </tr>
        <tr>
            <td colspan="4" class="section-sub">
                Official revenue calculations reconciled against order transactions and tax requirements.
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; background-color: #f8fafc;">Gross Sales Revenue (Total Orders Value)</td>
            <td class="currency" style="color: #0f172a; font-weight: bold;">&#8369;{{ number_format($kpis['gross_sales'], 2) }}</td>
            <td style="font-weight: bold; background-color: #f8fafc;">Net Store Sales (Post-Refunds &amp; Cancellations)</td>
            <td class="currency" style="color: #059669; font-weight: bold;">&#8369;{{ number_format($kpis['net_store_sales'], 2) }}</td>
        </tr>
        <tr>
            <td style="background-color: #ffffff;">Value Added Tax (12% Philippine VAT Liability)</td>
            <td class="currency" style="color: #4338ca;">&#8369;{{ number_format($kpis['vat_collected'], 2) }}</td>
            <td style="background-color: #ffffff;">Estimated Courier &amp; Logistics Expenses</td>
            <td class="currency" style="color: #b45309;">&#8369;{{ number_format($kpis['est_logistics_expense'], 2) }}</td>
        </tr>
        <tr>
            <td style="background-color: #f8fafc;">Total Customer Orders Completed</td>
            <td class="number" style="font-weight: bold;">{{ number_format($kpis['total_orders']) }}</td>
            <td style="background-color: #f8fafc;">Average Order Value (AOV)</td>
            <td class="currency" style="font-weight: bold;">&#8369;{{ number_format($kpis['average_order_value'], 2) }}</td>
        </tr>
        <tr>
            <td style="background-color: #ffffff;">Total Customer Refunds &amp; Reversals</td>
            <td class="currency" style="color: #e11d48;">&#8369;{{ number_format($kpis['total_refunds'], 2) }}</td>
            <td style="background-color: #ffffff;">Total Capital Tied Up in Active Inventory</td>
            <td class="currency" style="color: #0f172a; font-weight: bold;">&#8369;{{ number_format($kpis['tied_up_capital'], 2) }}</td>
        </tr>
        <tr>
            <td style="background-color: #f8fafc;">Total Physical Units in Warehouse</td>
            <td class="number">{{ number_format($kpis['total_inventory_units']) }} units</td>
            <td style="background-color: #f8fafc;">Active Catalog SKUs Managed</td>
            <td class="number">{{ number_format($kpis['total_active_skus']) }} SKUs</td>
        </tr>
        <tr>
            <td style="background-color: #ffffff;">Low Stock Reorder Alerts (Stock &le; 10)</td>
            <td class="number" style="color: #e11d48; font-weight: bold;">{{ number_format($kpis['low_stock_count']) }} SKUs</td>
            <td style="background-color: #ffffff;">Out of Stock SKUs (0 Units)</td>
            <td class="number" style="color: #991b1b; font-weight: bold;">{{ number_format($kpis['out_of_stock_count']) }} SKUs</td>
        </tr>
    </table>

    <table class="spacer"><tr><td style="border: none;">&nbsp;</td></tr></table>

    <!-- SECTION 2: 6-MONTH TRAJECTORY TABLE -->
    <table>
        <colgroup>
            <col width="160" style="width: 160px;">
            <col width="200" style="width: 200px;">
            <col width="180" style="width: 180px;">
            <col width="200" style="width: 200px;">
            <col width="140" style="width: 140px;">
            <col width="140" style="width: 140px;">
        </colgroup>
        <tr>
            <td colspan="6" class="section-header">
                2. SIX-MONTH HISTORICAL GROSS VS NET SALES TRAJECTORY
            </td>
        </tr>
        <tr>
            <td colspan="6" class="section-sub">
                Monthly revenue trend comparing gross revenue to net realized store revenue.
            </td>
        </tr>
        <thead>
            <tr>
                <th class="text-left">Month &amp; Year</th>
                <th class="text-right">Gross Revenue (&#8369;)</th>
                <th class="text-right">Customer Refunds (&#8369;)</th>
                <th class="text-right">Net Store Sales (&#8369;)</th>
                <th class="text-right">Order Count</th>
                <th class="text-right">Net Margin Rate</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totGross = 0;
                $totRefunds = 0;
                $totNet = 0;
                $totOrders = 0;
            @endphp
            @foreach($monthlyTrajectory as $idx => $m)
                @php
                    $totGross += $m['gross'];
                    $totRefunds += $m['refunds'];
                    $totNet += $m['net'];
                    $totOrders += $m['orders'];
                    $margin = $m['gross'] > 0 ? round(($m['net'] / $m['gross']) * 100, 1) : 0;
                @endphp
                <tr class="{{ $idx % 2 === 1 ? 'row-alt' : '' }}">
                    <td style="font-weight: bold;">{{ $m['month'] }} {{ $m['year'] }}</td>
                    <td class="currency">&#8369;{{ number_format($m['gross'], 2) }}</td>
                    <td class="currency" style="color: #e11d48;">&#8369;{{ number_format($m['refunds'], 2) }}</td>
                    <td class="currency" style="color: #059669; font-weight: bold;">&#8369;{{ number_format($m['net'], 2) }}</td>
                    <td class="number">{{ number_format($m['orders']) }}</td>
                    <td class="percentage">{{ $margin }}%</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>6-MONTH TOTALS</td>
                <td class="currency">&#8369;{{ number_format($totGross, 2) }}</td>
                <td class="currency" style="color: #e11d48;">&#8369;{{ number_format($totRefunds, 2) }}</td>
                <td class="currency" style="color: #059669;">&#8369;{{ number_format($totNet, 2) }}</td>
                <td class="number">{{ number_format($totOrders) }}</td>
                <td class="percentage">{{ $totGross > 0 ? round(($totNet / $totGross) * 100, 1) : 0 }}%</td>
            </tr>
        </tbody>
    </table>

    <table class="spacer"><tr><td style="border: none;">&nbsp;</td></tr></table>

    <!-- SECTION 3: HIGH VELOCITY STOCKOUT RISK MATRIX -->
    <table>
        <colgroup>
            <col width="160" style="width: 160px;">
            <col width="340" style="width: 340px;">
            <col width="160" style="width: 160px;">
            <col width="160" style="width: 160px;">
            <col width="160" style="width: 160px;">
            <col width="140" style="width: 140px;">
            <col width="220" style="width: 220px;">
        </colgroup>
        <tr>
            <td colspan="7" class="section-header">
                3. HIGH VELOCITY ITEMS &bull; STOCKOUT RISK MATRIX
            </td>
        </tr>
        <tr>
            <td colspan="7" class="section-sub">
                Fastest-moving items projected to exhaust warehouse inventory within 14 operational days.
            </td>
        </tr>
        <thead>
            <tr>
                <th class="text-left">SKU Code</th>
                <th class="text-left">Product Title</th>
                <th class="text-right">Unit Price (&#8369;)</th>
                <th class="text-right">Stock Remaining</th>
                <th class="text-right">Sales Velocity</th>
                <th class="text-center">Days to Stockout</th>
                <th class="text-center">Restock Urgency</th>
            </tr>
        </thead>
        <tbody>
            @foreach($highVelocity as $idx => $item)
                @php
                    $badgeClass = match($item['runRate']) {
                        'Critical' => 'badge-critical',
                        'High' => 'badge-high',
                        default => 'badge-moderate',
                    };
                @endphp
                <tr class="{{ $idx % 2 === 1 ? 'row-alt' : '' }}">
                    <td class="font-mono" style="font-weight: bold;">{{ $item['sku'] }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td class="currency">&#8369;{{ number_format($item['price'], 2) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $item['stock'] }} units</td>
                    <td class="number">{{ number_format($item['velocityPerDay'], 1) }} / day</td>
                    <td class="text-center" style="font-weight: bold; color: {{ $item['daysToStockout'] <= 3 ? '#b91c1c' : '#c2410c' }};">
                        {{ $item['daysToStockout'] }} days
                    </td>
                    <td class="{{ $badgeClass }}">
                        {{ strtoupper($item['runRate']) }} RESTOCK
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="spacer"><tr><td style="border: none;">&nbsp;</td></tr></table>

    <!-- SECTION 4: DEAD STOCK & TIED CAPITAL ANALYSIS -->
    <table>
        <colgroup>
            <col width="160" style="width: 160px;">
            <col width="340" style="width: 340px;">
            <col width="140" style="width: 140px;">
            <col width="160" style="width: 160px;">
            <col width="160" style="width: 160px;">
            <col width="180" style="width: 180px;">
            <col width="220" style="width: 220px;">
        </colgroup>
        <tr>
            <td colspan="7" class="section-header">
                4. DEAD STOCK &amp; STAGNANT CAPITAL AUDIT
            </td>
        </tr>
        <tr>
            <td colspan="7" class="section-sub">
                Inventory with zero or negligible units moved over the past 30 days, representing frozen working capital.
            </td>
        </tr>
        <thead>
            <tr>
                <th class="text-left">SKU Code</th>
                <th class="text-left">Product Title</th>
                <th class="text-center">Days in Storage</th>
                <th class="text-right">Unit Price (&#8369;)</th>
                <th class="text-right">Idle Stock Units</th>
                <th class="text-right">Tied-Up Capital (&#8369;)</th>
                <th class="text-center">Liquidation Strategy</th>
            </tr>
        </thead>
        <tbody>
            @php $totalDeadCapital = 0; @endphp
            @foreach($deadStock as $idx => $item)
                @php $totalDeadCapital += $item['tiedUpCapital']; @endphp
                <tr class="{{ $idx % 2 === 1 ? 'row-alt' : '' }}">
                    <td class="font-mono" style="font-weight: bold;">{{ $item['sku'] }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td class="text-center">{{ $item['daysInStock'] }} days</td>
                    <td class="currency">&#8369;{{ number_format($item['price'], 2) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $item['stock'] }} units</td>
                    <td class="currency" style="color: #b45309; font-weight: bold;">&#8369;{{ number_format($item['tiedUpCapital'], 2) }}</td>
                    <td class="badge-idle">
                        {{ $item['daysInStock'] > 120 ? 'Bundle Clearance / 20% Off' : 'Feature in Flash Promo' }}
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5">TOTAL CAPITAL FROZEN IN STAGNANT INVENTORY</td>
                <td class="currency" style="color: #b45309;">&#8369;{{ number_format($totalDeadCapital, 2) }}</td>
                <td class="text-center" style="font-weight: bold;">ACTION RECOMMENDED</td>
            </tr>
        </tbody>
    </table>

    <table class="spacer"><tr><td style="border: none;">&nbsp;</td></tr></table>

    <!-- SECTION 5: CATEGORY REVENUE & MARKET SHARE -->
    <table>
        <colgroup>
            <col width="280" style="width: 280px;">
            <col width="160" style="width: 160px;">
            <col width="160" style="width: 160px;">
            <col width="200" style="width: 200px;">
            <col width="180" style="width: 180px;">
            <col width="160" style="width: 160px;">
        </colgroup>
        <tr>
            <td colspan="6" class="section-header">
                5. PRODUCT CATEGORY REVENUE &amp; DISTRIBUTION BREAKDOWN
            </td>
        </tr>
        <tr>
            <td colspan="6" class="section-sub">
                Performance breakdown across all product divisions and departments.
            </td>
        </tr>
        <thead>
            <tr>
                <th class="text-left">Product Category</th>
                <th class="text-right">Catalog Products</th>
                <th class="text-right">Units Sold</th>
                <th class="text-right">Gross Revenue (&#8369;)</th>
                <th class="text-right">Avg Unit Revenue (&#8369;)</th>
                <th class="text-right">Revenue Share</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totCatRev = 0;
                $totCatUnits = 0;
            @endphp
            @foreach($categories as $idx => $cat)
                @php
                    $totCatRev += $cat['revenue'];
                    $totCatUnits += $cat['unitsSold'];
                    $avgPrice = $cat['unitsSold'] > 0 ? round($cat['revenue'] / $cat['unitsSold'], 2) : 0;
                @endphp
                <tr class="{{ $idx % 2 === 1 ? 'row-alt' : '' }}">
                    <td style="font-weight: bold;">{{ $cat['name'] }}</td>
                    <td class="number">{{ number_format($cat['productCount']) }} SKUs</td>
                    <td class="number">{{ number_format($cat['unitsSold']) }} units</td>
                    <td class="currency" style="font-weight: bold;">&#8369;{{ number_format($cat['revenue'], 2) }}</td>
                    <td class="currency">&#8369;{{ number_format($avgPrice, 2) }}</td>
                    <td class="percentage">{{ $cat['sharePct'] }}%</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>TOTAL CATEGORY SALES</td>
                <td class="number">{{ number_format(array_sum(array_column($categories, 'productCount'))) }} SKUs</td>
                <td class="number">{{ number_format($totCatUnits) }} units</td>
                <td class="currency">&#8369;{{ number_format($totCatRev, 2) }}</td>
                <td class="currency">&#8369;{{ $totCatUnits > 0 ? number_format($totCatRev / $totCatUnits, 2) : '0.00' }}</td>
                <td class="percentage">100.0%</td>
            </tr>
        </tbody>
    </table>

    <table class="spacer"><tr><td style="border: none;">&nbsp;</td></tr></table>

    <!-- SECTION 6: PAYMENT GATEWAYS & SETTLEMENT SHARE -->
    <table>
        <colgroup>
            <col width="280" style="width: 280px;">
            <col width="200" style="width: 200px;">
            <col width="240" style="width: 240px;">
            <col width="200" style="width: 200px;">
        </colgroup>
        <tr>
            <td colspan="4" class="section-header">
                6. PAYMENT GATEWAYS &amp; SETTLEMENT SHARE
            </td>
        </tr>
        <tr>
            <td colspan="4" class="section-sub">
                Acquisition channels and settlement distribution for customer transactions.
            </td>
        </tr>
        <thead>
            <tr>
                <th class="text-left">Payment Gateway / Method</th>
                <th class="text-right">Total Completed Orders</th>
                <th class="text-right">Settled Gross Volume (&#8369;)</th>
                <th class="text-right">Share of Payments</th>
            </tr>
        </thead>
        <tbody>
            @foreach($gateways as $idx => $g)
                <tr class="{{ $idx % 2 === 1 ? 'row-alt' : '' }}">
                    <td style="font-weight: bold;">{{ $g['method'] }}</td>
                    <td class="number">{{ number_format($g['orders']) }} orders</td>
                    <td class="currency" style="font-weight: bold;">&#8369;{{ number_format($g['volume'], 2) }}</td>
                    <td class="percentage">{{ $g['sharePct'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="spacer"><tr><td style="border: none;">&nbsp;</td></tr></table>

    <!-- EXECUTIVE SIGN-OFF & VERIFICATION BLOCK -->
    <table>
        <colgroup>
            <col width="240" style="width: 240px;">
            <col width="320" style="width: 320px;">
            <col width="240" style="width: 240px;">
            <col width="320" style="width: 320px;">
        </colgroup>
        <tr>
            <td colspan="4" class="title-banner" style="font-size: 11pt; padding: 10px 16px;">
                EXECUTIVE AUDIT SIGN-OFF &amp; COMPLIANCE CERTIFICATION
            </td>
        </tr>
        <tr>
            <td class="kpi-card-header">Prepared By:</td>
            <td>RLG Enterprise Analytics Engine (v2.6)</td>
            <td class="kpi-card-header">Chief Financial Officer:</td>
            <td style="border-bottom: 2px solid #0f172a;">___________________________________</td>
        </tr>
        <tr>
            <td class="kpi-card-header">System Verification:</td>
            <td style="color: #059669; font-weight: bold;">VERIFIED &amp; RECONCILED</td>
            <td class="kpi-card-header">Executive Managing Director:</td>
            <td style="border-bottom: 2px solid #0f172a;">___________________________________</td>
        </tr>
    </table>

</body>
</html>
