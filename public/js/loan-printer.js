function printLoanSchedule() {
    // 1. Safely grab form values using helper functions to prevent null errors
    const getValByName = (name) => {
        const el = document.querySelector(`[name="${name}"]`);
        return el ? el.value : 'N/A';
    };

    let typeEl = document.querySelector('[name="type"]');
    let loanType = 'N/A';
    if (typeEl) {
        loanType = typeEl.tagName === 'SELECT' ? typeEl.options[typeEl.selectedIndex].text : typeEl.value;
    }
    
    let dateApplied = document.getElementById('date_applied') ? document.getElementById('date_applied').value : 'N/A';
    let office = getValByName('office_name');
    let borrower = getValByName('borrower_name');
    let coMaker = getValByName('co_maker') || 'None';
    
    let principal = parseFloat(document.getElementById('amount') ? document.getElementById('amount').value : 0) || 0;
    let serviceFee = parseFloat(document.getElementById('service_fee') ? document.getElementById('service_fee').value : 0) || 0;
    let interestRate = document.getElementById('interest_rate_input') ? document.getElementById('interest_rate_input').value : 0;
    let interest = parseFloat(document.getElementById('interest') ? document.getElementById('interest').value : 0) || 0;
    let surcharge = parseFloat(document.getElementById('surcharge') ? document.getElementById('surcharge').value : 0) || 0;
    let netProceeds = parseFloat(document.getElementById('net_proceeds_actual') ? document.getElementById('net_proceeds_actual').value : 0) || 0;
    
    let start = document.getElementById('start_date') ? document.getElementById('start_date').value : 'N/A';
    let end = document.getElementById('end_date') ? document.getElementById('end_date').value : 'N/A';
    let months = document.getElementById('months') ? document.getElementById('months').value : '0';

    // Basic Validation before printing
    if (!borrower || borrower === 'N/A' || principal <= 0) {
        alert("Please enter the Applicant's Name and the Principal Amount before printing the schedule.");
        return;
    }

    // Formatter for currency
    const formatMoney = (amount) => '₱ ' + amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    let currentDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

    // 2. Open a new window for the print layout
    let printWindow = window.open('', '_blank', 'width=900,height=800');
    
    if (!printWindow) {
        alert("Please allow pop-ups in your browser to print the loan schedule.");
        return;
    }
    
    // 3. Construct the HTML using an array
    let htmlLines = [
        "<!DOCTYPE html>",
        "<html lang='en'>",
        "<head>",
        "    <meta charset='UTF-8'>",
        "    <title>Loan Schedule - " + borrower + "</title>",
        "    <style>",
        "        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');",
        "        body { font-family: 'Inter', sans-serif; padding: 40px; color: #2d3748; margin: 0 auto; max-width: 800px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }",
        "        .header { text-align: center; margin-bottom: 40px; border-bottom: 3px solid #007aff; padding-bottom: 20px; }",
        "        .header h1 { margin: 0 0 5px 0; color: #1a202c; text-transform: uppercase; letter-spacing: 1px; font-size: 24px; }",
        "        .header p { margin: 0; color: #718096; font-size: 14px; }",
        "        .grid-container { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }",
        "        .section-title { font-size: 13px; text-transform: uppercase; color: #a0aec0; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 20px; font-weight: 700; letter-spacing: 1px; }",
        "        .row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 15px; }",
        "        .label { color: #4a5568; font-weight: 500; }",
        "        .value { font-weight: 700; color: #1a202c; text-align: right; }",
        "        .finance-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; }",
        "        .finance-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 15px; }",
        "        .finance-row.deduction .value { color: #e53e3e; }",
        "        .divider { height: 1px; background: #e2e8f0; margin: 15px 0; }",
        "        .net-proceeds { display: flex; justify-content: space-between; align-items: center; background: rgba(52, 199, 89, 0.1); border: 2px solid #34c759; border-radius: 8px; padding: 15px; margin-top: 20px; }",
        "        .net-label { font-size: 15px; font-weight: 700; color: #276749; }",
        "        .net-value { font-size: 20px; font-weight: 800; color: #276749; }",
        "        .signatures { margin-top: 80px; display: flex; justify-content: space-between; }",
        "        .sig-block { text-align: center; width: 40%; }",
        "        .sig-line { border-bottom: 1px solid #1a202c; height: 40px; margin-bottom: 8px; }",
        "        .sig-name { font-weight: 700; font-size: 14px; text-transform: uppercase; color: #1a202c; }",
        "        .sig-title { font-size: 12px; color: #718096; }",
        "        @media print { body { padding: 0; } .finance-box { border: 1px solid #cbd5e1; } }",
        "    </style>",
        "</head>",
        "<body>",
        "    <div class='header'>",
        "        <h1>Loan Schedule Application</h1>",
        "        <p>Generated on " + currentDate + "</p>",
        "    </div>",
        "    <div class='grid-container'>",
        "        <div>",
        "            <div class='section-title'>Applicant Profile</div>",
        "            <div class='row'><span class='label'>Name of Applicant</span> <span class='value'>" + borrower + "</span></div>",
        "            <div class='row'><span class='label'>Co-Maker</span> <span class='value'>" + coMaker + "</span></div>",
        "            <div class='row'><span class='label'>Office</span> <span class='value'>" + office + "</span></div>",
        "            <div class='row'><span class='label'>Date Applied</span> <span class='value'>" + dateApplied + "</span></div>",
        "            <div class='row'><span class='label'>Loan Type</span> <span class='value'>" + loanType + "</span></div>",
        "            <div class='section-title' style='margin-top: 30px;'>Term Schedule</div>",
        "            <div class='row'><span class='label'>Duration</span> <span class='value'>" + months + " Months</span></div>",
        "            <div class='row'><span class='label'>Payment Start</span> <span class='value'>" + start + "</span></div>",
        "            <div class='row'><span class='label'>Payment End</span> <span class='value'>" + end + "</span></div>",
        "        </div>",
        "        <div>",
        "            <div class='section-title'>Financial Breakdown</div>",
        "            <div class='finance-box'>",
        "                <div class='finance-row'>",
        "                    <span class='label' style='font-weight: 700; color: #007aff;'>Principal Amount</span>",
        "                    <span class='value'>" + formatMoney(principal) + "</span>",
        "                </div>",
        "                <div class='divider'></div>",
        "                <div class='finance-row deduction'>",
        "                    <span class='label'>Service Fee (0.5%)</span>",
        "                    <span class='value'>- " + formatMoney(serviceFee) + "</span>",
        "                </div>",
        "                <div class='finance-row'>",
        "                    <span class='label'>Interest Rate</span>",
        "                    <span class='value' style='color: #4a5568;'>" + interestRate + "%</span>",
        "                </div>",
        "                <div class='finance-row'>",
        "                    <span class='label'>Interest Amount</span>",
        "                    <span class='value'>" + formatMoney(interest) + "</span>",
        "                </div>",
        "                <div class='finance-row deduction'>",
        "                    <span class='label'>Surcharge</span>",
        "                    <span class='value'>- " + formatMoney(surcharge) + "</span>",
        "                </div>",
        "                <div class='net-proceeds'>",
        "                    <span class='net-label'>Net Amount</span>",
        "                    <span class='net-value'>" + formatMoney(netProceeds) + "</span>",
        "                </div>",
        "            </div>",
        "        </div>",
        "    </div>",
        "    <div class='signatures'>",
        "        <div class='sig-block'>",
        "            <div class='sig-line'></div>",
        "            <div class='sig-name'>" + borrower + "</div>",
        "            <div class='sig-title'>Signature over Printed Name</div>",
        "        </div>",
        "        <div class='sig-block'>",
        "            <div class='sig-line'></div>",
        "            <div class='sig-name'>Authorized Officer</div>",
        "            <div class='sig-title'>Approved By</div>",
        "        </div>",
        "    </div>",
        "</body>",
        "</html>"
    ];

    printWindow.document.write(htmlLines.join(""));
    printWindow.document.close();
    printWindow.focus();
    
    // Slight delay ensures the CSS completely loads before the print dialog appears
    setTimeout(() => {
        printWindow.print();
    }, 250);
}