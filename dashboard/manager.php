<?php
// This PHP file serves the HTML content below.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Manager's View - Approvals</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');

        :root {
            --color-primary: #10b981; 
            --color-primary-dark: #059669;
            --color-pending: #f59e0b;
            --color-approved: #10b981;
            --color-rejected: #ef4444;
            --bg-light: #f9fafb;

            /* Custom Gradient Variables */
            --gradient-from: #7F00FF;
            --gradient-to: #00C6FF;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(110deg, #7F00FF, #00C6FF);
            min-height: 100vh;
            padding: 2.5rem 1rem;
        }
        
        .main-card {
            background-color: white;
            box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.1),
                        0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-radius: 1rem;
            border: 1px solid #e5e7eb;
        }

        .card-shadow { box-shadow: 0 8px 30px rgba(34, 24, 64, 0.12); } 

        .gradient-bg {
            background-image: linear-gradient(110deg, var(--gradient-from), var(--gradient-to));
        }

        #manager-photo {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        #manager-photo:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            display: inline-block;
            transition: background-color 0.2s, color 0.2s;
        }

        .status-pending { background-color: #fffbeb; color: var(--color-pending); border: 1px solid #fcd34d; }
        .status-approved { background-color: #ecfdf5; color: var(--color-approved); border: 1px solid #a7f3d0; }
        .status-rejected { background-color: #fef2f2; color: var(--color-rejected); border: 1px solid #fecaca; }

        .data-cell {
            padding: 1rem 0.75rem;
            text-align: left;
            word-break: break-word;
            border-bottom: 1px solid #f3f4f6;
        }

        .row-read-only {
            opacity: 0.7;
            background-color: #f9fafb;
            transition: all 0.3s ease;
            filter: grayscale(10%);
        }

        .action-btn {
            padding: 0.4rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1),
                        0 1px 2px rgba(0,0,0,0.06);
        }
        .action-btn:hover { opacity: 0.9; }
        .action-btn:active { transform: translateY(1px); }
    </style>
</head>
<body class="flex flex-col items-center">

    <header class="w-full max-w-6xl mb-10 p-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <img id="manager-photo" 
                      src="image.png" 
                      alt="Manager Photo" 
                      class="w-12 h-12 rounded-full border-2 border-emerald-500 shadow-sm" />

                <h1 id="manager-greeting" 
                    class="text-3xl font-extrabold text-white transition-opacity duration-500 opacity-0">
                    </h1>
            </div>

            <div class="text-sm text-white font-medium hidden sm:block">
                Expense Approval Dashboard
            </div>
        </div>
        <div class="w-full h-1 gradient-bg rounded-full mt-2"></div>
    </header>

    <main class="w-full max-w-6xl main-card card-shadow p-6 md:p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Expense Requests (Pending)</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Approval Subject</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Request Owner</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Category</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Request Status</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Amount (in Co. Currency)</th>
                        <th class="px-3 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="approvals-table-body" class="bg-white divide-y divide-gray-100">
                    </tbody>
            </table>

            <div id="empty-state" class="hidden text-center py-12 text-gray-400 italic font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                <p class="mt-2">No pending approvals found. Enjoy your break!</p>
            </div>
        </div>
    </main>

    <script>
        const managerName = "Rana Ji";
        const managerPhotoUrl = "image.png";

        let requests = [
            { id: 1, subject: 'Travel Booking for Q3', owner: 'Sarah', category: 'Travel', status: 'Pending', amount: '56.20', currency: 'GBP', companyAmount: '498.86', companyCurrency: 'USD' },
            { id: 2, subject: 'New Software License', owner: 'John Doe', category: 'Software', status: 'Pending', amount: '1200.00', currency: 'EUR', companyAmount: '1290.00', companyCurrency: 'USD' },
            { id: 3, subject: 'Client Dinner (Marketing)', owner: 'Alice Smith', category: 'Entertainment', status: 'Pending', amount: '250.00', companyAmount: '250.00', companyCurrency: 'USD' },
            { id: 4, subject: 'Office Supplies Restock', owner: 'Bob Johnson', category: 'Office', status: 'Pending', amount: '89.50', companyAmount: '65.00', companyCurrency: 'USD' },
            { id: 5, subject: 'Web Hosting Renewal', owner: 'Jane Smith', category: 'IT', status: 'Pending', amount: '49.99', companyAmount: '49.99', companyCurrency: 'USD' },
        ];

        const tableBody = document.getElementById('approvals-table-body');
        const emptyState = document.getElementById('empty-state');
        const managerGreeting = document.getElementById('manager-greeting');
        const managerPhoto = document.getElementById('manager-photo');

        const getStatusClasses = (status) => {
            switch (status) {
                case 'Approved': return 'status-approved';
                case 'Rejected': return 'status-rejected';
                default: return 'status-pending';
            }
        };

        const usdToInr = 88.76;

        const renderTable = () => {
            managerGreeting.textContent = `Hi, ${managerName}!`;
            managerPhoto.src = managerPhotoUrl;
            setTimeout(() => {
                managerGreeting.classList.remove('opacity-0');
                managerGreeting.classList.add('opacity-100');
            }, 100);

            const sortedRequests = [...requests].sort((a, b) => {
                if (a.status === 'Pending' && b.status !== 'Pending') return -1;
                if (a.status !== 'Pending' && b.status === 'Pending') return 1;
                return 0;
            });

            tableBody.innerHTML = '';

            if (requests.length === 0) {
                emptyState.classList.remove('hidden');
                return;
            } else {
                emptyState.classList.add('hidden');
            }

            sortedRequests.forEach(request => {
                const isReadOnly = request.status !== 'Pending';
                const row = document.createElement('tr');
                row.id = `request-row-${request.id}`;
                row.classList.add(isReadOnly ? 'row-read-only' : 'hover:bg-indigo-50', 'transition', 'duration-200');

                let amountInINR = (parseFloat(request.amount) * usdToInr).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                row.innerHTML = `
                    <td class="data-cell text-sm font-medium text-gray-900">${request.subject}</td>
                    <td class="data-cell text-sm text-gray-600">${request.owner}</td>
                    <td class="data-cell text-sm text-gray-600">${request.category}</td>
                    <td class="data-cell text-sm">
                        <span class="status-badge ${getStatusClasses(request.status)}" id="status-badge-${request.id}">
                            ${request.status}
                        </span>
                    </td>
                    <td class="data-cell text-sm text-gray-900 font-bold">
                        ${request.companyAmount} <span class="text-xs text-gray-500">${request.companyCurrency}</span>
                        <span class="text-xs text-red-500 block">(${amountInINR} ₹INR)</span>
                    </td>
                    <td class="data-cell text-sm text-gray-500 text-center">
                        <div id="actions-${request.id}" class="${isReadOnly ? 'hidden' : 'flex'} space-x-2 justify-center">
                            <button onclick="handleAction(${request.id}, 'Approved')" class="action-btn bg-emerald-500 hover:bg-emerald-600 text-white font-semibold rounded-lg">Approve</button>
                            <button onclick="handleAction(${request.id}, 'Rejected')" class="action-btn bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg">Reject</button>
                        </div>
                        ${isReadOnly ? '<span class="text-xs text-gray-400 italic">Reviewed</span>' : ''}
                    </td>
                `;
                tableBody.appendChild(row);
            });
        };

        window.handleAction = (id, newStatus) => {
            const index = requests.findIndex(r => r.id === id);
            if (index === -1) return;
            requests[index].status = newStatus;
            renderTable();
        };

        document.addEventListener('DOMContentLoaded', renderTable);
    </script>
</body>
</html>
